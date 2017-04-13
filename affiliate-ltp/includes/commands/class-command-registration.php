<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
namespace AffiliateLTP\commands;

use AffiliateLTP\admin\Agent_DAL;
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
   
    public function __construct(Agent_DAL $agent_dal) {
        $this->agent_dal = $agent_dal;
    }
    
    public function register() {
        $agent_commands = new Agent_Command($this->agent_dal);
        WP_CLI::add_command('agent', $agent_commands);
    }
}
