<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Run if AffiliateWP Performance Bonuses is Active
if ( class_exists( 'AffiliateWP_Performance_Bonuses' ) ) {

	/**
	 * Adds Rank Promotion to the Bonus Types List
	 *
	 * @since 1.0
	 * @return array
	 */
	function affwp_pb_add_rank_promotion_type( $types = array() ) {
		
		$ranks_type = array(
			'rank_promotion'  => __( 'Rank Promotion', 'affiliatewp-ranks' )
		);
		$types = array_merge( $types, $ranks_type );
		
		return $types;
	}
	add_filter( 'affwp_pb_get_bonus_types', 'affwp_pb_add_rank_promotion_type', 10, 1 );
	
	/**
	 * Check for bonuses based on rank promotion
	 * 
	 * @since 1.0
	 */
	function affwp_pb_check_for_rank_promotion_bonus( $affiliate_id = 0, $current_rank_id = 0, $last_rank_id = 0 ) {
		
		if ( empty( $affiliate_id ) ) {
			return;
		}
		
		if ( empty( $last_rank_id ) )
			affwp_ranks_get_affiliate_last_rank( $affiliate_id );
		
		if ( empty( $current_rank_id ) )
			affwp_ranks_get_affiliate_rank( $affiliate_id );
		
		// Exclude bonuses that have already been earned	
		$bonuses = apply_filters( 'affwp_pb_get_active_bonuses', get_active_bonuses(), $affiliate_id );
		
		// Stop if all bonuses have been earned already
		if( empty( $bonuses ) ) {
			return;
		}
		
		foreach( $bonuses as $key => $bonus ) {
			
			// Check for rank promotion bonus
			if( $bonus['type'] == 'rank_promotion' ) {
			
				// Check if the affiliate's rank matches the required rank
				if( $current_rank_id == $bonus['requirement'] ) {
				
					$bonus_earned = affwp_pb_get_bonus_log( $affiliate_id, $bonus['pre_bonus'] );
					
					// Check for prerequisite bonus
					if( !empty( $bonus['pre_bonus'] ) && empty( $bonus_earned ) )
							continue;
					
					// Create the bonus
					affwp_pb_create_bonus_referral( $affiliate_id, $bonus['id'], $bonus['title'], $bonus['amount'] );
				
				}
			}
		}	
	}
	add_action( 'affwp_ranks_affiliate_rank_promoted', 'affwp_pb_check_for_rank_promotion_bonus', 10, 3 );

}