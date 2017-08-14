<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\policies;

use AffiliateLTP\admin\Commission_DAL;
use Psr\Log\LoggerInterface;
use AffiliateLTP\admin\Notices;

/**
 * Description of class-policy-delete
 *
 * @author snielson
 */
class Policy_Delete implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
     /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;
    
    /**
     *
     * @var Notices
     */
    private $notices;
    
    public function __construct(Commission_Dal $commission_dal, Notices $notices
            ,LoggerInterface $logger) {
        $this->commission_dal = $commission_dal;
        $this->logger = $logger;
        $this->notices = $notices;
    }
    public function register_hooks_and_actions() {
        add_action('affwp_ltp_delete_policy', array($this, 'process_delete_policy'));
    }

    public function process_delete_policy() {
        
        if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_referrals' ) ) {
		wp_die( __( 'You do not have permission to manage referrals', 'affiliate-wp' ), array( 'response' => 403 ) );
	}
        
        $nonce = filter_input(INPUT_GET, '_wpnonce');

	if ( ! wp_verify_nonce( $nonce, 'affwp_ltp_delete_commission_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ), array( 'response' => 403 ) );
	}
        
        $commission_request_id = absint(filter_input(INPUT_GET, 'commission_request_id'));
        
        $delete_success = $this->commission_dal->delete_commissions_for_request($commission_request_id);

	if ( $delete_success  ) {
                $this->notices->add_success_notice('policy_deleted'
                   , __("Policy deleted.", 'affiliate-ltp'));
		wp_safe_redirect( admin_url( 'admin.php?page=affiliate-ltp-policies' ) );
		exit;
	} else {
                $this->notices->add_error_notice('policy_deleted'
                       , __("Policy failed to delete. Please try again or check the logs.", 'affiliate-ltp'));
		wp_safe_redirect( admin_url( 'admin.php?page=affiliate-ltp-policies' ) );
		exit;
	}
    }
}
