<?php

/**
 * Update the affiliate
 *
 * @since  1.0
 * @todo  use new hook to do this
 */
function affwp_mlm_process_update_affiliate( $data = array() ) {

	if ( empty( $data['affiliate_id'] ) ) {
		return false;
	}

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_affiliates' ) ) {
		wp_die( __( 'You do not have permission to manage affiliates', 'affiliate-wp' ) );
	}

	// If no parent affiliate, delete
	if ( empty( $data['parent_affiliate_id'] ) ) {
		affwp_mlm_delete_parent_affiliate( $data['affiliate_id'] );

		return;
	}

	$affiliate_id = absint( $data['affiliate_id'] );

	if ( ! affwp_mlm_get_affiliate_connections( $affiliate_id ) ) {
		affwp_mlm_add_affiliate_connections( $data );
	} else {
		affwp_mlm_update_affiliate_connections( $data );
	}

}
// Handle updating and deleting parent affiliate data
// Note: Needs to be done at earlier priority because default hooks redirect
// and exit thus preventing later priority actions from running
add_action( 'affwp_update_affiliate', 'affwp_mlm_process_update_affiliate', 5, 1 );

/**
 * Delete parent affiliate connection when an affiliate is deleted
 *
 * @since  1.0
 */
function affwp_mlm_process_affiliate_deletion( $affiliate_id, $delete_data ) {

	if ( ! is_admin() ) {
		return;
	}

	affwp_mlm_delete_parent_affiliate( $affiliate_id );

}
add_action( 'affwp_affiliate_deleted', 'affwp_mlm_process_affiliate_deletion', 10, 2 );

/**
 * Link sub-affiliate to parent on affiliate registration
 * 
 * @since  1.0
 */
function affwp_mlm_connect_affiliates( $affiliate_id ) {

	// Get currently tracked affiliate from cookie
	$direct_affiliate_id = affiliate_wp()->tracking->get_affiliate_id();
	
	if ( empty( $direct_affiliate_id ) ) {
	
		$default_parent = affiliate_wp()->settings->get( 'affwp_mlm_default_affiliate' );
		
		if ( !empty( $default_parent ) ) {
		
			// If no referring affiliate, use default if set
			$direct_affiliate_id = $default_parent;
		
		} elseif ( affiliate_wp()->settings->get( 'affwp_mlm_forced_matrix' ) ) {
			
			// Get the 1st active affiliate in the database
			$active_affiliate = affiliate_wp()->affiliates->get_by( 'status', 'active' );
			
			// If forced matrix is enabled, find the first available affiliate
			$direct_affiliate_id = affwp_mlm_find_open_affiliate( $active_affiliate->affiliate_id );
		}
		
	}
	
	if ( affwp_mlm_sub_affiliate_allowed( $direct_affiliate_id ) && 'active' == affwp_get_affiliate_status( $direct_affiliate_id ) ) {
	
		$parent_affiliate_id = $direct_affiliate_id;
	
	} else{
	
	// Tracked affiliate can't have more subs, get the next available affiliate below the referrer
	$parent_affiliate_id = affwp_mlm_find_open_affiliate( $direct_affiliate_id );

	}

	if ( $parent_affiliate_id ) {
	
		// Add affiliates level in the overall matrix
		$parent_connections = affwp_mlm_get_affiliate_connections( $parent_affiliate_id );
		$matrix_level = !empty( $parent_connections->matrix_level ) ? $parent_connections->matrix_level : 0;
		$matrix_level++;
	
		// Add parent affiliate & direct affiliate
		$mlm_data = array(
			'parent_affiliate_id' => $parent_affiliate_id,
			'direct_affiliate_id' => $direct_affiliate_id,
			'matrix_level' 		  => $matrix_level,
			'affiliate_id'        => $affiliate_id
		);
		
		if ( affwp_mlm_add_affiliate_connections( $mlm_data ) ) {
			
			do_action( 'affwp_mlm_affiliates_connected', $affiliate_id, $mlm_data );
		
		}
	}
	
}
add_action( 'affwp_insert_affiliate', 'affwp_mlm_connect_affiliates', 10, 1 );

/**
 * Add parent affiliate
 * 
 * @since 1.0
 */
function affwp_mlm_add_affiliate_connections( $data = array() ) {

	global $wpdb;

	$affiliate_id        = absint( $data['affiliate_id'] );
	$parent_affiliate_id = absint( $data['parent_affiliate_id'] );
	$direct_affiliate_id = ! empty( $data['direct_affiliate_id'] ) ? absint( $data['direct_affiliate_id'] ) : '';
	$matrix_level = ! empty( $data['matrix_level'] ) ? absint( $data['matrix_level'] ) : '';

	$affiliate_connection_table = affwp_mlm_get_connections_table();

	$affiliate_data = array(
		'affiliate_id'        => $affiliate_id,
		'affiliate_parent_id' => $parent_affiliate_id,
		'direct_affiliate_id' => $direct_affiliate_id,
		'matrix_level' 		  => $matrix_level
	);

	// Insert data
	$sql = $wpdb->insert( $affiliate_connection_table, $affiliate_data );
	return $sql;
}