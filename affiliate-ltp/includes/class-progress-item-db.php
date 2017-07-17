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
class Progress_Item_DB extends Affiliate_WP_DB implements I_Register_Hooks_And_Actions {
    
    const PRIMARY_KEY = 'progress_item_id';
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
			$this->table_name  = 'affiliate_wp_progress_items';
		} else {
			$this->table_name  = $wpdb->prefix . 'affiliate_wp_progress_items';
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
			'affiliate_id' => '%d',
			'progress_item_admin_id'     => '%d',
			'name'   => '%s',
                        'date_created'   => '%s',
                        'date_completed'   => '%s',
		);
	}
        
        public function get_column_defaults() {
            return [
                "date_created" => date("Y-m-d H:i:s")
            ];
        }
        
        public function get_by_admin_id( $affiliate_id, $progress_item_admin_id ) {
            $clauses = [
                "fields" => "*"
                ,"join" => ""
                ,"where" => "WHERE " . sprintf("`affiliate_id` = %d", absint($affiliate_id)) 
                                . " AND " . sprintf("`progress_item_admin_id` = %d", absint($progress_item_admin_id)) 
                ,"orderby" => "progress_item_admin_id"
                ,"order" => "DESC"
                ,"count" => false
            ];
            $args = ["number" => 1, "offset" => 0];
            
            $results = $this->get_results( $clauses, $args, function ($item) { return $this->hydrate_progress_item($item); } );
            if (!empty($results)) {
                return $results[0];
            }
            return null;
        }

        public function get_progress_items( $affiliate_id ) {
            $clauses = [
                "fields" => "*"
                ,"join" => ""
                ,"where" => "WHERE " . sprintf("`affiliate_id` = %d", absint($affiliate_id))
                ,"orderby" => "progress_item_admin_id"
                ,"order" => "DESC"
                ,"count" => false
            ];
            $args = ["number" => 1000, "offset" => 0];
            $results = $this->get_results( $clauses, $args, function ($item) { return $this->hydrate_progress_item($item); } );
            return $results;
        }
        
        public function hydrate_progress_item( $record ) {
            // do any formatting or other special work we need with the record.
            return [
                "progress_item_admin_id" => $record->progress_item_admin_id
                ,"progress_item_id" => $record->progress_item_id
                ,"affiliate_id" => $record->affiliate_id
                ,"name" => $record->name
                ,"date_created" => $record->date_created
                ,"date_completed" => $record->date_completed
            ];
        }
	
        public function add( $record ) {
            $add = $this->insert( $record, 'progress_item' );

            if ( $add ) {
                    do_action( 'affwp_affiliate_ltp_insert_progress_item', $add );
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

		$sql = "CREATE TABLE {$this->table_name} (
			" . self::PRIMARY_KEY . " bigint(20) NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) NOT NULL,
                        progress_item_admin_id bigint(20) NOT NULL,
			name varchar(255) DEFAULT NULL,
                        date_created datetime NOT NULL,
                        date_completed datetime DEFAULT NULL,
			PRIMARY KEY  (". self::PRIMARY_KEY ."),
			KEY progress_item_admin_id (progress_item_admin_id),
			KEY affiliate_id (affiliate_id)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

}
