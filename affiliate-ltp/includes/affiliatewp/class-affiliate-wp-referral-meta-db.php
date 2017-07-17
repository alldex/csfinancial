<?php
namespace AffiliateLTP\AffiliateWP;

if (!class_exists("\Affiliate_WP_DB")) {
    require_once dirname(AFFILIATE_LTP_PLUGIN_DIR) . "includes/abstracts/class-db.php";
}

use Affiliate_WP_DB;

/**
 * Core class used to implement referral meta.
 * Implemented here until Affiliate_WP plugin implements this piece.
 *
 * @since 1.6
 *
 * @see Affiliate_WP_DB
 */
class Affiliate_WP_Referral_Meta_DB extends Affiliate_WP_DB implements \AffiliateLTP\I_Register_Hooks_And_Actions {

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
			$this->table_name  = 'affiliate_wp_referralmeta';
		} else {
			$this->table_name  = $wpdb->prefix . 'affiliate_wp_referralmeta';
		}
		$this->primary_key = 'meta_id';
		$this->version     = '1.0';
	}
        
        public function register_hooks_and_actions() {
            add_action( 'plugins_loaded', array( $this, 'register_table' ), 11 );
        }

	/**
	 * Retrieves the table columns and data types.
	 *
	 * @access public
	 * @since  1.7.18
	 *
	 * @return array List of affiliate meta table columns and their respective types.
	*/
	public function get_columns() {
		return array(
			'meta_id'      => '%d',
			'referral_id' => '%d',
			'meta_key'     => '%s',
			'meta_value'   => '%s',
		);
	}

	/**
	 * Registers the table with $wpdb so the metadata api can find it.
	 *
	 * @access public
	 * @since  1.6
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function register_table() {
		global $wpdb;
		$wpdb->referralmeta = $this->table_name;
	}

	/**
	 * Retrieves an referral meta field for a referral.
	 *
	 * @access public
	 * @since  1.6
	 *
	 * @param int    $referral_id Optional. Referral ID. Default 0.
	 * @param string $meta_key     Optional. The meta key to retrieve. Default empty.
	 * @param bool   $single       Optional. Whether to return a single value. Default false.
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 */
	function get_meta( $referral_id = 0, $meta_key = '', $single = false ) {
		return get_metadata( 'referral', $referral_id, $meta_key, $single );
	}

	/**
	 * Adds a meta data field to a referral.
	 *
	 * @access public
	 * @since  1.6
	 *
	 * @param int    $referral_id Optional. Referral ID. Default 0.
	 * @param string $meta_key     Optional. Meta data key. Default empty.
	 * @param mixed  $meta_value   Optional. Meta data value. Default empty
	 * @param bool   $unique       Optional. Whether the same key should not be added. Default false.
	 * @return bool False for failure. True for success.
	 */
	function add_meta( $referral_id = 0, $meta_key = '', $meta_value = '', $unique = false ) {
		return add_metadata( 'referral', $referral_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Updates an referral meta field based on referral ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and referral ID.
	 *
	 * If the meta field for the referral does not exist, it will be added.
	 *
	 * @access public
	 * @since  1.6
	 *
	 * @param int    $referral_id Optional. Referral ID. Default 0.
	 * @param string $meta_key     Optional. Meta data key. Default empty.
	 * @param mixed  $meta_value   Optional. Meta data value. Default empty.
	 * @param mixed  $prev_value   Optional. Previous value to check before removing. Default empty.
	 * @return bool False on failure, true if success.
	 */
	function update_meta( $referral_id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {
		return update_metadata( 'referral', $referral_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Removes metadata matching criteria from a affiliate.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching key, if needed.
	 *
	 * @access public
	 * @since  1.6
	 *
	 * @param int    $referral_id Optional. Referral ID. Default 0.
	 * @param string $meta_key     Optional. Meta data key. Default empty.
	 * @param mixed  $meta_value   Optional. Meta data value. Default empty.
	 * @return bool False for failure. True for success.
	 */
	function delete_meta( $referral_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'referral', $referral_id, $meta_key, $meta_value );
	}
        
        function get_commission_ids_by_commission_request_id( $commission_request_id ) {
            global $wpdb;
            
            $sql = "SELECT DISTINCT referral_id FROM " .$this->table_name . " "
                    . "WHERE meta_key = 'commission_request_id' AND "
                    . "meta_value = %d";
            $prepared = $wpdb->prepare($sql, $commission_request_id);
            $results = $wpdb->get_col( $prepared  );
            if (!empty($results)) {
                return $results;
            }
            return null;
            
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
			meta_id bigint(20) NOT NULL AUTO_INCREMENT,
			referral_id bigint(20) NOT NULL DEFAULT '0',
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY referral_id (referral_id),
			KEY meta_key (meta_key)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

}
