<?php

namespace AffiliateLTP\admin;

use AffiliateLTP\Plugin;
use AffiliateLTP\Commission_Type;
use AffiliateLTP\admin\Commission_Payout_Export;
use AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB;
use \AffWP_Referrals_Table;
use AffiliateLTP\admin\Referrals_New_Request_Builder;
use AffiliateLTP\admin\Commissions_Table;
use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\admin\Commission_Dal_Affiliate_WP_Adapter;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Agent_DAL_Affiliate_WP_Adapter;
use AffiliateLTP\admin\Commission_Processor;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\admin\Settings_DAL_Affiliate_WP_Adapter;

/**
 * Description of class-referrals
 *
 * @author snielson
 */
class Referrals {

    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;
    
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
     *
     * @var Affiliate_WP_Referral_Meta_DB
     */
    private $referralMetaDb;

    private $referrals_table;

    public function __construct(Affiliate_WP_Referral_Meta_DB $referralMetaDb) {
        remove_action('affwp_add_referral', 'affwp_process_add_referral');
        add_action('affwp_add_referral', array($this, 'processAddReferralRequest'));

        add_action('wp_ajax_affwp_ltp_search_clients', array($this, 'ajaxSearchClients'));
        add_action('wp_ajax_affwp_add_referral', array($this, 'processAddReferralRequest'));
        
        add_action('wp_ajax_affwp_search_commission', array($this, 'ajaxSearchCommission'));
        
        add_action('affwp_process_chargeback_commission', array($this, 'process_chargeback_commission'));

        add_action('affwp_delete_referral', array($this, 'cleanup_referral_metadata'), 10, 1);

        add_action('affwp_generate_commission_payout', array($this, 'generateCommissionPayoutFile') );
        
        add_action('affwp_process_delete_commission', array($this, 'process_delete_commission'));

        // TODO: stephen when dealing with rejecting / overridding commissions uncomment this piece.
        //add_filter( 'affwp_referral_row_actions', array($this, 'disableEditsForOverrideCommissions'), 10, 2);

        $this->referralMetaDb = $referralMetaDb;
        // see the commissions table for the hooks that alter the affiliate_referrals_list table.
        
        $this->commission_dal = new Commission_Dal_Affiliate_WP_Adapter($referralMetaDb);
        $this->referrals_table = new Commissions_Table($this->commission_dal);
        $this->agent_dal = new Agent_DAL_Affiliate_WP_Adapter();
        $this->settings_dal = new Settings_DAL_Affiliate_WP_Adapter();
    }

    public function generateCommissionPayoutFile( $data ) {
        $export = new Commission_Payout_Export($this->referralMetaDb, $this->settings_dal);
        if (isset($data['is_life_commission'])) {
            $export->commissionType = Commission_Type::TYPE_LIFE;
        }
        else {
            $export->commissionType = Commission_Type::TYPE_NON_LIFE;
        }
        
        $export->date = array(
            'start' => $data['from'],
            'end'   => $data['to'] . ' 23:59:59'
        );
        $export->export();
    }

    public function disableEditsForOverrideCommissions($actions, $referral) {
        if (isset($referral) && $referral->custom == 'indirect') {
            $actions = array();
        }
        return $actions;
    }

    public function handleDisplayListReferralsScreen() {
        $referrals_table = new AffWP_Referrals_Table();
        $referrals_table->prepare_items();

        $minimum_payout_amount = $this->settings_dal->get_minimum_payout_amount();
        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 'list', false);

        include_once $templatePath;
    }

    /**
     * Handles the display of the different admin referral pages.
     */
    public function handleAdminSubMenuPage() {
        // filter our post variables
        $action = filter_input(INPUT_GET, 'action');

        if (isset($action) && 'add_referral' == $action) {
            $this->handleDisplayNewReferralScreen();
        } else if (isset($action) && 'edit_referral' == $action) {
            $this->handleDisplayEditReferralScreen();
        } else {
            $this->handleDisplayListReferralsScreen();
        }
    }
    
    public function ajaxSearchCommission() {
        
        $this->debugLog("searching commission");
        
        if (!is_admin()) {
            return false;
        }
        
        if (!current_user_can('manage_referrals')) {
            wp_die(__('You do not have permission to manage referrals', 'affiliate-wp'), __('Error', 'affiliate-wp'), array('response' => 403));
        }
        
        $contract_number = sanitize_text_field($_REQUEST['contract_number']);
        // TODO: stephen validate the contract_number
        
        $this->debugLog("contract submitted is " . $contract_number);
        
        // search through all commissions where the reference = contract_number
        //   and where the new_business = 'N'
        //   and where the type of commission is personal rather than override
        //   
        //   order by referral_id so we can make sure we can get the right data
        $commission_data = $this->commission_dal->get_repeat_commission_data($contract_number);
        if (!empty($commission_data)) {
            $agents = $this->populate_agent_array($commission_data->agents);
            $formatted_commission = [
                "writing_agent" => array_shift($agents)
                ,"agents" => $agents
                ,"contract_number" => $contract_number
                ,"is_life_commission" => absint($commission_data->type) == Commission_Type::TYPE_LIFE
                ,"split_commission" => count($agents) > 0
                ,'commission_request_id' => $commission_data->commission_request_id
            ];
            $result = array("data" => $formatted_commission);
        }
        else {
            http_response_code(404);
            $result = array("message" => __("Repeat business not found for contract number", "affiliate-ltp"));
        }
        
        echo json_encode($result);
        exit;
    }
    
    private function populate_agent_array($agents) {
        $result_agents = [];
        foreach ($agents as $agent) {
            $copy_agent = clone $agent;
            // TODO: stephen need to add in name
            // TODO: stephen I don't like how the abstraction layer is broken here with agent_id/user_id switching.
            $copy_agent->name = $this->agent_dal->get_agent_email($copy_agent->id);
            $copy_agent->agent_id = $copy_agent->id;
            $copy_agent->id = $this->agent_dal->get_agent_user_id($copy_agent->id);
            $result_agents[] = $copy_agent;
        }
        return $result_agents;
    }
    
    /**
     * Handle ajax requests for searching through a list of clients.
     */
    public function ajaxSearchClients() {
        // TODO: stephen would it be better to just make searchAccounts conform
        // to what we return to the client instead of what it's returning now?
        $instance = Plugin::instance()->getSugarCRM();

        // TODO: stephen have this use the filter_input functions.
        $searchQuery = htmlentities2(trim($_REQUEST['term']));

        $results = $instance->searchAccounts($searchQuery);
        
        $jsonResults = array();
        foreach ($results as $id => $record) {
            $record['label'] = $record['contract_number'] . " - " . $record['name'];
            $record['value'] = $record['contract_number'];
            $record['client_id'] = $id;
            $jsonResults[] = $record;
        }


        wp_die(json_encode($jsonResults)); // this is required to terminate immediately and return a proper response
    }

    public function handleDisplayEditReferralScreen() {
        // load up the template.. defaults to our templates/admin-referral-edit.php
        // if no one else has overridden it.

        $referral_id = filter_input(INPUT_GET, 'referral_id');
        $referral = $this->commission_dal->get_commission( absint( $referral_id ) );

        $payout = $this->commission_dal->get_commission_payout( $referral->payout_id );

        $disabled = disabled((bool) $payout, true, false);
        $payout_link = add_query_arg(array(
            'page' => 'affiliate-wp-payouts',
            'action' => 'view_payout',
            'payout_id' => $payout ? $payout->ID : 0
                ), admin_url('admin.php'));

        $referralId = $referral->referral_id;
        $agentRate = $this->commission_dal->get_commission_agent_rate($referralId);
        $points = $this->commission_dal->get_commission_agent_points($referralId);
        $clientId = $this->commission_dal->get_commission_client_id($referralId);
        
        if (!empty($clientId)) {
            $instance = Plugin::instance()->getSugarCRM();
            $client = $instance->getAccountById($clientId);
        } else {
            $client = array(
                "contract_number" => ""
                , "name" => ""
                , "street_address" => ""
                , "city_address" => ""
                , "zipcode" => ""
                , "phone" => ""
                , "email" => ""
            );
        }

        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 'edit', false);

        include_once $templatePath;
    }

    public function handleDisplayNewReferralScreen() {
        // load up the template.. defaults to our templates/admin-referral-edit.php
        // if no one else has overridden it.
        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 'new', true);

        include_once $templatePath;
    }
    
    public function process_delete_commission() {
        
        if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_referrals' ) ) {
		wp_die( __( 'You do not have permission to manage referrals', 'affiliate-wp' ), array( 'response' => 403 ) );
	}
        
        $nonce = filter_input(INPUT_GET, '_wpnonce');

	if ( ! wp_verify_nonce( $nonce, 'affwp_delete_commission_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ), array( 'response' => 403 ) );
	}
        
        $commission_request_id = absint(filter_input(INPUT_GET, 'commission_request_id'));
        
        $delete_success = $this->commission_dal->delete_commissions_for_request($commission_request_id);

	if ( $delete_success  ) {
		wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_deleted' ) );
		exit;
	} else {
		wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_delete_failed' ) );
		exit;
	}
    }
    
    public function process_chargeback_commission() {
        $commission_request_id = absint(filter_input(INPUT_GET, 'commission_request_id'));
        
        $commission_request_record = $this->commission_dal->get_commission_request($commission_request_id);
        
        // TODO: stephen how do I determine if this is already a charge back or not?
        // perhaps by the type of request???
        $request_json = $commission_request_record['request'];
        try {
            $request_hydrated = json_decode($request_json, true);
            if ($request_hydrated['amount'] > 0) {
                $request_hydrated['amount'] = $request_hydrated['amount'] * -1;
            }
            // we need to map agent_ids -> user_ids as that's what request builder expects
            // unfortunately the affiliate-wp code sends user ids instead of agent_ids
            $request_hydrated['agents'] = $this->fill_agent_user_ids($request_hydrated['agents']);
            $request_hydrated['split_commission'] = count($request_hydrated['agents']) > 0;
            $request = Referrals_New_Request_Builder::build($request_hydrated);
            $commissionProcessor = new Commission_Processor($this->commission_dal, 
                    $this->agent_dal, $this->settings_dal);
            $commissionProcessor->process_commission_request($request);
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=commission_chargeback_success&affwp_message=Chargeback%20Succeeded' ) );
            exit;
        } catch (Exception $ex) {
             $message = $ex->getMessage() . "\nTrace: " . $ex->getTraceAsString(); 
            error_log($message);
        }
        
        wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=commission_chargeback_failed&affwp_message=Chargeback%20Failed' ) );
        exit;
    }

    public function processAddReferralRequest() {
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
        
//        var_dump($data);
        
        $response = ["type" => "error", "message" => __("Server Error occurred", 'affiliate-ltp')];
//        $response = ["type" => "success", "redirect" => admin_url('admin.php?page=affiliate-wp-referrals&affwp_notice=referral_added')];
        
        try {
//            error_log(var_export($requestData, true));
            $request = Referrals_New_Request_Builder::build($requestData);
//            error_log(var_export($request, true));
            
            $commissionProcessor = new Commission_Processor($this->commission_dal, 
                    $this->agent_dal, $this->settings_dal);
            $commissionProcessor->process_commission_request($request);
            $response['type'] = 'success';
            $response['message'] = __("Commission Added", 'affiliate-ltp');
            $response['redirect'] = admin_url('admin.php?page=affiliate-wp-referrals&affwp_notice=referral_added');
            // add validation exceptions here...
        } catch (Commission_Validation_Exception $ex) {
            $response['errors'] = $ex->get_validation_errors();
            $response['type'] = 'validation';
            error_log($response['message']);
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . "\nTrace: " . $ex->getTraceAsString(); 
            error_log($message);
            $response['message'] = __("A server error occurred and we could not process the request.  Check the server logs for more details", 'affiliate-ltp');
        }
        echo json_encode($response);
        exit;
    }

    public function cleanup_referral_metadata( $referralId ) {
        // delete all the meta information.
        $this->commission_dal->delete_commission_meta_all( $referralId );
    }
    
    private function debugLog($message) {
        // TODO: stephen need to put in the logger here.
        error_log($message);
    }
    
    private function fill_agent_user_ids($agents) {
        if (empty($agents)) {
            return [];
        }
        
        $new_agents = [];
        foreach ($agents as $agent) {
            $agent_id = $agent['id'];
            $agent['id'] = $this->agent_dal->get_agent_user_id($agent_id);
            $new_agents[] = $agent;
        }
        return $new_agents;
    }
}
