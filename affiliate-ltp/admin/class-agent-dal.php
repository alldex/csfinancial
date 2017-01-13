<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AffiliateLTP\admin;

/**
 *
 * @author snielson
 */
interface Agent_DAL {
    
    /**
     * Retrieves all of the upline agent ids for the passed in agent id
     * @param int $agent_id
     */
    function get_agent_upline( $agent_id );
    
    /**
     * Return the status of the agent.
     * @param int $agent_id
     * @return string|false Agent status, false otherwise.
     */
    function get_agent_status( $agent_id );
    
    /**
     * Return the rank id of the agent. Null if the agent has no rank.
     * @param int $agent_id
     * @return int|null The rank id of the agent
     */
    function get_agent_rank( $agent_id );
    
    /**
     * Retrieve the parent agent id of this agent if there is one
     * @param int $agent_id The id of the agent.
     * @return int|null The id of the parent agent if there is one, false otherwise.
     */
    function get_parent_agent_id( $agent_id );
    
    /**
     * Checks if the passed in agent has a status of active.
     * @param int $agent_id
     * @return boolean true if the agent is active, false otherwise
     */
    function is_active( $agent_id );
    
    /**
     * Checks if the passed in agent has an active license to sell life insurance
     * or not.
     * @param int $agent_id
     * @return boolean true if the agent is life licensed, false otherwise.
     */
    function is_life_licensed( $agent_id );
    
    /**
     * Filters the agents list to only include agents whose status is the passed
     * in status.
     * @param array $agents
     */
    function filter_agents_by_status( $agents, $status = 'active' );
    
    /**
     * Filters the agents list to only include agents who are licensed to sell
     * life insurance.
     * @param array $agents
     */
    function filter_agents_by_licensed_life_agents( $agents );
    
    /**
     * retrieves the agent commission rate for the passed in agent id
     */
    function get_agent_commission_rate( $agent_id );
}
