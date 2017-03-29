<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

use AffiliateLTP\admin\commissions\New_Commission_Tree_Parser;
use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\admin\Referrals_New_Request;

/**
 * Uses template design pattern to override the pieces we need of the
 * algorithm to use the repeat business data.
 *
 * @author snielson
 */
class Repeat_Commission_Tree_Parser extends New_Commission_Tree_Parser {
    /**
     * The database access layer for the commissions.
     * @var \AffiliateLTP\admin\commissions\Commission_DAL  
     */
    private $commission_dal;
    
    private $agent_lookup_table;
    
    /**
     * Create the re
     * @param \AffiliateLTP\admin\commissions\Commission_DAL $commission_dal
     */
    public function __construct(Commission_DAL $commission_dal) {
        $this->commission_dal = $commission_dal;
    }
    
    // agent_lookup_table[agent_id] -> parent_agent_id
    // agent_lookup_table[agent_id] -> coleadership_id
    
    // commission -> 
    // first need to group commissions by id
    // now scan through and create the same agent heirarchies
    
    private function create_agent_repeat_data(Referrals_New_Request $request) {
        $contract_number = $request->client['contract_number'];
       $results = $this->get_repeat_commission_from_database($contract_number);
       if (empty($results)) {
           throw new RuntimeException("There is no repeat data to parse for the contract number: $contract_number");
       }
       // sort commissions into objects
       // create agents linked to commissions
       // now run through each of the agent commissions and find the child 
       
       $commissions_by_id = [];
       $agents_by_id = [];
       foreach ($results as $result) {
           $commission_id = $result['referral_id'];
           $agent_id = $result['affiliate_id'];
            if (empty($agents_by_id[$agent_id])) {
               $agent = new \stdClass();
               $agent->id = $result['affiliate_id'];
               $agent->user_id = $record['user_id'];
               $agent->email = $record['user_email'];
               $agent->commissions = [];
            }
            else {
                $agent = $agents_by_id[$agent_id];
            }
            
           if (empty($commissions_by_id[$commission_id])) {
               $commission = new \stdClass();
               $commission->id = $commission_id;
               $commission->agent = $agent;
           }
           $agent->commissions[$commission_id] = $commission;
           
           // all of the contexts should be the same
           $commission->is_life_commission = absint($record['context']) == CommissionType::TYPE_LIFE;
            
            // this assumes there is only one value for the meta which there
            // should only be one value. First record takes precedence if there
            // are duplicate meta_keys
            $meta_key = $record['meta_key'];
            $meta_value = $record['meta_value'];
            if (empty($commissions_by_id[$commission_id][$meta_key])) {
                $commissions_by_id[$commission_id][$meta_key] = $meta_value;
            }
       }
    }
    
    
    public function parse(Referrals_New_Request $request) {
        // first we need to do the database pieces to grab all the data we need
        // then we can parse the request.
        
        // hit the database and pull the data
        // TODO: stephen typesafe the client by converting it to an object.
        
        // this repeat_commission_data doesn't work as we can have duplicate agents
        // due to the coleadership problem....
        $repeat_data = $this->commission_dal->get_repeat_commission_data($request->client['contract_number']);
        
        // now we need to go through and setup our lookup tables
        $agents = array_merge([$repeat_data['writing_agent']], $repeat_data['agents']);
        foreach ($agents as $agent) {
            $this->agent_lookup_table[$agent['id']] = $agent;
        }
        
        parent::parse($request);
    }

    protected function get_initial_processor_item_for_agent(Referrals_New_Request $request, $agent) {
        
        $agent_data = $this->get_repeat_agent_data($agent->id);
        $splitPercent = $agent_data['split'];

        $item = $this->create_initial_processor_item($agent->id);
//                $item->amount = $request->amount * $splitPercent;
//            // TODO: stephen not sure I like this split rate piece here
//            // either it needs to be it's own attribute or other things like contract_number should go there.
        $item->meta_items['split_rate'] = $splitPercent;
        $item->date = $request->date;
        $item->is_direct_sale = true;
        $item->points = $request->points;
        $item->type = $request->type;
        $item->contract_number = $request->client['contract_number'];
        $item->client_id = $request->client['id'];
        return $item;
    }

    protected function get_parent_for_node(Commission_Node $node) {
        $agent_data = $this->get_repeat_agent_data($node->agent->id);
        $parent_node = null;
        $parent_agent_id = $agent_data['parent_agent_id'];
        if (!empty($parent_agent_id)) {
            $parent_node = $this->create_initial_processor_item($node, $parent_agent_id);
        }
        return $parent_node;
    }

    protected function get_coleadership_for_node(Commission_node $node) {
        $agent_data = $this->get_repeat_agent_data($node->agent->id);
        $coleadership_node = null;
        $coleadership_id = $agent_data['coleadership_id'];
        if (!empty($coleadership_id)) {
            $coleadership_node = $this->create_initial_processor_item($node, $coleadership_id);
            $coleadership_node->coleadership_rate = $agent_data['coleadership_rate'];
        }
        return $coleadership_node;
    }

    protected function create_initial_processor_item($agent_id, Commission_Node $child_node = null) {
        $agent_data = $this->get_repeat_agent_data($agent_id);
        $item = new Commission_Node();
        $agent = new Agent_Data();
        $agent->id = $agent_id;
        $agent->rank = $agent_data['rank'];
        $agent->life_license_status = $this->agent_dal->get_life_license_status($agent_id);
        
        $agent->is_partner = $this->is_partner($agent->rank);
        $item->agent = $agent;
        
        if ($agent->is_partner && !empty($child_node)) {
            $item->generational_count = $child_node->generational_count + 1;
            $agent->rate = $this->get_generational_override_rate($item->generational_count);
        }
        else {
            $agent->rate = $agent_data['rate'];
        }
        return $item;
    }
    
    private function get_repeat_agent_data($agent_id) {
        if (!empty($this->agent_lookup_table[$agent_id])) {
            return $this->agent_lookup_table[$agent_id];
        }
        // TODO: stephen fix this.
//        throw new \InvalidArgumentException("");
    }
    
    private function get_repeat_commission_from_database($contract_number) {
        // if we want override sales agents here.
        $sql = <<<EOD
select 
    a.affiliate_id, a.user_id, wu.user_email,
    ref.referral_id,ref.status,ref.amount,ref.custom, ref.context,
    ref.reference,ref.date, 
    refm.meta_id,refm.meta_key,refm.meta_value
FROM
    wp_affiliate_wp_referrals ref
    JOIN 
        wp_affiliate_wp_referralmeta refm 
            ON ref.referral_id = refm.referral_id
    JOIN 
        wp_affiliate_wp_affiliates a 
            ON ref.affiliate_id = a.affiliate_id
    JOIN
        wp_users wu
            ON a.user_id = wu.ID
WHERE 
    ref.referral_id IN (
        select 
            DISTINCT r.referral_id 
        FROM 
            wp_affiliate_wp_referrals r 
        JOIN 
            wp_affiliate_wp_referralmeta rm 
                ON r.referral_id = rm.referral_id
WHERE 
    r.reference = '%s'
    AND meta_key = 'new_business' and meta_value = 'Y'
)
ORDER BY 
    ref.date asc
EOD;
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare($sql, $contract_number), ARRAY_A);
        if ($results->num_rows > 0) {
            error_log('rows returned ' . $results->num_rows);
            return $results;
        }
        else {
            error_log('num rows was 0');
        }
        return null;
    }

}
