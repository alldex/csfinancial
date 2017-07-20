<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
namespace AffiliateLTP\commands;

use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\Sugar_CRM_DAL;
use AffiliateLTP\admin\Commission_DAL;
use \WP_CLI;

/**
 * Handles the creation and management of wordpress cli commands.
 *
 * @author snielson
 */
class Command_Registration {
    
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
     * Sugar_CRM_DAL
     */
    private $sugar_crm_dal;
    
    /**
     * Commissions_DAL
     */
    private $commission_dal;
   
    public function __construct(Settings_DAL $settings_dal, Agent_DAL $agent_dal
            ,Sugar_CRM_DAL $sugar_crm_dal
            ,Commission_DAL $commission_dal) {
        $this->settings_dal = $settings_dal;
        $this->agent_dal = $agent_dal;
        $this->sugar_crm_dal = $sugar_crm_dal;
        $this->commission_dal = $commission_dal;
    }
    
    public function register() {
        $agent_commands = new Agent_Command($this->agent_dal);
        $sugarcrm_commands = new SugarCRM_Command($this->sugar_crm_dal, $this->commission_dal);
        WP_CLI::add_command('agent', $agent_commands);
        WP_CLI::add_command('sugarcrm', $sugarcrm_commands);
        WP_CLI::add_command('affwp-ltp-gf', new Affiliate_LTP_GravityForms_Command($this->settings_dal, $this->agent_dal));
    }
}
