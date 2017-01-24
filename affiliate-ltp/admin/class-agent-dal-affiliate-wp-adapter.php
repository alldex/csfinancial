<?php
namespace AffiliateLTP\admin;

/**
 * Description of class-agent-dal-affiliate-wp-adapter
 *
 * @author snielson
 */
class Agent_DAL_Affiliate_WP_Adapter implements Agent_DAL {
    
    const AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID = 'coleadership_agent_id';
    const AFFILIATE_META_KEY_COLEADERSHIP_AGENT_RATE = 'coleadership_agent_rate';
    
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
        return Affiliates::isAffiliateCurrentlyLifeLicensed($agent_id);
    }
    
    public function is_active($agent_id) {
        return $this->get_agent_status($agent_id) === 'active';
    }
    
    public function get_parent_agent_id($agent_id) {
        $parent_agent_id = null;
        
        if ( affwp_mlm_is_sub_affiliate( $agent_id ) ) {
            $parent_agent_id = affwp_mlm_get_parent_affiliate( $agent_id );
        }
        
        return $parent_agent_id;
    }

    public function get_agent_rank($agent_id) {
        $rank_id = affwp_ranks_get_affiliate_rank( $agent_id );
        if (empty($rank_id)) {
            return null;
        }
        return $rank_id;
    }

    public function get_agent_coleadership_agent_id( $agent_id ) {
        $single = true;
        $id = affiliate_wp()->affiliate_meta->get_meta($agent_id, 
                self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID, $single);
        if (empty($id)) {
            return null;
        }
        return absint($id);
    }

    public function get_agent_coleadership_agent_rate($agent_id) {
        $single = true;
        $rate = absint(affiliate_wp()->affiliate_meta->get_meta($agent_id, 
                self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_RATE, $single));
        // rates are in whole numbers
        if ($rate > 0) {
            $rate /= 100;
        }
        return $rate;
    }
}
