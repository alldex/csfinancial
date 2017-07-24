<?php
namespace AffiliateLTP\admin;

use AffiliateLTP\admin\Agent_Points_Summary_Request;
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
     * Returns the life insurance license status for the agent.
     * @param string $agent_id
     * @return \AffiliateLTP\admin\Life_License_Status
     */
    function get_life_license_status($agent_id);
    
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
     * @param int $agent_id
     * @return Agent_Tree_Node
     */
    function get_agent_downline_with_coleaderships( $agent_id );
    
    /**
     * Retrieves the downline but only includes your base-shop, not partners
     * @param int $agent_id
     */
    function get_agent_base_shop_with_coleaderships( $agent_id );
    
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
    
    /**
     * Retrieves the name of the passed in agent_id
     */
    function get_agent_name( $agent_id);
    
    /**
     * Retrieves the agent display name as set in their wordpress Display Name 
     * profile for the passed in agent.
     * @param int $agent_id
     */
    function get_agent_displayname( $agent_id );
    
    /**
     * Retrieves the email of the passed in agent_id
     */
    function get_agent_email( $agent_id);
    
    /**
     * Retrieves the username of the passed in agent_id
     */
    function get_agent_username( $agent_id);
    
    /**
     * Retrieves the user id of the passed in agent_id
     * @param int $agent_id
     */
    function get_agent_user_id( $agent_id );
    
    /**
     * Updates the agent phone number
     * @param int $agent_id
     * @param string $phone
     */
    function set_agent_phone( $agent_id, $phone );
    
    /**
     * Creates an agent for the passed in user id.
     * @param int $user_id
     * @param string $payment_email
     * @param string $status either 'active' or 'inactive'
     */
    function create_agent( $user_id, $payment_email, $status);
    
    /**
     * Retrieves the Gravity Forms Entry ID used to register this agent.
     * @param int $agent_id
     */
    function get_agent_registration_entry_id( $agent_id );
    
    
    function get_partner_agent_leaderboard_points_data($limit, $date_filter, $company_agent_id);
    
    function get_agent_leaderboard_points_data($limit, $date_filter, $company_agent_id);
    
    function get_agent_leaderboard_direct_recruits( $limit, $date_filter, $company_agent_id);
    
    function get_partner_agent_leaderboard_base_shop_recruits($limit, $date_filter, $company_agent_id);
    
    /**
     * Retrieves the agent's current registration date
     * @param int $agent_id
     */
    function get_agent_registration_date( $agent_id );
    
    /**
     * Retrieves all of the agent ids that currently have the specified rank
     * @param int $rank_id
     */
    function get_agent_ids_by_rank( $rank_id );
    
    /**
     * Searches the agents by their name(display name, username) and rank. Returns
     * array in format of ["id" => number, "display_name" => string];
     * @param string $name
     * @param string $rank
     */
    function search_agents_by_name_and_rank($name, $rank);
    
    /**
     * Retrieves the summary data using the request parameters provided.
     * @param Agent_Points_Summary_Request $request
     */
    function get_agent_point_summary_data(Agent_Points_Summary_Request $request);
    
    /**
     * Get the unique agent code that represents an agent and sub-agents can
     * sign up under.
     */
    function get_agent_code( $agent_id );
    
    /**
     * Retrieve the agent id by their agent code.
     * @param string $agent_code
     */
    function get_agent_by_code( $agent_code );
    
    /**
     * Return a list of agents that have the code snippet in their agent code
     * @param string $agent_code_snippet
     */
    function search_agents_by_code( $agent_code_snippet );
}
