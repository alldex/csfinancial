<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Runs the affiliate ltp actions that are in the post or get request specific
 * to ltp.  Actions are run on init.
 *
 * @author snielson
 */
class Actions_Processor implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    public function register_hooks_and_actions() {
        add_action( 'init', array($this, 'do_actions' ) );
    }
    
    public function do_actions() {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        $action = !empty($action) ? $action : filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
        if (empty($action)) {
            return;
        }
        
        do_action( "affwp_ltp_{$action}");
    }

}
