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
            if (AffiliateLTPAffiliates::isAffiliateCurrentlyLifeLicensed($agentId)) {
                $licensedAgents[] = $agentId;
            }
        }
        return $licensedAgents;
    }
    
    public function filter_agents_by_status( $upline, $status = 'active' ) {
        return affwp_mlm_filter_by_status( $upline, $status );
    }

    public function get_agent_commission_rate( $agent_id ) {
        return affwp_get_affiliate_rate( $agent_id );
    }
    
    public function get_agent_upline( $agent_id ) {
        return affwp_mlm_get_upline( $agent_id );
    }
}
