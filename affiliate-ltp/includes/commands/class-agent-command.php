<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\commands;

use \WP_CLI;
use \WP_CLI_Command;
use GFAPI;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\GravityForms\Gravity_Forms_Utilities;


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
     * Sets the phone number of an agent
     * ## OPTIONS
     *
     * <agent_id>
     * : The agent id of the agent to update
     *
     * <phone>
     * : The phone number to set for the agent
     * 
     * ## EXAMPLES
     *
     *     wp agent set-phone "801-555-5555"
     * 
     * 
     * @subcommand set-phone
     * @param type $args
     */
    public function set_phone( $args ) {
        list ($agent_id, $phone_number) = $args;
        
        if (empty($agent_id) || !is_numeric($agent_id)) {
            WP_CLI::error("Agent id invalid: $agent_id");
        }
        
        // TODO: stephen I should have a phone validator here...
        if (empty($phone_number)) {
            WP_CLI::error("Phone Number is invalid: $phone_number");
        }
        
        $agent_username = $this->agent_dal->get_agent_username($agent_id);
        
        if (empty($agent_username)) {
            WP_CLI::error("Agent id invalid: $agent_id");
        }
        
        $this->agent_dal->set_agent_phone($agent_id, $phone_number);
        
        WP_CLI::success("Agent '$agent_username' updated.  Phone set to {$phone_number}");
    }
    
    /**
     * Sets the phone number of an agent
     * ## OPTIONS
     *
     * <agent_id>
     * : The agent id of the agent to update
     *
     * <field>
     * : The form field to get the agent entry value for
     * 
     * ## EXAMPLES
     *
     *     wp agent get-entry-field-value 1 "Cell Phone"
     * 
     * 
     * @subcommand get-entry-field-value
     * @param type $args
     */
    public function get_entry_field_value( $args ) {
        list ($agent_id, $field) = $args;
        
        if (empty($agent_id) || !is_numeric($agent_id)) {
            WP_CLI::error("Agent id invalid: $agent_id");
        }
        
        // TODO: stephen I should have a phone validator here...
        if (empty($field)) {
            WP_CLI::error("Field is invalid: $field");
            return;
        }
        
        $agent_username = $this->agent_dal->get_agent_username($agent_id);
        
        if (empty($agent_username)) {
            WP_CLI::error("Agent id invalid: $agent_id");
            return;
        }
        
        $agent_registration_entry_id = $this->agent_dal->get_agent_registration_entry_id( $agent_id );
        $entry = GFAPI::get_entry( $agent_registration_entry_id );
        if (is_wp_error($entry)) {
            WP_CLI::error("Error in retrieving registration entry for agent $agent_id. Error: " . $entry->get_error_message());
            return;
        }
        
        $form = \GFAPI::get_form($entry['form_id']);
        if (is_wp_error($form)) {
            WP_CLI::error("Error in retrieving registration entry form for agent $agent_id. Error: " . $form->get_error_message());
            return;
        }
        
        $value = Gravity_Forms_Utilities::get_form_field_value($form, $entry, $field);
        echo $value . "\n";
        //WP_CLI::success($value);
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
