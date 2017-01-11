<?php

namespace AffiliateLTP\admin;

use \AffiliateLTPReferralsNewRequest;
use \AffiliateLTPCommissionType;
use \AffiliateLTP;
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
    
    const STATUS_DEFAULT = 'unpaid';
    
    public function __construct(Commission_DAL $commission_dal, Agent_DAL $agent_dal) {
        $this->commission_dal = $commission_dal;
        $this->agent_dal = $agent_dal;
    }
    
    public function processCommissionRequest(AffiliateLTPReferralsNewRequest $request) {
        $this->processCompanyCommission($request);
        $request->client['id'] = $this->createClient($request->client);
        $this->processAgentSplits($request);
        $this->connectCompanyToClient($request, $request->client);
    }
    
     /**
     * Do any work to connect the company referral information to the client.
     * @param array $companyData
     * @param array $clientData
     */
    private function connectCompanyToClient($companyData, $clientData) {
        $this->commission_dal->connect_commission_to_client( $companyData->company_referral_id, $clientData );
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

    
    private function processCompanyCommission(AffiliateLTPReferralsNewRequest $request) {
        
        // do nothing here if we are to skip the company commissions.
        if ($request->skipCompanyHaircut) {
            return;
        }
        
        $companyCommission = affiliate_wp()->settings->get("affwp_ltp_company_rate");
        $companyAgentId = affiliate_wp()->settings->get("affwp_ltp_company_agent_id");
        
        // if the company is taking everything we set the commission to be 100%
        if ($request->companyHaircutAll) {
            $companyCommission = 100;
        }

        // if we have no company agent
        if (empty($companyAgentId)) {
            return $request;
        }

        // make the commission 0 if we don't have anything here so that we get
        // a line item here.
        if (empty($companyCommission)) {
            $companyCommission = 0;
        } else {
            $companyCommission = absint($companyCommission) / 100;
        }

        $amount = $request->amount;
        $companyAmount = round($companyCommission * $amount, 2);
        $amountRemaining = $amount - $companyAmount;

        // create the records for the company commission

        $request->amount = $amountRemaining;
        
        // if we are not a life we will use the points after the company
        // 'haircut' or percentage they took out.
        if ($request->type != AffiliateLTPCommissionType::TYPE_LIFE) {
            $request->points = $amountRemaining;
        }

        // Process cart and get amount
        $companyReferral = array(
            "affiliate_id" => absint($companyAgentId)
            , "description" => __("Override", "affiliate-ltp")
            , "reference" => $request->client['contract_number']
            , "amount" => $companyAmount
            , "custom" => "indirect"
            , "context" => $request->type
            , "status" => self::STATUS_DEFAULT
            , "date" => $request->date
        );


        // create referral
        $referral_id = $this->commission_dal->add_commission( $companyReferral );
        if (empty($referral_id)) {
            error_log("Failed to calculate company commission.  Data array: "
                    . var_export($companyReferral, true));
        } else {
            $request->company_referral_id = $referral_id;
            $this->addReferralMeta($referral_id, $companyCommission, $request->points);
        }

        return $request;
    }

    private function createReferral($affiliateId, $amount, $reference, $directAffiliate, $paymentRate, $points, $date, $type) {

        $custom = 'direct';
        $description = __("Personal sale", "affiliate-ltp");
        if (!$directAffiliate) {
            $custom = 'indirect';
            $description = __("Override", "affiliate-ltp");
        }
        // Process cart and get amount
        $referral = array(
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
        $referral_id = affiliate_wp()->referrals->add($referral);

        if ($referral_id) {
            $this->addReferralMeta($referral_id, $paymentRate, $points);

            // TODO: stephen not sure this is needed anymore... or if it is pass the array of data.
            do_action('affwp_ltp_referral_created', $referral_id, $description, $amount, $reference, $custom, $context, $status);
            return $referral_id;
        } else {
            // TODO: stephen add more details here.
            throw new \Exception("Failed to create referral id for referral data: " . var_export($referral, true));
        }
    }

    
    private function addReferralMeta($referralId, $paymentRate, $points) {
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
            $this->createReferralHeirarchy($agent->id, $computedAmount, $request);
            $request->amount = $currentAmount; // keep amount at the same to prevent modification lower down.
        }
    }

    private function createReferralHeirarchy($agentId, $amount, $referralData) {
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
            $referralId = $this->createReferral($affiliateId, $adjustedAmount, $reference, $directAffiliate, $adjustedRate, $points, $referralData->date, $referralData->type);
            $this->connectReferralToClient($referralId, $referralData->client);
        } while (!empty($affiliates));
    }
   
     
    private function connectReferralToClient($referral_id, $client_data) {
        $this->commission_dal->connect_commission_to_client( $referral_id, $client_data );
    }
}
