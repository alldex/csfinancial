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
