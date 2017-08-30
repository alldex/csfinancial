<?php
namespace AffiliateLTP\admin\policies;

use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\Template_Loader;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\State_DAL;
use AffiliateLTP\admin\Notices;
use AffiliateLTP\admin\policies\Policies_List_Table;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
/**
 * Description of class-policies
 *
 * @author snielson
 */
class Policies implements \AffiliateLTP\I_Register_Hooks_And_Actions{
    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;
    
    /**
     *
     * @var Template_Loader
     */
    private $template_loader;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     *
     * @var State_DAL
     */
    private $state_dal;
    
    /**
     * Handles the notice messages for the admin.
     * @var Notices
     */
    private $notices;
    
    public function __construct(Commission_DAL $commission_dal, Template_Loader $template_loader
            ,Agent_DAL $agent_dal, State_DAL $state_dal, Notices $notices) {
        $this->commission_dal = $commission_dal;
        $this->template_loader = $template_loader;
        $this->agent_dal = $agent_dal;
        $this->state_dal = $state_dal;
        $this->notices = $notices;
    }

    public function handle_admin_sub_menu_page() {
        
        // filter our post variables
        $action = filter_input(INPUT_GET, 'action');

        if (isset($action) && 'add_policy' == $action) {
            $this->handle_display_new_policy_screen();
        } else if (isset($action) && 'edit_policy' == $action) {
            $this->handle_display_edit_policy_screen();
        } else {
            $this->handle_display_list_policy_screen();
        }
    }
    
    public function handle_display_new_policy_screen() {
        // load up the template.. defaults to our templates/admin-commission-edit.php
        // if no one else has overridden it.
        $state_dal = $this->state_dal;
        $state_list = $state_dal->get_states();
        $template_path = $this->template_loader->get_template_part('admin-commission', 'new', false);
        include_once $template_path;
    }
    
    // TODO: stephen there is lots of problems here we will need to address...
    public function handle_display_edit_policy_screen() {
        // load up the template.. defaults to our templates/admin-commission-edit.php
        // if no one else has overridden it.

        $commission_request_id = filter_input(INPUT_GET, 'commission_request_id', FILTER_SANITIZE_NUMBER_INT);
        
        $policy = $this->commission_dal->get_commission_request($commission_request_id);
        
        $request = json_decode($commission_request->request);
        
//        $commission = $this->commission_dal->get_commission( absint( $referral_id ) );
//
//        $payout = $this->commission_dal->get_commission_payout( $commission->payout_id );
        

        $disabled = disabled((bool) $payout, true, false);
        $payout_link = add_query_arg(array(
            'page' => 'affiliate-wp-payouts',
            'action' => 'view_payout',
            'payout_id' => $payout ? $payout->ID : 0
                ), admin_url('admin.php'));

        $referralId = $commission->commission_id;
        $agentRate = $this->commission_dal->get_commission_agent_rate($referralId);
        $points = $this->commission_dal->get_commission_agent_points($referralId);
        $clientId = $this->commission_dal->get_commission_client_id($referralId);
        
        if (!empty($clientId)) {
            $instance = $this->sugar_crm_dal;
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

        $template_path = $this->template_loader->get_template_part('admin-commission', 'edit', false);
        include_once $template_path;
    }
    
    public function handle_display_list_policy_screen() {
        $orderby = filter_input(INPUT_GET, 'orderby', FILTER_SANITIZE_STRING,
                ['options' => ['default' => 'commission_request_id']] );
        $order = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING,
                ['options' => ['default' => 'DESC']] );
        
        $sort = ['orderby' => $orderby, 'order' => $order];
        $filter = [];
        
        $this->add_notices();
        $notices = $this->notices;
        $requests = $this->commission_dal->get_commission_requests($filter, $sort, 1000, 0);
        $table = new Policies_List_Table([], $this->agent_dal, $requests);
        $table->prepare_items();
        $file = $this->template_loader->get_template_part("admin", "policies-list", false);
        include $file;
    }
    
    private function add_notices() {
        $notices = $this->notices;
        $policy_added_notice = filter_input(INPUT_GET, 'affwp_ltp_notice', FILTER_SANITIZE_STRING,
                ['options' => ['default' => '']] );
        switch ($policy_added_notice) {
            case 'policy_deleted_failed': {
                $notices->add_success_notice($policy_added_notice, __("Policy failed to delete. Please try again or check the logs.", 'affiliate-ltp'));
            }
            break;
            case 'policy_deleted': {
                $notices->add_success_notice($policy_added_notice, __("Policy deleted.", 'affiliate-ltp'));
            }
            break;
            case 'policy_added': {
                $notices->add_success_notice($policy_added_notice, __("Policy successfully added!", 'affiliate-ltp'));
            }
            break;
            case 'policies_imported': {
                $notices->add_success_notice($policy_added_notice, __("Policies successfully imported!", 'affiliate-ltp'));
            }
            break;
        }
    }

    public function register_hooks_and_actions() {
    }
}