<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\policies;

use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\admin\Commission_Chargeback_Processor;
use Psr\Log\LoggerInterface;
use AffiliateLTP\admin\Notices;

/**
 * Handles the policy chargeback action.
 *
 * @author snielson
 */
class Policy_Chargeback implements \AffiliateLTP\I_Register_Hooks_And_Actions {

    /**
     *
     * @var Settings_DAL 
     */
    private $settings_dal;

    /**
     * Handles the chargebacks of commissions.
     * @var Commission_Chargeback_Processor
     */
    private $commission_chargeback_processor;
    
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Handles the adding and displaying of notices.
     * @var Notices
     */
    private $notices;
    
    public function __construct(Settings_DAL $settings_dal
            , Commission_Chargeback_Processor $commission_chargeback_processor
            , Notices $notices
            , LoggerInterface $logger) {
        $this->settings_dal = $settings_dal;
        $this->commission_chargeback_processor = $commission_chargeback_processor;
        $this->logger = $logger;
        $this->notices = $notices;
    }
    
    public function register_hooks_and_actions() {
        add_action("affwp_ltp_chargeback", array($this, 'process_chargeback'));
    }
    
    public function process_chargeback() {
       $commission_request_id = absint(filter_input(INPUT_GET, 'commission_request_id'));

       try {
           $company_agent_id = $this->settings_dal->get_company_agent_id();
           $chargeback_processor = $this->commission_chargeback_processor;
           $chargeback_processor->process_request($commission_request_id);
           $this->notices->add_success_notice('commission_chargeback_success'
                   , __("Policy chargeback successfully issued.", 'affiliate-ltp'));
           wp_safe_redirect( admin_url( 'admin.php?page=affiliate-ltp-policies' ) );
           exit;
       } catch (Exception $ex) {
           $message = $ex->getMessage() . "\nTrace: " . $ex->getTraceAsString(); 
           $this->logger->error($message);
       }
       $this->notices->add_error_notice('commission_chargeback_failed'
                   , __("Policy chargeback failed. Try again or check the logs.", 'affiliate-ltp'));
       wp_safe_redirect( admin_url( 'admin.php?page=affiliate-ltp-policies' ) );
       exit;
    }

}
