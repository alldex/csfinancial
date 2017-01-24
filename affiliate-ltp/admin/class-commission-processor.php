<?php

namespace AffiliateLTP\admin;

use AffiliateLTP\Plugin;
use \AffiliateLTPReferralsNewRequest;
use AffiliateLTP\CommissionType;

// TODO: stephen need to save the agent_parent_id piece... 
// how to do this I'm not sure.
require_once 'class-commission-company-processor.php';

class Commission_Processor_Item {

    public $amount;
    public $agent_id;
    public $points;
    public $type;
    public $is_direct_sale;
    public $date;
    public $generational_count = 0;
    // TODO: stephen would be it better to rename this to be highest_previous_rate?
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

    /**
     * Holds the commission arrays that were created during processing.
     * @var array
     */
    private $processed_items;

    const STATUS_DEFAULT = 'unpaid';

    public function __construct(Commission_DAL $commission_dal, Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->commission_dal = $commission_dal;
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
    }

    public function process_commission_request(AffiliateLTPReferralsNewRequest $request) {

        // reset this so we can stay clean.
        $this->processedItems = [];

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
        $items = $this->processed_items;
        $this->processed_items = [];
        return $items;
    }

    private function process_item(Commission_Processor_Item $item, \SplStack $processingStack) {
        if ($this->should_process_item($item)) {


            $adjusted_amount = $item->amount * $item->rate;
//            error_log("Agent '{$item->agent_id}' {$item->amount} * {$item->rate} = $adjusted_amount");
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
            , "client" => array("id" => $item->client_id
                , "contract_number" => $item->contract_number)
            , "points" => $item->points
        );


        // TODO: stephen is this the best place for this?? Do we really want it
        // on the commission?
        $coleadership_id = $this->agent_dal->get_agent_coleadership_agent_id($item->agent_id);
        if ($coleadership_id) {
            $coleadership_rate = $this->agent_dal->get_agent_coleadership_agent_rate($item->agent_id);
            $commission['coleadership_id'] = $coleadership_id;
            $commission['coleadership_rate'] = $coleadership_rate;
        }
//        error_log("gen count: {$item->generational_count}");
        if ($item->generational_count > 0) {
            $commission['description'] .= "- {$item->generational_count} Generation ";
        }

        // create referral
        $commission_id = $this->commission_dal->add_commission($commission);
        //$commission_id = affiliate_wp()->referrals->add($commission);

        if ($commission_id) {
            $this->processed_items[] = $commission;
            do_action('affwp_ltp_commission_created', $commission);
            return $commission_id;
        } else {
            // TODO: stephen add more details here.
            throw new \Exception("Failed to create commission id for commission data: " . var_export($commission, true));
        }
    }

    private function should_process_item($item) {
        if (!$this->agent_dal->is_active($item->agent_id)) {
            return false;
        }
        if ($item->type === CommissionType::TYPE_LIFE && !$this->agent_dal->is_life_licensed($item->agent_id)) {
            return false;
        }

        return true;
    }

    private function process_parent_item(Commission_Processor_Item $item, \SPLStack $processingStack) {
        if ($this->is_partner($item->agent_id)) {
//            error_log("agent is partner, processing generational override");
            $this->add_generational_override_agent($item, $processingStack);
        } else if ($this->agent_has_coleadership($item)) {
//            error_log("agent has co-leadership, processing coleadership");
            $this->add_coleadership_agents($item, $processingStack);
        } else {
//            error_log("calling add_parent_agent");
            $this->add_parent_agent($item, $processingStack);
        }
    }

    private function agent_has_coleadership(Commission_Processor_Item $item) {
        $coleadership_id = $this->agent_dal->get_agent_coleadership_agent_id($item->agent_id);
        if (!empty($coleadership_id)) {
            return true;
        }
        return false;
    }

    private function add_parent_agent(Commission_Processor_Item $child_item, \SplStack $processingStack) {

        $parent_agent_id = $this->agent_dal->get_parent_agent_id($child_item->agent_id);
        if ($parent_agent_id != null) {
            $parent_item = $this->create_parent_processor_item($child_item, $parent_agent_id);
            $processingStack->push($parent_item);
        }
    }

    private function create_parent_processor_item(Commission_Processor_Item $child_item, $parent_agent_id) {

        // we have to use the highest rate for our calculations.
        $previous_rate = max($child_item->rate, $child_item->previous_rate);
        $parent_rate = $this->agent_dal->get_agent_commission_rate($parent_agent_id);

        //$child_rate = $this->agent_dal->get_agent_commission_rate($child_item->agent_id);
        $adjusted_rate = $this->get_adjusted_rate($parent_rate, $previous_rate);
//        error_log("previous rate is {$previous_rate} and parent rate is $parent_rate, adjusted rate is $adjusted_rate");

        $item = clone $child_item;
        $item->agent_id = $parent_agent_id;
        $item->is_direct_sale = false;
        $item->previous_rate = max($previous_rate, $parent_rate);
//        error_log("setting previous rate to be $previous_rate");
        $item->rate = $adjusted_rate;
        return $item;
    }

    private function get_adjusted_rate($nominal_rate, $previous_rate) {
        if ($nominal_rate > $previous_rate) {
            return $nominal_rate - $previous_rate;
        }
        return 0;
    }

    private function is_partner($agent_id) {

        $rank = $this->agent_dal->get_agent_rank($agent_id);
        $partner_rank_id = $this->settings_dal->get_partner_rank_id();
//        error_log("is_partner: agent rank '$rank' partner rank id '$partner_rank_id'");
        if (!empty($partner_rank_id) && $rank === $partner_rank_id) {
            return true;
        }
        return false;
    }

    /**
     * Assumes the parent agent is a partner
     * @param \AffiliateLTP\admin\Commission_Processor_Item $child_item
     * @param \SplStack $processingStack
     */
    private function add_generational_override_agent(Commission_Processor_Item $child_item, \SplStack $processingStack) {
        // if partner create a new item and increment the generational override

        $coleadership_agent_id = $this->agent_dal->get_agent_coleadership_agent_id($child_item->agent_id);
        $parent_agent_id = $this->agent_dal->get_parent_agent_id($child_item->agent_id);
        if (empty($parent_agent_id) && empty($coleadership_agent_id)) {
            return;
        }

        if (!empty($coleadership_agent_id)) {
//            error_log("generational override has co-leadership.  Creating co-leadership generational override");
            $this->add_parent_partner_coleadership_items($child_item, $coleadership_agent_id, $parent_agent_id, $processingStack);
        } else if ($this->is_partner($parent_agent_id)) {
//            error_log("parent is partner, adding generational item");
            $processingStack->push($this->get_generational_item($child_item, $parent_agent_id));
        } else {
//            error_log("adding parent agent as parent is not a partner nor coleadership");
            $this->add_parent_agent($child_item, $processingStack);
        }
    }

    private function add_parent_partner_coleadership_items(Commission_Processor_Item $child_item, $coleadership_agent_id, $parent_agent_id, \SplStack $processingStack) {
        $coleadership_is_partner = !empty($coleadership_agent_id) && $this->is_partner($coleadership_agent_id);
        $rate = $this->agent_dal->get_agent_coleadership_agent_rate($child_item->agent_id);
        $active_item = clone $child_item;
        $active_item->points = round($child_item->points * $rate, 2);
        $active_item->amount = round($child_item->amount * $rate, 2);

        $passive_item = clone $child_item;
        $passive_item->points = $child_item->points - $active_item->points;
        $passive_item->amount = $child_item->amount - $active_item->amount;

        if ($coleadership_is_partner) {
            $gen_item = $this->get_generational_item($active_item, $coleadership_agent_id);
            $processingStack->push($gen_item);
        } else {
            $parent_item = $this->create_parent_processor_item($active_item, $coleadership_agent_id);
            $processingStack->push($parent_item);
        }

        $passive_gen_item = $this->get_generational_item($passive_item, $parent_agent_id);
        $processingStack->push($passive_gen_item);
    }

    private function get_generational_item($child_item, $parent_agent_id) {
        $item = clone $child_item;
        $item->agent_id = $parent_agent_id;
        $item->is_direct_sale = false;
        $item->previous_rate = $item->rate;
        $item->rate = $this->get_generational_rate($item);
        $item->generational_count += 1;
        return $item;
    }

    private function get_generational_rate(Commission_Processor_Item $item) {
        // TODO: stephen... how do we handle the situation where we have a generational override
        // that is at 17%, but the person above them is not a partner....
        // how do we handle the calculations for this? Or if two levels up the person is not a partner...
        // If their commission is 50% - 17% we'll have > 100% for our commission calculations....
        // we currently only handle three generational overrides.
        return $this->settings_dal->get_generational_override_rate($item->generational_count + 1);
    }

    /**
     * Adds the active leader (who is not a direct parent) and the passive leader
     * who is the parent of the current agent.  The points and amounts are adjusted
     * 
     * @param \AffiliateLTP\admin\Commission_Processor_Item $item
     * @param \SplStack $processingStack
     */
    private function add_coleadership_agents(Commission_Processor_Item $item, \SplStack $processingStack) {

        $coleadership_id = $this->agent_dal->get_agent_coleadership_agent_id($item->agent_id);
        $coleadership_rate = $this->agent_dal->get_agent_coleadership_agent_rate($item->agent_id);

        $active_leader_item = clone $item;
        $active_leader_item->amount = round($item->amount * $coleadership_rate, 2);
        $active_leader_item->points = round($item->points * $coleadership_rate, 2);

        $processingStack->push($this->create_parent_processor_item($active_leader_item, $coleadership_id));

        // since we already have an add parent let's just call that to make sure
        // it pushes at the top of the stack.
        $passive_adjusted_child_item = clone $item;
        $passive_adjusted_child_item->amount = $item->amount - $active_leader_item->amount;
        $passive_adjusted_child_item->points = $item->points - $active_leader_item->points;
        $this->add_parent_agent($passive_adjusted_child_item, $processingStack);
    }

    private function get_initial_commissions_to_process_from_request(AffiliateLTPReferralsNewRequest $request) {
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
            $item->amount = $request->amount * $splitPercent;
            ;
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
        $instance = Plugin::instance()->getSugarCRM();
        return $instance->createAccount($clientData);
    }

    private function process_company_commission(AffiliateLTPReferralsNewRequest $request) {

        $company_processor = new Commission_Company_Processor($this->commission_dal, $this->settings_dal);

        return $company_processor->create_company_commission($request);
    }

}
