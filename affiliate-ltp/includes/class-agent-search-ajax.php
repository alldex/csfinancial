<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */


namespace AffiliateLTP;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;


/**
 * Ajax handler for searching for agents.  Similar to the affwp_search_users function
 * however, this allows non-admin users (but still authenticated) to search for users.
 *
 * @author snielson
 */
class Agent_Search_AJAX implements I_Register_Hooks_And_Actions {
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;
    
    public function __construct(Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
    }
    
    
    public function ajax_search_agents() {
        
        $searchQuery = htmlentities2(trim(filter_input(INPUT_GET, 'term')));
        $agents = $this->agent_dal->search_agents_by_code($searchQuery);
        $jsonResults = [];
        if (!empty($agents)) {
            foreach ($agents as $agent) {
                $jsonResults[] = ["label" => $agent['display_name'], "user_id" => $agent['agent_id'], "value" => $agent['display_name']];
            }
        }
        wp_die(json_encode($jsonResults)); // this is required to terminate immediately and return a proper response
    }

    public function register_hooks_and_actions() {
        add_filter('wp_ajax_affwp_ltp_search_agents', array($this, 'ajax_search_agents'));
    }
}
