<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AffiliateLTP\admin;

use \AffiliateLTPAffiliates;
/**
 * Description of class-agent-dal-affiliate-wp-adapter
 *
 * @author snielson
 */
class Agent_DAL_Affiliate_WP_Adapter implements Agent_DAL {
    
    public function filter_agents_by_licensed_life_agents( $upline ) {
        $licensedAgents = array();
        
        foreach ( $upline as $agentId ) {
            if ($this->is_life_licensed($agentId)) {
                $licensedAgents[] = $agentId;
            }
        }
        return $licensedAgents;
    }
    
    public function filter_agents_by_status( $upline, $status = 'active' ) {
        return affwp_mlm_filter_by_status( $upline, $status );
    }
    
    public function get_agent_status( $agent_id ) {
        return affwp_get_affiliate_status( $agent_id );
    }

    public function get_agent_commission_rate( $agent_id ) {
        return affwp_get_affiliate_rate( $agent_id );
    }
    
    public function get_agent_upline( $agent_id ) {
        return affwp_mlm_get_upline( $agent_id );
    }
    
    public function is_life_licensed($agent_id) {
        return AffiliateLTPAffiliates::isAffiliateCurrentlyLifeLicensed($agent_id);
    }
    
    public function is_active($agent_id) {
        return $this->get_agent_status($item->agent_id) === 'active';
    }
    
    public function get_parent_agent_id($agent_id) {
        $parent_agent_id = null;
        
        if ( affwp_mlm_is_sub_affiliate( $agent_id ) ) {
            $parent_agent_id = affwp_mlm_get_parent_affiliate( $agent_id );
        }
        
        return $parent_agent_id;
    }
}
