<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */


namespace AffiliateLTP;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;
use Psr\Log\LoggerInterface;


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
    
    /**
     * The current downline of the agent
     * @var 
     */
    private $current_agent_downline = null;
    
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(LoggerInterface $logger, 
            Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
        $this->logger = $logger;
    }
    
    
    public function ajax_search_agents() {
        $searchQuery = htmlentities2(trim(filter_input(INPUT_GET, 'term')));
        $agents = $this->agent_dal->search_agents_by_code($searchQuery);
        $jsonResults = [];
        if (!empty($agents)) {
            
            foreach ($agents as $agent) {
                if ($this->can_see_agent($agent)) {
                    $jsonResults[] = ["label" => $agent['display_name'], "user_id" => $agent['agent_id'], "value" => $agent['display_name']];
                }
            }
        }
        wp_die(json_encode($jsonResults)); // this is required to terminate immediately and return a proper response
    }

    public function register_hooks_and_actions() {
        add_filter('wp_ajax_affwp_ltp_search_agents', array($this, 'ajax_search_agents'));
    }
    
    private function can_see_agent($agent) {
        $agent_id = $agent['agent_id'];
        
        if (current_user_can("administrator")) {
            return true;
        }
        
        $current_agent_id = $this->agent_dal->get_current_user_agent_id();
        if (empty($current_agent_id)) {
            $this->logger->info("current user is not an agent.  Cannot check if agent $agent_id in downline");
            return false;
        }
        
        $downline_tree = $this->agent_dal->get_agent_downline_with_coleaderships($current_agent_id);
        $this->logger->debug("Checking agent($current_agent_id) downline for agent($agent_id)");
       
        foreach ($downline_tree as $node) {
            $this->logger->debug("Checking agent({$node->id}) == agent($agent_id)");
            if ($node->id == $agent_id) {
                return true;
            }
        }
        
        return false;
    }
}
