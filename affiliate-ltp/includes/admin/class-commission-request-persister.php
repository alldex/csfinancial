<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

use AffiliateLTP\admin\Commission_DAL;

/**
 * Responsible for saving off the commission request objects.
 *
 * @author snielson
 */
class Commission_Request_Persister {
    
    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;
    
    public function __construct(Commission_DAL $dal) {
        $this->commission_dal = $dal;
    }
    
    public function persist(Referrals_New_Request $request, $agent_trees) {
        $commission_request = [
            "writing_agent_id" => $request->agents[0]->id
           ,"contract_number" => $request->client['contract_number']
           ,"amount" => $request->amount
                ,"points" => $request->points
                ,"request_type" => $request->type
                ,"new_business" => $request->new_business ? "Y" : "N"
                ,"request" => json_encode($request)
                ,"agent_tree" => json_encode($agent_trees)
        ];
        return $this->commission_dal->add_commission_request($commission_request);
    }
}
