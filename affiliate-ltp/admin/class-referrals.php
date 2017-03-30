<?php

namespace AffiliateLTP\admin;

require_once 'class-referrals-new-request-builder.php';
require_once 'class-commissions-table.php';
require_once 'class-commission-dal.php';
require_once 'class-commission-dal-affiliate-wp-adapter.php';
require_once 'class-agent-dal.php';
require_once 'class-agent-dal-affiliate-wp-adapter.php';
require_once 'class-commission-processor.php';
require_once 'class-settings-dal.php';
require_once 'class-settings-dal-affiliate-wp-adapter.php';

use AffiliateLTP\Plugin;
use AffiliateLTP\CommissionType;
use AffiliateLTP\admin\Commission_Payout_Export;
use \Affiliate_WP_Referral_Meta_DB;
use \AffWP_Referrals_Table;
use AffiliateLTP\admin\Referrals_New_Request_Builder;

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

    private $referralsTable;

    public function __construct(Affiliate_WP_Referral_Meta_DB $referralMetaDb) {
        remove_action('affwp_add_referral', 'affwp_process_add_referral');
        add_action('affwp_add_referral', array($this, 'processAddReferralRequest'));

        add_action('wp_ajax_affwp_ltp_search_clients', array($this, 'ajaxSearchClients'));
        add_action('wp_ajax_affwp_add_referral', array($this, 'processAddReferralRequest'));
        
        add_action('wp_ajax_affwp_search_commission', array($this, 'ajaxSearchCommission'));

        add_action('affwp_delete_referral', array($this, 'cleanupReferralMetadata'), 10, 1);

        add_action('affwp_generate_commission_payout', array($this, 'generateCommissionPayoutFile') );

        // TODO: stephen when dealing with rejecting / overridding commissions uncomment this piece.
        //add_filter( 'affwp_referral_row_actions', array($this, 'disableEditsForOverrideCommissions'), 10, 2);

        $this->referralMetaDb = $referralMetaDb;
        $this->referralsTable = new Commissions_Table($this->referralMetaDb);
        $this->commission_dal = new Commission_Dal_Affiliate_WP_Adapter($referralMetaDb);
        $this->agent_dal = new Agent_DAL_Affiliate_WP_Adapter();
        $this->settings_dal = new Settings_DAL_Affiliate_WP_Adapter();
    }

    public function generateCommissionPayoutFile( $data ) {
        require_once 'class-commission-payout-export.php';

        
        $export = new Commission_Payout_Export($this->referralMetaDb, $this->settings_dal);
        if (isset($data['is_life_commission'])) {
            $export->commissionType = CommissionType::TYPE_LIFE;
        }
        else {
            $export->commissionType = CommissionType::TYPE_NON_LIFE;
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
                ,"is_life_commission" => absint($commission_data->type) == CommissionType::TYPE_LIFE
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
            $copy_agent->name = $this->agent_dal->get_agent_email($copy_agent->id);
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

    public function cleanupReferralMetadata( $referralId ) {
        // delete all the meta information.
        $this->commission_dal->delete_commission_meta_all( $referralId );
    }
    
    private function debugLog($message) {
        // TODO: stephen need to put in the logger here.
        error_log($message);
    }
}
