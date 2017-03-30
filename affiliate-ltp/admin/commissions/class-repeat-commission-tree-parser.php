<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\admin\Agent_DAL;

/**
 * Uses template design pattern to override the pieces we need of the
 * algorithm to use the repeat business data.
 *
 * @author snielson
 */
class Repeat_Commission_Tree_Parser  {
    /**
     * The database access layer for the commissions.
     * @var \AffiliateLTP\admin\commissions\Commission_DAL  
     */
    private $commission_dal;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     * Create the re
     * @param \AffiliateLTP\admin\commissions\Commission_DAL $commission_dal
     */
    public function __construct(Agent_DAL $agent_dal, Commission_DAL $commission_dal) {
        $this->agent_dal = $agent_dal;
        $this->commission_dal = $commission_dal;
    }
    
    public function parse(Referrals_New_Request $request) {
        
        // this would be best to use the original commission id
        $commission_record = $this->commission_dal->get_repeat_commission_record($request->client['contract_number']);
        
        $json_trees = [];
        try {
            if (!empty($commission_record['agent_tree'])) {
                $json_trees = json_decode($commission_record['agent_tree'], true);
            }
        } catch (Exception $ex) {
            // TODO: stephen throw parsing exception here
            throw $ex;
        }
        
        $populated_trees = [];
        foreach ($json_trees as $json_tree) {
            $populated_trees[] = $this->populate_tree($json_tree);
        }
        return $populated_trees;
    }
    
    private function populate_tree(array $json_tree) {
        $item = new Commission_Node();
        foreach ($json_tree as $key => $value) {
            if ($key == 'coleadership_node' || $key == 'parent_node' || $key == 'agent') {
                continue;
            }
            $item->$key = $value;
        }
        
        $item->agent = $this->populate_agent($json_tree['agent']);
        
        if (!empty($json_tree['coleadership_node'])) {
            $item->coleadership_node = $this->populate_tree($json_tree['coleadership_node']);
        }
        if (!empty($json_tree['parent_node'])) {
            $item->parent_node = $this->populate_tree($json_tree['parent_node']);
        }
        return $item;
    }
    private function populate_agent(array $agent_arr) {
        $agent = new Agent_Data();
        
        foreach ($agent_arr as $key => $value) {
            if ($key == 'life_license_status') {
                continue;
            }
            $agent->$key = $value;
        }
        
        $agent->life_license_status = $this->agent_dal->get_life_license_status($agent->id);
        
        return $agent;
    }
}
