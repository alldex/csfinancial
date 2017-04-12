<?php
namespace AffiliateLTP\admin;

/**
 * Handles the CRUD for everything to do with a commission.
 * @author snielson
 */
interface Commission_DAL {
    
    /**
     * Adds and persists the provided commission data to the system.
     * @param array $commission
     */
    function add_commission( $commission );
    
    /**
     * Retrieves the commission record for the given commission_id
     * @param int $commission_id
     */
    function get_commission( $commission_id );
    
    /**
     * Saves the meta information about the commission to the database
     * @param int $commission_id
     * @param string $key
     * @param mixed $value
     */
    function add_commission_meta( $commission_id, $key, $value );
    
    /**
     * Get the payout record if this commission has been paid out already to an
     * agent.
     * @param int $payout_id The id of the payout record
     */
    function get_commission_payout( $payout_id );
    
    /**
     * Removes the meta information for the key and value from the database
     * @param int $agent_id
     * @param string $key
     */
    function delete_commission_meta( $agent_id, $key );
    
    /**
     * Removes all of the meta information connected to a commission.
     * @param int $agent_id
     */
    function delete_commission_meta_all( $commission_id );
    
    /**
     * Retrieves the points an agent earned for this specific commission.
     * @param int $commission_id
     */
    function get_commission_agent_points( $commission_id );
    
    /**
     * Get the client that this commission is connected to.
     * @param int $commission_id
     */
    function get_commission_client_id( $commission_id );
    
    /**
     * Retrieves the agent rate that was used at the time the commission was created
     * (This can be different than the agent's current rate if the agent's rate
     * has been changed).
     * @param int $commission_id
     */
    function get_commission_agent_rate( $commission_id );
    
    /**
     * Connects a commission object that has been created to the passed in client
     * 
     * @param int $commission_id
     * @param array $client_data
     */
    function connect_commission_to_client( $commission_id, $client_data );
    
    /**
     * The contract number we are attempting to get repeat business for.
     * TODO: stephen fix this duplicate naming.
     * @param string $contract_number
     */
    function get_repeat_commission_data($contract_number);
    
    /**
     * The contract number we are attempting to get repeat business for.
     * @param string $contract_number
     */
    function get_repeat_commission_record($contract_number);
    
    /**
     * Adds a commission request record to the system.
     * @param array $commission_request
     */
    function add_commission_request( $commission_request );
    
    /**
     * Returns the commission request id connected with the commission id
     * @param int $commission_id
     */
    function get_commission_request_id_from_commission( $commission_id );
    
    /**
     * Returns the entire commission request including the deserialized 
     * request and agent trees using the commission_request_id
     * @param int $commission_request_id
     */
    function get_commission_request( $commission_request_id );
    
    /**
     * Deletes all of the commissions associated with the passed in commission
     * request
     * @param int $commission_request_id
     */
    function delete_commissions_for_request( $commission_request_id );
    
    /**
     * Returns all of the ids of the commissions that are connected to 
     * this commission request id.
     * @param int $commission_request_id
     */
    function get_commission_ids_for_request( $commission_request_id );
}
