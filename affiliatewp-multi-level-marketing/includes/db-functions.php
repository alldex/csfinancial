<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the downline of an affiliate
 *
 * @since 1.0
 */
function affwp_mlm_get_connections_table() {

	global $wpdb;

	if( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
	
		// Set the global table name
		$affwp_mlm_table_name = 'affiliate_wp_mlm_connections';
	
	} else{

		// Set the single site table name
		$affwp_mlm_table_name = $wpdb->prefix . 'affiliate_wp_mlm_connections';
		
	}

	return $affwp_mlm_table_name;

}

/**
 * Get an affiliate's sub-affiliates
 *
 * @since 1.0
 * @return object
 */
function affwp_mlm_get_sub_affiliates( $affiliate_id = 0 ) {

	global $wpdb;

	$affiliate_connection_table = affwp_mlm_get_connections_table();

	$affiliate_data = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT     *
			FROM       $affiliate_connection_table
			WHERE      affiliate_parent_id = %d
			",
			$affiliate_id
		)
	);

	// Return false if nothing returned
	if ( null ===  $affiliate_data ) {
		return false;
	}

	return $affiliate_data;

}

/**
 * Get an affiliate's directly referred sub-affiliates
 *
 * @since 1.0.4
 * @return object
 */
function affwp_mlm_get_direct_sub_affiliates( $affiliate_id = 0 ) {

	global $wpdb;

	$affiliate_connection_table = affwp_mlm_get_connections_table();

	$affiliate_data = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT     *
			FROM       $affiliate_connection_table
			WHERE      direct_affiliate_id = %d
			",
			$affiliate_id
		)
	);

	// Return false if nothing returned
	if ( null ===  $affiliate_data ) {
		return false;
	}

	return $affiliate_data;

}

/**
 * Get affiliate connections data
 * 
 * @since 1.0
 * @return object
 */
function affwp_mlm_get_affiliate_connections( $affiliate_id = 0 ) {

	global $wpdb;

	$affiliate_connection_table = affwp_mlm_get_connections_table();

	$affiliate_data = $wpdb->get_row( $wpdb->prepare(
			"
			SELECT     *
			FROM       $affiliate_connection_table
			WHERE      affiliate_id = %d
			",
			$affiliate_id
		)
	);

	// Return false if nothing returned
	if ( null ===  $affiliate_data ) {
		return false;
	}

	return $affiliate_data;

}

/**
 * Get the parent affiliate of a given affiliate
 *
 * @since 1.0.4
 */
function affwp_mlm_get_parent_affiliate( $affiliate_id ) {

	// Get the affiliate's direct affiliate
	$affiliate_connections = affwp_mlm_get_affiliate_connections( $affiliate_id );
	$parent_affiliate_id = $affiliate_connections ? $affiliate_connections->affiliate_parent_id : '';
	
	return $parent_affiliate_id;
}

/**
 * Get the direct referring affiliate of a given affiliate
 *
 * @since 1.0.4
 */
function affwp_mlm_get_direct_affiliate( $affiliate_id ) {

	// Get the affiliate's direct affiliate
	$affiliate_connections = affwp_mlm_get_affiliate_connections( $affiliate_id );
	$direct_affiliate_id = $affiliate_connections ? $affiliate_connections->direct_affiliate_id : '';
	
	return $direct_affiliate_id;
}

/**
 * Get the given affiliate's level in the matrix
 *
 * @since 1.0.5
 */
function affwp_mlm_get_matrix_level( $affiliate_id ) {

	// Get the affiliate's direct affiliate
	$affiliate_connections = affwp_mlm_get_affiliate_connections( $affiliate_id );
	$matrix_level = $affiliate_connections ? $affiliate_connections->matrix_level : '';
	
	return $matrix_level;
}

/**
 * Get the upline of an affiliate
 *
 * @since 1.0
 */
function affwp_mlm_get_upline( $affiliate_id = 0, $upline = array() ) {

	// Stop no affiliate is given
	if ( empty( $affiliate_id ) ) {
		return $upline;
	}

	static $level_count = 0;	
	$level_count++;
	$level_max = apply_filters( 'affwp_mlm_upline_level_max', 15, $affiliate_id, $upline );	

	// Get the affiliate's parent affiliate
	$parent_affiliate_id = affwp_mlm_get_parent_affiliate( $affiliate_id );
	
	// Stop if the affiliate has no upline
	if ( empty( $parent_affiliate_id ) ) {
		return $upline;
	}
	
	// Check level count to prevent endless loop or exceeding max memory limit
	if ( $level_count <= $level_max && $parent_affiliate_id && $parent_affiliate_id != $affiliate_id ) {
				
		// Check if affiliates are already in the array to prevent duplicates
		if ( ! in_array( $parent_affiliate_id, $upline ) ) { 
			$upline[] = $parent_affiliate_id;
		}
		
		// Get the parent affiliate's parent
		return affwp_mlm_get_upline( $parent_affiliate_id, $upline );
	}

	return $upline;
	
}

/**
 * Get the downline of an affiliate
 *
 * @since 1.0
 */
function affwp_mlm_get_downline( $affiliate_id, $downline = array() ) {
	
	// Get the affiliate's sub affiliates
	$sub_affiliates = affwp_mlm_get_sub_affiliates( $affiliate_id );
	$sub_affiliate_ids = wp_list_pluck( $sub_affiliates, 'affiliate_id' );
	
	if( $sub_affiliate_ids ) {
		
		// Check if affiliates are already in the array to prevent duplicates
		if ( ! in_array( $sub_affiliate_ids, $downline ) )
			$downline[] = $sub_affiliate_ids;
				
		// Get sub affiliates for each sub affiliate
		foreach ( $sub_affiliate_ids as $sub_id ) {
		
			// Check if the affiliate is already in the array to prevent duplicates
			if ( ! in_array( $sub_id, $downline ) )
				$downline[] = $sub_id;
			
			return affwp_mlm_get_downline( $sub_id, $downline );
							
		}
	}

	return $downline;
	
}

/**
 * Find an affiliate that can have a new sub affiliate
 *
 * @since 1.0
 */
function affwp_mlm_find_open_affiliate( $affiliate_ids ) {

	global $wpdb;

	$affiliate_ids = is_array( $affiliate_ids ) ? implode( ',', array_map( 'intval', $affiliate_ids ) ) : $affiliate_ids;

	$affiliate_connection_table = affwp_mlm_get_connections_table(); 

	if( !empty( $affiliate_ids ) ) {
		$sub_affiliates = $wpdb->get_results(
				"
				SELECT		*
				FROM		$affiliate_connection_table
				WHERE		affiliate_parent_id 
				IN			( {$affiliate_ids} )
				ORDER BY    affiliate_id ASC
				"
		);
	}

	$affiliate_ids = array();

	if( ! empty( $sub_affiliates ) ) {
		
		foreach ( $sub_affiliates as $sub_aff ) {
			
			$sub_aff_id = $sub_aff->affiliate_id;
			
			// Check for open and active sub affiliates
			if ( affwp_mlm_sub_affiliate_allowed( $sub_aff_id ) && 'active' == affwp_get_affiliate_status( $sub_aff_id ) ) {
				
				return $sub_aff_id;
				break;
			
			}
			
			$affiliate_ids[] = $sub_aff_id;
		
		}
		
		return affwp_mlm_find_open_affiliate( $affiliate_ids );
	
	}

}

/**
 * Get referrals for order
 *
 * @since 1.0
 */
function affwp_mlm_get_referrals_for_order( $reference, $context ) {

	global $wpdb;

	if( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
	
		$referral_table = 'affiliate_wp_referrals';
	
	} else{

		$referral_table = $wpdb->prefix . 'affiliate_wp_referrals';
		
	}

	$referrals = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT *
		FROM {$referral_table}
		WHERE reference = %d
		AND context = %s
		",
	$reference,
	$context
	) );

	return $referrals;

}

/**
 * Get referrals by type
 *
 * @since  1.0
 */
function affwp_mlm_get_referrals_by_type( $args = array(), $referral_type = '' ) {

	$defaults = array(
		'number'       => -1,
		'offset'       => 0,
		'referrals_id' => 0,
		'affiliate_id' => 0,
		'context'      => '',
		'status'       => array( 'paid', 'unpaid', 'rejected' )
	);

	$args  = wp_parse_args( $args, $defaults );

	// get the affiliate's referrals
	$referrals = affiliate_wp()->referrals->get_referrals(
		array(
			'number'       => $args['number'],
			'offset'       => $args['offset'],
			'referrals_id' => $args['referrals_id'],
			'affiliate_id' => $args['affiliate_id'],
			'context'      => $args['context'],
			'status'       => $args['status']
		)
	);

	// Only show referrals by type
	if ( $referrals ) {
		foreach ( $referrals as $key => $referral ) {
		
			$sub_affiliate_order = $referral->custom == $referral_type ? $referral->custom : '';

			if ( ! $sub_affiliate_order ) {
				unset( $referrals[$key] );
				// unset( $referrals );
			}

		}

		return $referrals;
	}

}

/**
 * Update parent affiliate
 * 
 * @since 1.0
 */
function affwp_mlm_update_affiliate_connections( $data = array() ) {

	global $wpdb;

	$affiliate_id        = absint( $data['affiliate_id'] );
	$parent_affiliate_id = absint( $data['parent_affiliate_id'] );
	$direct_affiliate_id = absint( $data['direct_affiliate_id'] );
	
	// Add affiliates level in the overall matrix
	$matrix_level 	 	 = absint( $data['matrix_level'] );
	$parent_connections  = affwp_mlm_get_affiliate_connections( $parent_affiliate_id );
	
	if( empty( $matrix_level ) ) {
	
		$matrix_level = !empty( $parent_connections->matrix_level ) ? $parent_connections->matrix_level : 0;
		$matrix_level++;
	
	}

	$affiliate_connection_table = affwp_mlm_get_connections_table();

	$affiliate_data = array(
		'affiliate_id'        => $affiliate_id,
		'affiliate_parent_id' => $parent_affiliate_id,
		'direct_affiliate_id' => $direct_affiliate_id,
		'matrix_level' 		  => $matrix_level
	);

	// Insert data
	$sql = $wpdb->replace( $affiliate_connection_table, $affiliate_data );

	return $sql;

}

/**
 * Delete parent affiliate
 * 
 * @since 1.0
 */
function affwp_mlm_delete_parent_affiliate( $affiliate_id = 0, $parent_affiliate_id = 0 ) {

	if ( empty( $affiliate_id ) )
		return;
	
	global $wpdb;
	
	$affiliate_connection_table = affwp_mlm_get_connections_table();
	
	if ( !empty( $parent_affiliate_id ) ) {
	
		$wpdb->delete( $affiliate_connection_table, array( 'affiliate_id' => $affiliate_id, 'affiliate_parent_id' => $parent_affiliate_id ), array( '%d' ) );
	
	} else{
	
		$wpdb->delete( $affiliate_connection_table, array( 'affiliate_id' => $affiliate_id ), array( '%d' ) );	
	}
	
	do_action( 'affwp_mlm_parent_deleted', $affiliate_id, $parent_affiliate_id );

}