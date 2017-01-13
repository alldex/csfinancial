<?php

namespace AffiliateLTP\admin;

use \AffiliateLTPReferralsNewRequest;
use \AffiliateLTPCommissionType;
use \AffiliateLTP;

require_once 'class-commission-company-processor.php';

class Commission_Processor_Item {
    public $amount;
    public $agent_id;
    public $points;
    public $type;
    public $is_direct_sale;
    public $date;
    public $generational_count = 0;
    public $previous_rate = 0;
    public $rate = 0;
    public $contract_number;
    public $client_id;
    
}
/**
 * 
 *
 * @author snielson
 */
class Commission_Processor {
    
    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;
    
    const STATUS_DEFAULT = 'unpaid';
    
    public function __construct(Commission_DAL $commission_dal, Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->commission_dal = $commission_dal;
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
    }
    
    public function process_commission_request(AffiliateLTPReferralsNewRequest $request) {
        
        // create the client if necessary
        $request->client['id'] = $this->createClient($request->client);
        
        // process the company
        $updatedRequest = $this->process_company_commission($request);
        
        $processingStack = $this->get_initial_commissions_to_process_from_request($updatedRequest);
        $stackBreakCount = 0;
        while (!$processingStack->isEmpty() && 500 >= $stackBreakCount++) {
            $item = $processingStack->pop();
            $this->process_item($item, $processingStack);
        }
        
    }
    
    private function process_item(Commission_Processor_Item $item, \SplStack $processingStack) {
        // create the commission for yourself
        
        // use the previous rate unless we process this one.
        $rate = $item->previous_rate;
        
        if ($this->should_process_item($item)) {

            
            $adjusted_amount = $item->amount * $item->rate;
            
            $commission_id = $this->create_commission_for_item($item, $adjusted_amount);
        }
        // TODO: stephen add logging to state that the commission was added.
        
        $this->process_parent_item($item, $processingStack);
    }
    
    private function create_commission_for_item(Commission_Processor_Item $item, $adjusted_amount) {
        $custom = 'direct';
        $description = __("Personal sale", "affiliate-ltp");
        if (!$item->is_direct_sale) {
            $custom = 'indirect';
            $description = __("Override", "affiliate-ltp");
        }

        $commission = array(
            "affiliate_id" => $item->agent_id
            , "description" => $description
            , "amount" => $adjusted_amount
            , "reference" => $item->contract_number
            , "custom" => $custom
            , "context" => $item->type
            , "status" => self::STATUS_DEFAULT
            , "date" => $item->date
            , "agent_rate" => $item->rate
            , "client" => array("id" => $item->client_id, 
                "contract_number" => $item->contract_number)
            , "points" => $item->points
        );

        // create referral
        $commission_id = $this->commission_dal->add_commission($commission);
        //$commission_id = affiliate_wp()->referrals->add($commission);

        if ($commission_id) {
            do_action('affwp_ltp_commission_created', $commission);
            return $commission_id;
        } else {
            // TODO: stephen add more details here.
            throw new \Exception("Failed to create commission id for commission data: " . var_export($commission, true));
        }
    }
    
    private function should_process_item( $item ) {
        if (!$this->agent_dal->is_active( $item->agent_id )) {
            return false;
        }
        if ($item->type === AffiliateLTPCommissionType::TYPE_LIFE
                && !$this->agent_dal->is_life_licensed( $item->agent_id )) {
            return false;
        }
        
        return true;
    }
    
    private function process_parent_item(Commission_Processor_Item $item, \SPLStack $processingStack) {
         // grab your rank.
        // TODO: stephen when co-leadership is implemented
        // put the check here.
        $is_coleadership = false;
        
        // if coleadership
        // addColeadershipAgentsToStack($stack)
        // else if partner
        // addGenerationalOverride($item, $stack)
        // else
        // addParentAgent($item, $stack)
        if ($is_coleadership) {
            $this->add_coleadership_agents($item, $processingStack);
        }
        else if ($this->is_partner($item->agent_id)) {
            $this->add_generational_override_agent($item, $processingStack);
        }
        else {
            $this->add_parent_agent($item, $processingStack);
        }
    }
    
    private function add_parent_agent(Commission_Processor_Item $child_item,
            \SplStack $processingStack) {
        
        $parent_agent_id = $this->agent_dal->get_parent_agent_id( $child_item->agent_id );
        if ($parent_agent_id != null) {
            $rate = $this->agent_dal->get_agent_commission_rate($parent_agent_id);
            $child_rate = $this->agent_dal->get_agent_commission_rate($child_item->agent_id);
            $adjusted_rate = ($rate > $child_rate) ? $rate - $child_rate : 0;
            
            $item = clone $child_item;
            $item->agent_id = $parent_agent_id;
            $item->is_direct_sale = false;
            $item->previous_rate = $item->rate;
            $item->rate = $adjusted_rate;
            
            
            $processingStack->push($item);
        }
    }
    
    private function is_partner( $agent_id ) {
        
        $rank = $this->agent_dal->get_agent_rank($agent_id);
        $partner_rank_id = $this->settings_dal->get_partner_rank_id();
        if (!empty($partner_rank_id) && $rank === $partner_rank_id) {
            return true;
        }
        return false;
    }
    
    private function add_generational_override_agent(Commission_Processor_Item $child_item, 
            \SplStack $processingStack) {
        // if partner create a new item and increment the generational override
        
        $parent_agent_id = $this->agent_dal->get_parent_agent_id( $child_item->agent_id );
        if (empty($parent_agent_id)) {
            return;
        }
        
        if (!$this->is_partner($parent_agent_id)) {
            $this->add_parent_agent($child_item, $processingStack);
            return;
        }
        // calculate the percent
        $item = clone $child_item;
        $item->agent_id = $parent_agent_id;
        $item->is_direct_sale = false;
        $item->previous_rate = $item->rate;
        $item->rate = $this->get_generational_rate($item);
        $item->generational_count += 1;
        $processingStack->push($item);
        
        
    }
    
    private function get_generational_rate(Commission_Processor_Item $item) {
        // TODO: stephen... how do we handle the situation where we have a generational override
        // that is at 17%, but the person above them is not a partner....
        // how do we handle the calculations for this? Or if two levels up the person is not a partner...
        // If their commission is 50% - 17% we'll have > 100% for our commission calculations....

        // we currently only handle three generational overrides.
        return $this->settings_dal->get_generational_override_rate($item->generational_count + 1);
    }
    
    private function add_coleadership_agents($child_agent_id, 
            \SplStack $processingStack) {
        
    }
    
    private function get_initial_commissions_to_process_from_request(AffiliateLTPReferralsNewRequest $request){
        $stack = new \SplStack();
        
        // if the company is taking everything we don't process anything for other
        // agents
        if ($request->companyHaircutAll) {
            return $stack;
        }
        
        foreach ($request->agents as $agent) {
            $splitPercent = $agent->split / 100;
            
            $rate = $this->agent_dal->get_agent_commission_rate($agent->id);
            
            $item = new Commission_Processor_Item();
            $item->amount = $request->amount * $splitPercent;;
            $item->agent_id = $agent->id;
            $item->date = $request->date;
            $item->is_direct_sale = true;
            $item->points = $request->points;
            $item->type = $request->type;
            $item->contract_number = $request->client['contract_number'];
            $item->client_id = $request->client['id'];
            $item->rate = $rate;
            $stack->push($item);
        }
        
        return $stack;
    }
    
     /**
     * Creates the client and returns the id of the client that was created.
     * @param array $clientData
     * @return string
     */
    private function createClient($clientData) {
        // we already have a client id we don't need to create a client here
        if (!empty($clientData['id'])) {
            // we return the id to keep the code flow the same.
            return $clientData['id'];
        }
        // create the client on the sugar CRM system.
        $instance = AffiliateLTP::instance()->getSugarCRM();
        return $instance->createAccount($clientData);
    }

    
    private function process_company_commission(AffiliateLTPReferralsNewRequest $request) {
        
        $company_processor = new Commission_Company_Processor($this->commission_dal, $this->settings_dal);
        
        return $company_processor->create_company_commission($request);
    }

    private function create_commission($affiliateId, $amount, $reference, $directAffiliate, $paymentRate, $points, $date, $type) {

        $custom = 'direct';
        $description = __("Personal sale", "affiliate-ltp");
        if (!$directAffiliate) {
            $custom = 'indirect';
            $description = __("Override", "affiliate-ltp");
        }

        $commission = array(
            "affiliate_id" => $affiliateId
            , "description" => $description
            , "amount" => $amount
            , "reference" => $reference
            , "custom" => $custom
            , "context" => $type
            , "status" => self::STATUS_DEFAULT
            , "date" => $date
        );

        // create referral
        $commission_id = $this->commission_dal->add_commission($commission);
        //$commission_id = affiliate_wp()->referrals->add($commission);

        if ($commission_id) {
            $this->add_commission_meta($commission_id, $paymentRate, $points);

            // TODO: stephen not sure this is needed anymore... or if it is pass the array of data.
            do_action('affwp_ltp_referral_created', $commission_id, $description, $amount, $reference, $custom);
            return $commission_id;
        } else {
            // TODO: stephen add more details here.
            throw new \Exception("Failed to create commission id for commission data: " . var_export($commission, true));
        }
    }

    
    private function add_commission_meta($referralId, $paymentRate, $points) {
        // TODO: stephen should we refactor so we add in the client info here?
        $this->commission_dal->add_commission_meta($referralId, 'agent_rate', $paymentRate);
        $this->commission_dal->add_commission_meta($referralId, 'points', $points);
    }

    private function processAgentSplits(AffiliateLTPReferralsNewRequest $request) {
        // if the company is taking everything we don't process anything for other
        // agents
        if ($request->companyHaircutAll) {
            return;
        }
        
        foreach ($request->agents as $agent) {
            $currentAmount = $request->amount;
            $splitPercent = $agent->split / 100;
            $computedAmount = $request->amount * $splitPercent;
            $request->amount = $computedAmount;
            $this->createCommissionHeirarchy($agent->id, $computedAmount, $request);
            $request->amount = $currentAmount; // keep amount at the same to prevent modification lower down.
        }
    }

    private function createCommissionHeirarchy($agentId, $amount, $referralData) {
        $reference = $referralData->client['contract_number'];
        $points = $referralData->points;

        $upline = $this->agent_dal->get_agent_upline( $agentId );
        if ($upline) {
            $upline = $this->agent_dal->filter_agents_by_status($upline, 'active');
        }

        $affiliates = array_merge(array($agentId), $upline);
        
        if ($referralData->type == AffiliateLTPCommissionType::TYPE_LIFE) {
            $affiliates = $this->agent_dal->filter_agents_by_licensed_life_agents( $affiliates );
        }
        // if there are no licensed agents
        // TODO: stephen if there are no licensed agents for this commission how do we handle that?
        if (empty($affiliates)) {
            return;
        }
        
        $levelCount = 0;
        $priorAffiliateRate = 0;


        do {
            $affiliateId = array_shift($affiliates);
            $levelCount++;
            $affiliateRate = $this->agent_dal->get_agent_commission_rate( $affiliateId );

            $adjustedRate = ($affiliateRate > $priorAffiliateRate) ? $affiliateRate - $priorAffiliateRate : 0;
            $adjustedAmount = $amount * $adjustedRate;
            $priorAffiliateRate = $affiliateRate;

            $directAffiliate = ($levelCount === 1);
            $referralId = $this->create_commission($affiliateId, $adjustedAmount, $reference, $directAffiliate, $adjustedRate, $points, $referralData->date, $referralData->type);
            $this->connectCommissionToClient($referralId, $referralData->client);
        } while (!empty($affiliates));
    }
   
     
    private function connectCommissionToClient($referral_id, $client_data) {
        $this->commission_dal->connect_commission_to_client( $referral_id, $client_data );
    }
}
