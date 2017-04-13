<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\commands;

use \WP_CLI;
use \WP_CLI_Command;
use AffiliateLTP\admin\Agent_DAL;


/**
 * Manage agents
 *
 * @author snielson
 */
class Agent_Command extends \WP_CLI_Command {
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    public function __construct(Agent_DAL $agent_dal) {
        $this->agent_dal = $agent_dal;
    }
    
     /**
     * Sets the rank of an agent
     * ## OPTIONS
     *
     * <agent_id>
     * : The agent id of the agent to update
     *
     * <rank_id>
     * : The rank id to set the agent to
     * 
     * ## EXAMPLES
     *
     *     wp agent set-rank 2 3
     * 
     * 
     * @subcommand set-rank
     * @param type $args
     */
    public function set_rank( $args ) {
        
        list ($agent_id, $rank_id) = $args;
        
        if (empty($agent_id) || !is_numeric($agent_id)) {
            WP_CLI::error("Agent id invalid: $agent_id");
        }
        
        if (empty($rank_id) || !is_numeric($rank_id)) {
            WP_CLI::error("Rank id invalid: $rank_id");
        }
        $agent_username = $this->agent_dal->get_agent_username($agent_id);
        $rank = get_rank_by_id($rank_id);
        
        if (empty($agent_username)) {
            WP_CLI::error("Agent id invalid: $agent_id");
        }
        
        if (empty($rank)) {
            WP_CLI::error("Rank id invalid: $rank_id");
        }
        
        // TODO: stephen this should be in the dal
        affwp_ranks_set_affiliate_rank( $agent_id, $rank_id );
        
        WP_CLI::success("Agent '$agent_username' updated.  Rank set to {$rank[0]['name']}");
        
    }
    
    /**
     * Creates an agent
     * ## OPTIONS
     *
     * <user_id>
     * : The user id of the agent to create
     *
     * [--status=<status>]
     * : The status to set the agent to.
     * ---
     * default: active
     * options:
     *   - active
     *   - inactive
     * ---
     * 
     * [--payment_email=<payment_email>]
     * : The payment email to use for the agent.
     * ---
     * default: user_email
     * ---
     * 
     * ## EXAMPLES
     *
     *     wp agent create 1 --status=active --payment_email=user@example.com
     * 
     * 
     * @param type $args
     */
    public function create( $args, $assoc_args ) {
        
        list($user_id) = $args;
        $data = get_userdata($user_id);
        if (empty($data)) {
            WP_CLI::error("User does not exist with id $user_id");
        }
       
        $status = isset($assoc_args['status']) ? $assoc_args['status'] : 'active';
        
        if (isset($assoc_args['status']) && $assoc_args['payment_email'] != 'user_email') {
            $payment_email = $assoc_args['payment_email'];
        }
        else {
           
            $payment_email = $data->user_email;
        }
        
        $agent_id = $this->agent_dal->create_agent($user_id, $payment_email, $status);
        
        if (!empty($agent_id)) {
            WP_CLI::success( "Agent created. ID: $agent_id");
        }
        else {
            WP_CLI::error("Failed to create agent");
        }
    }
}
