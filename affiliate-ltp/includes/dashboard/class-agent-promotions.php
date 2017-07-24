<?php

namespace AffiliateLTP\dashboard;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\Template_Loader;
use AffiliateLTP\admin\Agent_DAL;
use Psr\Log\LoggerInterface;
use AffiliateLTP\Current_User;
use AffiliateLTP\dashboard\Agent_Filter_Widget;

/**
 * Responsible for the collection and display of the promotion data on the
 * promotions tab of the dashboard.
 */
class Agent_Promotions implements \AffiliateLTP\I_Register_Hooks_And_Actions {
  
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
     * Logger
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * The current user
     * @var Current_User
     */
    private $current_user;
    
    public function __construct(LoggerInterface $logger, 
            Settings_DAL $settings_dal, Template_Loader $template_loader
            ,Agent_DAL $agent_dal
            ,Current_User $current_user) {
        $this->logger = $logger;
        // this hook gets triggered in the templates/dashboard-tab-events.php which is the way
        // the affiliate plugin works.
        $this->company_agent_id = $settings_dal->get_company_agent_id();
        $this->template_loader = $template_loader;
        $this->agent_dal = $agent_dal;
        $this->current_user = $current_user;
    }
    
    public function register_hooks_and_actions() {
        add_action("affwp_affiliate_dashboard_promotions_show", array($this, "show_promotions"));
    }
    
    public function show_promotions( $current_agent_id ) {
        $this->logger->info("show_promotions(" . $current_agent_id . ")");
        $agent_id = $current_agent_id;
        $submitted_agent_id = filter_input(INPUT_GET, 'input_agent_id');
        if ($submitted_agent_id) {
            $agent_id = $submitted_agent_id;
        }
        $this->logger->info("show_promotions final agent id $agent_id");
        
        
        $agent_name = $this->agent_dal->get_agent_displayname($agent_id);
        $filter_widget = $this->setup_filter_widget($agent_id, $agent_name);
        $include_super_shop = $filter_widget->include_super_shop();
        $date_filter = $filter_widget->get_date_search();
        $downline = $this->get_agent_downline($agent_id, $include_super_shop);
        $ids = array_map(function ($node) { return $node->id; }, $downline->children);
        $nodes = [];
        
        if (!empty($ids)) {
            $this->logger->debug("agent first level downline ids: " . implode(",",$ids));
            $nodes = $this->get_direct_downline_nodes($date_filter, $agent_name, $include_super_shop, $ids);
        }
        // now add in the parent agent.
        $request = new \AffiliateLTP\admin\Agent_Points_Summary_Request();
        $request->date_filter = $date_filter;
        $request->base_shop_only = !$include_super_shop;
        $request->limit = 1;
        $request->personal_sales_only = false;
        $request->set_agent_ids([$agent_id]);
        $agent_point_data = $this->agent_dal->get_agent_point_summary_data($request);
        $points = !empty($agent_point_data) ? $agent_point_data[0]['points'] : 0;
        $nodes[] = $this->get_promotion_node_data($agent_id, $points, '');
        
        $sub_id = $agent_id;
        $include = $this->template_loader->get_template_part('dashboard-tab', 'promotions-chart', false);
        include_once $include;
    }
    
    private function get_agent_downline($agent_id, $include_super_shop) {
        if ($include_super_shop) {
            return $this->agent_dal->get_agent_downline_with_coleaderships($agent_id);
        }
        else {
            return $this->agent_dal->get_agent_base_shop_with_coleaderships($agent_id);
        }
    }
    
    private function get_direct_downline_nodes($date_filter, $parent_name, $include_partner_shop, array $agent_ids) {
        
        $request = new \AffiliateLTP\admin\Agent_Points_Summary_Request();
        $request->date_filter = $date_filter;
        $request->base_shop_only = !$include_partner_shop;
        $request->partners_only = false;
        $request->personal_sales_only = false;
        $request->limit = count($agent_ids);
        $request->set_agent_ids($agent_ids);
        
        $point_data = $this->agent_dal->get_agent_point_summary_data($request);
        $points_by_id = [];
        foreach ($point_data as $record) {
            $points_by_id[$record['agent_id']] = $record['points'];
        }
        $nodes = [];
        foreach ($agent_ids as $agent_id) {
            $points = 0;
            if (isset($points_by_id[$agent_id])) {
                $points = $points_by_id[$agent_id];
            }
            $nodes[] = $this->get_promotion_node_data($agent_id, $points, $parent_name);
        }
        return $nodes;
    }
    
    private function setup_filter_widget($agent_id, $agent_name) {
        $tab = filter_input(INPUT_GET, 'tab');
        $filter_from = filter_input(INPUT_GET, "filter_from");
        $filter_to = filter_input(INPUT_GET, "filter_to");
        
        $tab = $tab ? $tab : "promotions";
        $filter_from = $filter_from ? $filter_from : "";
        $filter_to = $filter_to ? $filter_to : "";
        
        
        
        $agent = ["id" => $agent_id, "name" => $agent_name];
        $dates = affwp_get_report_dates(); // TODO: stephen need to fix this function for myself
        $widget = new Agent_Filter_Widget($tab, $agent, $dates, $filter_from, $filter_to);
        
        
        if ($this->current_user->is_partner()) {
            $include_super_shop = filter_input(INPUT_GET, 'show_super_shop') == 1;
            $widget->toggle_super_shop();
            $widget->set_include_super_shop($include_super_shop);
        }
        return $widget;
        
    }
    
    private function get_promotion_node_data($agent_id, $points, $parent_name) {
        $avatarUrl = "<img class='avatar avatar-96 photo' src='" . AFFILIATE_LTP_PLUGIN_URL . 'assets/images/person.png' . "' />";
        return [
            "life_licensed" => $this->agent_dal->is_life_licensed($agent_id)
            ,"checklist_complete" => false
            ,"status" => "active"
            , "avatar" => $avatarUrl
            , "name" => $this->agent_dal->get_agent_displayname($agent_id)
            , "points" => $points
            , "parent_name" => $parent_name
            , "code" => $this->agent_dal->get_agent_code( $agent_id )
            , "rank" => $this->get_rank_name( $this->agent_dal->get_agent_rank( $agent_id ) )
        ];
    }
    
    private function get_rank_name( $rank_id ) {
        $ranks = get_rank_by_id( $rank_id );
        if (!empty($ranks)) {
            return array_shift($ranks)["name"];
        }
        return "";
    }
}