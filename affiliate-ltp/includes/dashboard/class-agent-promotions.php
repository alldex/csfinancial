<?php

namespace AffiliateLTP\dashboard;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\Template_Loader;
use AffiliateLTP\admin\Agent_DAL;

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
    
    public function __construct(Settings_DAL $settings_dal, Template_Loader $template_loader
            ,Agent_DAL $agent_dal) {
        // this hook gets triggered in the templates/dashboard-tab-events.php which is the way
        // the affiliate plugin works.
        $this->company_agent_id = $settings_dal->get_company_agent_id();
        $this->template_loader = $template_loader;
    }
    
    public function register_hooks_and_actions() {
        add_action("affwp_affiliate_dashboard_promotions_show", array($this, "show_promotions"));
    }
    
    public function show_promotions( $agent_id ) {
        
        $downline = $this->agent_dal->get_agent_downline_with_coleaderships($agent_id);
        
        $ids = array_map(function ($node) { return $node->id; }, $downline->children);
        if (empty($ids)) {
            $len = count($ids);
            $date_filter = ['start' => '', 'end' => ''];
//            $this->agent_dal->get_agent_point_summary_data($len, $date_filter, )
        }
        
        $avatarUrl = "<img class='avatar avatar-96 photo' src='" . AFFILIATE_LTP_PLUGIN_URL . 'assets/images/person.png' . "' />";
        $nodes = [
            [
                'life_licensed' => false
                ,'checklist_complete' => false
                ,'status' => 'active'
                ,'avatar' => $avatarUrl
                ,'name' => 'Scott Webb'
                ,'points' => 5000
            ]
            ,[
                'life_licensed' => false
                ,'checklist_complete' => false
                ,'status' => 'active'
                ,'avatar' => $avatarUrl
                ,'name' => 'Rod Tietjen'
                ,'parent_name' => 'Scott Webb'
                ,'points' => 2500
            ]
        ];
        $sub_id = $agent_id;
        $include = $this->template_loader->get_template_part('dashboard-tab', 'promotions-chart', false);
        include_once $include;
    }
}