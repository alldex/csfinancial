<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */


namespace AffiliateLTP;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\I_Register_Hooks_And_Actions;

/**
 * Ajax handler for searching for partners
 *
 * @author snielson
 */
class Agent_Partner_Search_AJAX implements I_Register_Hooks_And_Actions {
    
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
    
    public function register_hooks_and_actions() {
        add_filter('wp_ajax_affwp_ltp_search_partners', array($this, 'ajax_search_partners'));
        // make it so non logged in users can access it also.
        add_filter('wp_ajax_nopriv_affwp_ltp_search_partners', array($this, 'ajax_search_partners'));
    }
    
    public function ajax_search_partners() {
        
        $searchQuery = htmlentities2(trim($_REQUEST['term']));
        
        $partner_rank_id = $this->settings_dal->get_partner_rank_id();
        
        $partners = $this->agent_dal->search_agents_by_name_and_rank($searchQuery, $partner_rank_id);
        $jsonResults = [];
        foreach ($partners as $partner) {
            $jsonResults[] = ["label" => $partner['display_name'], "user_id" => $partner['id'], "value" => $partner['display_name']];
        }
        wp_die(json_encode($jsonResults)); // this is required to terminate immediately and return a proper response
    }

    

}
