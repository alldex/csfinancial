<?php

namespace AffiliateLTP\dashboard;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\Template_Loader;

class Agent_Promotions {
    /**
     *
     * @var number
     */
    private $company_agent_id;
    
    /**
     *
     * @var Template_Loader
     */
    private $template_loader;
    
    public function __construct(Settings_DAL $settings_dal, Template_Loader $template_loader) {
        // this hook gets triggered in the templates/dashboard-tab-events.php which is the way
        // the affiliate plugin works.
        add_action("affwp_affiliate_dashboard_promotions_show", array($this, "show_promotions"));
        $this->company_agent_id = $settings_dal->get_company_agent_id();
        $this->template_loader = $template_loader;
    }
    
    public function show_promotions( $agent_id ) {
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
    
    
    private function is_company_agent($agent_id) {
        return $agent_id == $this->company_agent_id;
    }
}