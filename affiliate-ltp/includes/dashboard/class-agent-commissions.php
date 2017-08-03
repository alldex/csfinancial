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

/**
 * Displays the current agent's commissions they've earned in the agent dashboard.
 */
class Agent_Commissions implements \AffiliateLTP\I_Register_Hooks_And_Actions {
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
        add_action("affwp_affiliate_dashboard_commissions_show", array($this, "show_commissions"));
    }
    
     public function show_commissions( $current_agent_id ) {
        $this->logger->info("show_commissions(" . $current_agent_id . ")");
        
        $include = $this->template_loader->get_template_part('dashboard-tab', 'commissions-list', false);
        include_once $include;
    }

//put your code here
}
