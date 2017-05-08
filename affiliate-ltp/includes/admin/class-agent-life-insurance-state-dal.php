<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Description of class-agent-life-insurance-state-dal
 *
 * @author snielson
 */
class Agent_Life_Insurance_State_DAL {
    
    public function get_default_agent_license_list() {
        $default_state_list = $this->get_default_state_list();
        $state_licenses = array_map(function ($x) { $x['licensed'] = false; return $x;}, $default_state_list);
        return $state_licenses;
    }
    
    public function get_state_licensing_for_agent( $agent_id ) {
        $life_license_states = affwp_get_affiliate_meta( $agent_id, 'life_license_states', true );
        if (empty($life_license_states)) {
            return $this->get_default_agent_license_list();
        }
        
        $default_list = $this->get_default_state_list();
        $agent_licenses = json_decode($life_license_states, true);
        
        $updated_licenses = [];
        foreach ($default_list as $state) {
            if (!empty($agent_licenses[$state['abbr']])) {
                $state['licensed'] = true;
            }
            else {
                $state['licensed'] = false;
            }
            $updated_licenses[] = $state;
        }
        return $updated_licenses;
    }
    
    public function save_state_licensing_for_agent( $agent_id, $state_licenses) {
        // clear out existing meta data
        affwp_delete_affiliate_meta($agent_id, 'life_license_states');
        
        $data = json_encode($state_licenses);
        
        $result = affwp_add_affiliate_meta( $agent_id, 'life_license_states', $data);
    }
    
    public function get_default_state_list() {
        $state_licenses = [
            [
                "name" => "Alabama"
                ,"abbr" => "AL"
            ]
            ,[
                "name" => "California"
                ,"abbr" => "CA"
            ]
            ,[
                "name" => "Florida"
                ,"abbr" => "FL"
            ]
            ,[
                "name" => "Georgia"
                ,"abbr" => "GA"
            ]
            ,[
                "name" => "Kentucky"
                ,"abbr" => "KY"
            ]
            ,[
                "name" => "Louisiana"
                ,"abbr" => "LA"
            ]
            ,[
                "name" => "Massachussets"
                ,"abbr" => "MA"
            ]
            ,[
                "name" => "Mississippi"
                ,"abbr" => "MS"
            ]
            ,[
                "name" => "Montana"
                ,"abbr" => "MT"
            ]
            ,[
                "name" => "New Mexico"
                ,"abbr" => "NM"
            ]
            ,[
                "name" => "Pennsylvania"
                ,"abbr" => "PA"
            ]
            ,[
                "name" => "South Dakota"
                ,"abbr" => "SD"
            ]
            ,[
                "name" => "Utah"
                ,"abbr" => "UT"
            ]
            ,[
                "name" => "Virginia"
                ,"abbr" => "VA"
            ]
            ,[
                "name" => "West Virginia"
                ,"abbr" => "WV"
            ]
            ,[
                "name" => "Wisconsin"
                ,"abbr" => "WI"
            ]
        ];
        return $state_licenses;
    }
}
