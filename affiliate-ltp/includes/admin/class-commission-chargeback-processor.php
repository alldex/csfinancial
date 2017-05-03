<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\Commission_Type;
use AffiliateLTP\admin\Referrals_New_Request_Builder;
use AffiliateLTP\Commission;

/**
 * Handles commission chargebacks.
 *
 * @author snielson
 */
class Commission_Chargeback_Processor {
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
     * The id of the company agent.
     * @var int
     */
    private $company_agent_id;
    
    public function __construct(Commission_DAL $commission_dal, Agent_DAL $agent_dal, 
            $company_agent_id) {
        $this->commission_dal = $commission_dal;
        $this->agent_dal = $agent_dal;
        $this->company_agent_id = $company_agent_id;
    }
    
    public function process_request($commission_request_id) {
        $commission_request_record = $this->commission_dal->get_commission_request($commission_request_id);
        
        try {
            $request_json = $commission_request_record['request'];
            $request_hydrated = json_decode($request_json, true);
            
            $request = $this->create_request_from_json($request_hydrated);
            $chargeback_commission_request_id = $this->save_chargeback_request($request);
            
            // need to save off the request.
            $commission_ids = $this->commission_dal->get_commission_ids_for_request($commission_request_id);
            foreach ($commission_ids as $commission_id) {
                $commission = $this->commission_dal->get_commission($commission_id);
                $this->charge_back_commission($chargeback_commission_request_id, $commission);
            }
        } catch (Exception $ex) {
            // TODO: stephen make a chargeback exception.
            throw $ex;
        }
    }
    
    private function create_request_from_json($request_hydrated) {
        $request = new Referrals_New_Request();
        $request->amount = $request_hydrated['amount'] * -1;
        $request->client = $request_hydrated['client'];
        $request->type = $request_hydrated['type'];
        $request->points = $request_hydrated['points'] * -1;
        $request->date = $request_hydrated['date'];
        $request->skipCompanyHaircut = $request_hydrated['skipCompanyHaircut'];
        $request->companyHaircutAll = $request_hydrated['companyHaircutAll'];
        $request->agents = [];
        $request->new_business = false; // we do not let chargebacks be new business.
        foreach ($request_hydrated['agents'] as $json_agent) {
            $agent = new Referrals_Agent_Request();
            $agent->id = $json_agent['id'];
            $agent->split = $json_agent['split'];
            $request->agents[] = $agent;
        }
            
        return $request;
    }
    
    private function save_chargeback_request(Referrals_New_Request $original_request) {
        $persistor = new Commission_Request_Persister($this->commission_dal);
        // there are no agent trees on the chargeback
        return $persistor->persist($original_request, []);
    }
    
    /**
     * Given the id of the chargeback request and the commission record process
     * and save off the chargeback for this particular commission for an individual
     * agent.
     * @param int $commission_request_id The id 
     * @param int $commission The commission data corresponding to the commission of a single agent in this request.
     */
    public function charge_back_commission($commission_request_id, Commission $commission) {
        $chargeback_commission = clone $commission;
        $chargeback_commission->id = null;
        
        $points = $this->commission_dal->get_commission_agent_points($commission->commission_id);
        
        $chargeback_commission->amount = $commission->amount * -1;
        $chargeback_commission->meta = [];
        $chargeback_commission->meta['points'] = $points * -1; // reverse it.
        $chargeback_commission->meta['chargeback_commission_id'] = $commission->commission_id;
        $chargeback_commission->meta['commission_request_id'] = $commission_request_id;
        $chargeback_commission->description .= " Chargeback";
        
        if ($chargeback_commission->agent_id == $this->company_agent_id) {
            $chargeback_commission->status = Commission_Status::PAID;
        }
        else {
            $chargeback_commission->status = Commission_Status::UNPAID;
        }
        
        $this->commission_dal->add_commission($chargeback_commission);
    }
}
