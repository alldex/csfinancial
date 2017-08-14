<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Description of class-notices
 *
 * @author snielson
 */
class Notices implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    
    private $notices;
    
    public function __construct() {
        $this->success_notices = [];
        $this->error_notices = [];
    }
    
    public function register_hooks_and_actions() {
        add_action( 'admin_notices', array( $this, 'display_notices' ) );
    }
    
    private function get_notices() {
        $notices = get_transient('affwp_ltp_notices');
        if (empty($notices)) {
            $notices = [];
        }
        return $notices;
    }
    
    private function clear_notices() {
        delete_transient('affwp_ltp_notices');
    }
    
    public function display_notices() {
        $notices = $this->get_notices();
        if (empty($notices)) {
            return;
        }
        
        foreach ($notices as $notice) {
        ?>
            <div class="notice notice-<?= $notice['type']; ?> is-dismissible">
                <p><?= $notice['message']; ?></p>
            </div>
            <?php
        }
        // as these are one time display notices we clear them after we've displayed them.
        $this->clear_notices();
    }

    private function add_notice($tag, $message, $type) {
        $notices = $this->get_notices();
        
        $notices[$tag] = ['type' => $type, 'message' => $message];
        set_transient("affwp_ltp_notices", $notices);
    }
    
    public function add_success_notice($tag, $message) {
        $this->add_notice($tag, $message, 'success');
    }
    
    public function add_error_notice($tag, $message) {
        $this->add_notice($tag, $message, 'info');
    }
    
    private function lookup_message($tag) {
        switch ($tag) {
            case 'policy_deleted_failed': {
                $message = __("Policy failed to delete. Please try again or check the logs.", 'affiliate-ltp');
            }
            break;
            case 'policy_deleted': {
                $message = __("Policy deleted.", 'affiliate-ltp');
            }
            break;
            case 'policy_added': {
                $message = __("Policy successfully added!", 'affiliate-ltp');
            }
            break;
        }
        $message = $tag;
        return $message;
    }

}
