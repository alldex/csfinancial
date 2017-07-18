<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;

/**
 * Ajax handler for saving the agent progress status.
 *
 * @author snielson
 */
class Agent_Checklist_AJAX implements I_Register_Hooks_And_Actions {

    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
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
    
    public function __construct(\Psr\Log\LoggerInterface $logger, Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->logger = $logger;
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
    }
    
    public function register_hooks_and_actions() {
        add_action('wp_ajax_affwp_ltp_save_progress_item', array($this, 'save_progress_item'));
    }
    

    public function save_progress_item() {
        $this->logger->info("save_progress_item called");
        $agent_id = absint(filter_input(INPUT_POST, 'agent_id'));
        $progress_item_admin_id = absint(filter_input(INPUT_POST, 'progress_item_admin_id'));
        $completed_check = absint(filter_input(INPUT_POST, 'completed'));
        $completed = $completed_check !== 0;
        
        if (empty($agent_id) || empty($progress_item_admin_id)) {
            $this->logger->error("save_progress_item called with empty agent_id or empty progress_item_admin_id");
            return;
        }
        
        // verify permission settings
        // check to see if the current user is a 
        
        $agent_dal = $this->agent_dal;
        $current_user_agent_id = $agent_dal->get_current_user_agent_id();
        $agent_rank = $agent_dal->get_agent_rank($current_user_agent_id);
        
        $settings = $this->settings_dal;
        $partner_rank_id = $settings->get_partner_rank_id();
        $trainer_rank_id = $settings->get_trainer_rank_id();
        
        if (!empty($agent_rank)
            && ($agent_rank == $partner_rank_id
                || $agent_rank == $trainer_rank_id) )
        {
            $success = $agent_dal->update_agent_progress_item($agent_id, $progress_item_admin_id, $completed);
            if (!$success) {
                // TODO: stephen need to report back to the client that the item failed.
                $this->logger->error("failed to update agent progress item with agent_id $agent_id and progress_item_admin_id $progress_item_admin_id and completed $completed_check");
            }
        }
        else {
            $this->logger->error("save_progress_item called with incorrect permissions.  Current agent $current_user_agent_id has agent rank of $agent_rank");
        }
    }
}
