<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;
use Affiliate_WP_DB;

/**
 * Core class used to implement the progress item table.
 */
class Commission_Request_DB extends Affiliate_WP_DB implements I_Register_Hooks_And_Actions {
    
    const PRIMARY_KEY = 'commission_request_id';
    const VERSION = '1.0';

	/**
	 * Sets up the Affiliate Meta DB class.
	 *
	 * @access public
	 * @since  1.6
	*/
	public function __construct() {
		global $wpdb;

		if( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
			// Allows a single affiliate meta table for the whole network
			$this->table_name  = 'affiliate_wp_commission_requests';
		} else {
			$this->table_name  = $wpdb->prefix . 'affiliate_wp_commission_requests';
		}
		$this->primary_key = self::PRIMARY_KEY;
		$this->version     = self::VERSION;
	}
        
        public function register_hooks_and_actions() {}

	/**
	 * Retrieves the table columns and data types.
	 *
	 * @access public
	 * @since  1.7.18
	 *
	 * @return array List of affiliate meta table columns and their respective types.
	*/
	public function get_columns() {
            /** 
             * affiliate_id bigint(20) NOT NULL,
                        progress_item_admin_id bigint(20) NOT NULL,
			name varchar(255) DEFAULT NULL,
                        date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        date_completed TIMESTAMP DEFAULT NULL,
             */
		return array(
			self::PRIMARY_KEY      => '%d',
                        'contract_number' => '%s',
			'creator_user_id' => '%d',
			'writing_agent_id'     => '%d',
                        'amount'     => '%d',
                        'points'     => '%d',
                        'date_created'     => '%s',
			'request_type'   => '%s',
                        'new_business' => '%s',
                        'request'   => '%s',
                        'agent_tree'   => '%s',
		);
	}
        
        public function get_column_defaults() {
            return [
                "date_created" => date("Y-m-d H:i:s")
                ,"creator_user_id" => get_current_user_id()
                ,"new_business" => "Y"
            ];
        }
        
        public function get_commission_requests( $contract_number ) {
            $clauses = [
                "fields" => "*"
                ,"join" => ""
                ,"where" => "WHERE " . sprintf("`contract_number` = %s", $contract_number)
                ,"orderby" => "date_created"
                ,"order" => "DESC"
                ,"count" => false
            ];
            $args = ["number" => 1000, "offset" => 0];
            $results = $this->get_results( $clauses, $args, function ($item) { return $this->hydrate_commission_request($item); } );
            return $results;
        }
        
        public function get_new_commission_request( $contract_number ) {
            $clauses = [
                "fields" => "*"
                ,"join" => ""
                ,"where" => "WHERE " . sprintf("`contract_number` = '%s'", $contract_number) 
                                . " AND new_business = 'Y'"
                ,"orderby" => "date_created"
                ,"order" => "DESC"
                ,"count" => false
            ];
            // there should only be one.
            $args = ["number" => 1, "offset" => 0];
            $results = $this->get_results( $clauses, $args, function ($item) { return $this->hydrate_commission_request($item); } );
            if (!empty($results)) {
                return $results[0];
            }
            return null;
        }
        
        public function get_commission_request( $commission_request_id ) {
            $clauses = [
                "fields" => "*"
                ,"join" => ""
                ,"where" => "WHERE " . sprintf("`commission_request_id` = '%d'", $commission_request_id)
                ,"orderby" => "date_created"
                ,"order" => "DESC"
                ,"count" => false
            ];
            // there should only be one.
            $args = ["number" => 1, "offset" => 0];
            $results = $this->get_results( $clauses, $args, function ($item) { return $this->hydrate_commission_request($item); } );
            if (!empty($results)) {
                return $results[0];
            }
            return null;
        }
        
        
        public function hydrate_commission_request( $record ) {
            // do any formatting or other special work we need with the record.
           
            return [
                "commission_request_id" => $record->commission_request_id
                ,"contract_number" => $record->contract_number
                ,"creator_user_id" => $record->creator_user_id
                ,"writing_agent_id" => $record->writing_agent_id
                ,"amount" => $record->amount
                ,"points" => $record->points
                ,"request_type" => $record->request_type
                ,"new_business" => $record->new_business
                ,"request" => $record->request
                ,"agent_tree" => $record->agent_tree
                ,"date_created" => $record->date_created
            ];
        }
	
        public function add( $record ) {
            $add = $this->insert( $record, 'commission_request' );

            if ( $add ) {
                    do_action( 'affwp_affiliate_ltp_insert_commission_request', $add );
                    return $add;
            }

            return false;
        }
        
	/**
	 * Creates the table.
	 *
	 * @access public
	 * @since  1.6
	 *
	 * @see dbDelta()
	*/
	public function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

                 /**
             * 'contract_number' => '%s',
			'creator_user_id' => '%d',
			'writing_agent_id'     => '%d',
                        'amount'     => '%d',
                        'points'     => '%d',
                        'date_created'     => '%s',
			'request_type'   => '%s',
                        'new_business' => '%s',
                        'request'   => '%s',
                        'agent_tree'   => '%s',
             */
		$sql = "CREATE TABLE {$this->table_name} (
			" . self::PRIMARY_KEY . " bigint(20) NOT NULL AUTO_INCREMENT,
			contract_number varchar(255) NOT NULL,
                        creator_user_id bigint(20) NOT NULL,
                        writing_agent_id bigint(20) NOT NULL,
                        amount DOUBLE NOT NULL DEFAULT 0,
			points DOUBLE NOT NULL DEFAULT 0,
                        date_created datetime NOT NULL,
                        request_type varchar(100) NOT NULL,
                        new_business ENUM('Y','N') NOT NULL DEFAULT 'Y',
                        request TEXT,
                        agent_tree TEXT,
			PRIMARY KEY  (". self::PRIMARY_KEY ."),
			KEY idx_contract_number (contract_number),
			KEY idx_contract_new (contract_number,new_business)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

}
