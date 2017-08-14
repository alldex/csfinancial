<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\policies;

use Psr\Log\LoggerInterface;
use AffiliateLTP\admin\Commission_Processor;
use AffiliateLTP\admin\Referrals_New_Request_Builder;
use AffiliateLTP\admin\Commission_Validation_Exception;

/**
 * Description of class-policy-add-ajax
 *
 * @author snielson
 */
class Policy_Add_AJAX implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    
     /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
     /**
     * The service that actually creates and implements the commissions
     * @var Commission_Processor
     */
    private $commission_processor;
    
    public function __construct(Commission_Processor $processor, LoggerInterface $logger) {
        $this->logger = $logger;
        $this->commission_processor = $processor;
    }
    public function register_hooks_and_actions() {
         add_action('wp_ajax_affwp_ltp_add_policy', array($this, 'process_add_commission_request'));
    }
    
    public function process_add_commission_request() {
        $this->logger->info("inside process_add_commission_request");
        
        // since the data is received using application/json we read it from
        // the request body.
        if (!is_admin()) {
            return false;
        }
        
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING);
        // Retrieve JSON payload as hash arrays (I like that better than stdObj
        $requestData = json_decode(file_get_contents('php://input'), true);

        if (!current_user_can('manage_referrals')) {
            wp_die(__('You do not have permission to manage referrals', 'affiliate-wp'), __('Error', 'affiliate-wp'), array('response' => 403));
        }

        if (!wp_verify_nonce($requestData['affwp_add_referral_nonce'], 'affwp_add_referral_nonce')) {
            wp_die(__('Security check failed', 'affiliate-wp'), array('response' => 403));
        }
        
        $response = ["type" => "error", "message" => __("Server Error occurred", 'affiliate-ltp')];
//        $response = ["type" => "success", "redirect" => admin_url('admin.php?page=affiliate-wp-referrals&affwp_notice=referral_added')];
        
        try {
//            error_log(var_export($requestData, true));
            $request = Referrals_New_Request_Builder::build($requestData);
//            error_log(var_export($request, true));
            
            $commissionProcessor = $this->commission_processor;
            $commissionProcessor->process_commission_request($request);
            $response['type'] = 'success';
            $response['message'] = __("Policy Added", 'affiliate-ltp');
            $response['redirect'] = admin_url('admin.php?page=affiliate-ltp-policies&affwp_ltp_notice=policy_added');
            // add validation exceptions here...
        } catch (Commission_Validation_Exception $ex) {
            $response['errors'] = $ex->get_validation_errors();
            $response['type'] = 'validation';
            $this->logger->warning($response['message'] . "\nTrace: " . $ex->getTraceAsString());
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . "\nTrace: " . $ex->getTraceAsString(); 
            $this->logger->error($message);
            $response['message'] = __("A server error occurred and we could not process the request.  Check the server logs for more details", 'affiliate-ltp');
        }
        echo json_encode($response);
        exit;
    }

}
