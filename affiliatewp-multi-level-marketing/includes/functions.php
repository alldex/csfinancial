<?php

/**
 * Check whether the current affiliate is a sub-affiliate
 *
 * @since  1.0
 * @return boolean
 */
function affwp_mlm_is_sub_affiliate( $affiliate_id = 0 ) {
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}

	if ( affwp_mlm_get_affiliate_connections( absint( $affiliate_id ) ) ) {
		return true;
	}

	return false;

}

/**
 * Check whether the current affiliate is a parent affiliate
 *
 * @since 1.0
 * @return boolean
 */
function affwp_mlm_is_parent_affiliate( $affiliate_id = 0 ) {
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}

	// Parent affiliates must have sub-affiliates
	if ( affwp_mlm_get_sub_affiliates( absint( $affiliate_id ) ) ) {
		return true;
	}

	return false;
}

/**
 * Check whether the current affiliate can have a new sub affiliate
 *
 * @since 1.0
 * @return boolean
 */
function affwp_mlm_sub_affiliate_allowed( $affiliate_id = 0 ) {

	if ( ! $affiliate_id ) {
		return;
	}

	// Make sure affiliate is active
	if ( 'active' !== affwp_get_affiliate_status( $affiliate_id ) ) {
		return false;
	}
	
	// Check if total depth is enabled
	if ( affiliate_wp()->settings->get( 'affwp_mlm_total_depth' ) ) {
			
		$matrix_level = affwp_mlm_get_matrix_level( $affiliate_id );
		$sub_level = $matrix_level + 1;
		$matrix_depth = affiliate_wp()->settings->get( 'affwp_mlm_matrix_depth' );
			
		// Check if matrix depth limit has been reached
		if ( $sub_level > $matrix_depth ) {
			return false;
		}
		
	}
	
	// Check if forced matrix is disabled
	if ( ! affiliate_wp()->settings->get( 'affwp_mlm_forced_matrix' ) ) {
		return true;
	}
	
	$initial_width = affiliate_wp()->settings->get( 'affwp_mlm_matrix_width' );
	$extra_width = affiliate_wp()->settings->get( 'affwp_mlm_matrix_width_extra' );
	$max_width = $initial_width + $extra_width;
	$sub_affiliate_count = count( affwp_mlm_get_sub_affiliates( absint( $affiliate_id ) ) );

	if ( $sub_affiliate_count < $initial_width ) {
		return true;
	} elseif ( ! empty( $extra_width ) && $sub_affiliate_count < $max_width ){
		return true;
	}
	
	return false;

}

/**
 * Filter an array of Affiliate IDs by each affiliate's level in the matrix
 *
 * @since 1.0
 * @return array
 */
function affwp_mlm_filter_by_level( $affiliate_ids = array(), $levels = 0 ) {

	if ( !empty( $affiliate_ids ) ) {
		
		if ( empty( $levels ) ) {
			
			$matrix_depth = affiliate_wp()->settings->get( 'affwp_mlm_matrix_depth' );
			$levels = $matrix_depth ? $matrix_depth : 15;
			
		}
		
		$level_count = 0;
		
		foreach( $affiliate_ids as $affiliate_id ) {
			
			$level_count++;
			
			if( $level_count > $levels ) {
				break;
			}
			
			$filtered_affiliates[] = $affiliate_id;
		
		}
		
		return $filtered_affiliates;
	
	} else{
		return;
	}
}

/**
 * Filter an array of Affiliate IDs by each affiliate's status
 *
 * @since 1.0.4
 */
function affwp_mlm_filter_by_status( $affiliate_ids = array(), $status = '' ) {
	
	// Stop if the affiliate has no upline
	if( empty( $affiliate_ids ) ) {
		return;
	}

	if( empty( $status ) ) {
		$status = 'active';
	}
	
	foreach( $affiliate_ids as $affiliate_id ) {
		
		// Skip affiliates that don't have the given status
		if( $status != affwp_get_affiliate_status( $affiliate_id ) ) {
			continue;
		}
		
		$filtered_affiliates[] = $affiliate_id;
	
	}
		
		return $filtered_affiliates;
}