<?php
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
    
    /**
     * Retrieves the id of the agent that is a co-leader of the passed in agent.
     * @param int $agent_id The unique id of the agent.
     * @return int|null the id of the co-leader agent or null if there is none
     */
    function get_agent_coleadership_agent_id( $agent_id );
    
    /**
     * Retrieves the co-leadership commission percentage that the co-leader agent
     * will receive for this particular agent.
     * @param int $agent_id The unique id of the agent.
     * @return float|null The percentage rate (0.00-1.00) of the commission the co-leader receives.
     */
    function get_agent_coleadership_agent_rate( $agent_id );
    
    /**
     * Tries to find the agent using the passed in agent email
     * @param string $agent_email
     */
    function get_agent_id_by_email( $agent_email );

    /**
     * Retrieves the ids of all of the agents that the current agent has a coleadership
     * agreement over.
     * @param int $coleadership_agent_id
     */
    function get_coleadership_sponsored_agent_ids( $coleadership_agent_id );
    
    /**
     * Retrieves the downline in a one 
     * @param type $agent_id
     */
    function get_agent_downline_with_coleaderships( $agent_id );
    
    /**
     * Returns the list of progress items for the agent including their 
     * date_completed.
     * @param int $agent_id The id of the agent to get the progress data for.
     */
    function get_agent_progress_items( $agent_id );
    
    /**
     * Updates the completion status of the progress item for the provided
     * agent id.  It uses the admin id of the progress item which is the global
     * progress item id stored in the plugin settings.
     * @param int $agent_id
     * @param int $progress_item_admin_id
     * @param boolean $completed
     */
    function update_agent_progress_item( $agent_id, $progress_item_admin_id, $completed );
    
    /**
     * Retrieves the currently logged in user's agent id if there is one.
     * @returns int
     */
    function get_current_user_agent_id();
}
