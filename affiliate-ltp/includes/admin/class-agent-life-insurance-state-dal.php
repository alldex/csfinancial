<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Returns the life insurance state data needed for agents.
 *
 * @author snielson
 */
class Agent_Life_Insurance_State_DAL {
    
    public function get_default_agent_license_list() {
        $state_dal = new State_DAL();
        $default_state_list = $state_dal->get_license_required_states();
        $state_licenses = array_map(function ($x) { $x['licensed'] = false; return $x;}, $default_state_list);
        return $state_licenses;
    }
    
    public function get_state_licensing_for_agent( $agent_id ) {
        $life_license_states = affwp_get_affiliate_meta( $agent_id, 'life_license_states', true );
        if (empty($life_license_states)) {
            return [];
        }
        
        try {
            $agent_licenses = json_decode($life_license_states, true);
            return $agent_licenses;
        }
        catch (Exception $ex) {
            error_log($ex->getMessage());
            return [];
        }
    }
    
    public function save_state_licensing_for_agent( $agent_id, $state_licenses) {
        // clear out existing meta data
        affwp_delete_affiliate_meta($agent_id, 'life_license_states');
        
        $data = json_encode($state_licenses);
        
        $result = affwp_add_affiliate_meta( $agent_id, 'life_license_states', $data);
        if (empty($result)) {
            error_log("failed to save life_license_states for agent $agent_id");
            return false;
        }
        return true;
    }
}
