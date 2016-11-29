<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the rate for an affiliate based on their rank
 * 
 * @since 1.0
 */
function affwp_ranks_get_rank_rate( $rate, $affiliate_id, $type, $reference ) {

	$affiliate_rank = affwp_ranks_get_affiliate_rank( $affiliate_id );

	// Stop if the affiliate has no rank
	if ( empty( $affiliate_rank ) ) {
		return $rate;
	}

	// If a per-affiliate rate is set, use that
	if ( affiliate_wp()->affiliates->get_column( 'rate', $affiliate_id ) ) {
		return $rate;
	}

	$affiliate_rank = affwp_ranks_get_affiliate_rank( $affiliate_id );
	$affiliate_rank_data = get_rank_by_id( $affiliate_rank );
	
	// Get per-rank rate
	$rank_rate = $affiliate_rank_data[0]['rate'];

	if( ! empty( $rank_rate ) ) {
		// Use the per-rank rate
		$rate = $rank_rate;
	
		// Format percentage rates
		$rate = ( 'percentage' === $type ) ? $rate / 100 : $rate;
	}
	
	return $rate;

}
add_filter( 'affwp_get_affiliate_rate', 'affwp_ranks_get_rank_rate', 10, 4 );

/**
 * Get the rate type for an affiliate based on their rank
 *
 * @since  1.0
 */
function affwp_ranks_get_rank_rate_type( $type, $affiliate_id ) {

	$affiliate_rank = affwp_ranks_get_affiliate_rank( $affiliate_id );

	// Stop if the affiliate has no rank
	if ( empty( $affiliate_rank ) ) {
		return $type;
	}

	// If a per-affiliate rate type is set, use that
	if ( affiliate_wp()->affiliates->get_column( 'rate_type', $affiliate_id ) ) {
		return $type;
	}

	$affiliate_rank = affwp_ranks_get_affiliate_rank( $affiliate_id );
	$affiliate_rank_data = get_rank_by_id( $affiliate_rank );
	$rank_rate_type = $affiliate_rank_data[0]['rate_type'];

	// Check for a per-rank rate type
	if ( !empty( $rank_rate_type ) ) {
		
		// Use the per-rank rate type
		$type = $rank_rate_type;
	}

	return $type;

}
add_filter( 'affwp_get_affiliate_rate_type', 'affwp_ranks_get_rank_rate_type', 10, 2 );