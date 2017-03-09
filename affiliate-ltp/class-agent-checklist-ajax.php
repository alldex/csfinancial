<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

/**
 * Description of class-agent-checklist-ajax
 *
 * @author snielson
 */
class Agent_Checklist_AJAX {

    public function __construct() {
        add_action('wp_ajax_affwp_ltp_save_progress_item', array($this, 'save_progress_item'));
    }

    public function save_progress_item() {
        $agent_id = absint(filter_input(INPUT_POST, 'agent_id'));
        $progress_item_admin_id = absint(filter_input(INPUT_POST, 'progress_item_admin_id'));
        $completed_check = absint(filter_input(INPUT_POST, 'completed'));
        $completed = $completed_check !== 0;
        
        if (empty($agent_id) || empty($progress_item_admin_id)) {
            error_log("save_progress_item called with empty agent_id or empty progress_item_admin_id");
            return;
        }
        
        // verify permission settings
        // check to see if the current user is a 
        
        $agent_dal = Plugin::instance()->get_agent_dal();
        $current_user_agent_id = $agent_dal->get_current_user_agent_id();
        $agent_rank = $agent_dal->get_agent_rank($current_user_agent_id);
        
        $settings = Plugin::instance()->get_settings_dal();
        $partner_rank_id = $settings->get_partner_rank_id();
        $trainer_rank_id = $settings->get_trainer_rank_id();
        
        if (!empty($agent_rank)
            && ($agent_rank == $partner_rank_id
                || $agent_rank == $trainer_rank_id) )
        {
            $success = $agent_dal->update_agent_progress_item($agent_id, $progress_item_admin_id, $completed);
            if (!$success) {
                // TODO: stephen need to report back to the client that the item failed.
                error_log("failed to update agent progress item with agent_id $agent_id and progress_item_admin_id $progress_item_admin_id and completed $completed_check");
            }
        }
        else {
            error_log("save_progress_item called with incorrect permissions.  Current agent $current_user_agent_id has agent rank of $agent_rank");
        }
    }
}
