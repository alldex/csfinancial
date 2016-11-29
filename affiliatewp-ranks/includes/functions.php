<?php

/**
 * Get a Particular Rank
 *
 * @since 1.0
 * @return array
 */
function get_rank_by_id( $rank_id ) {

	$ranks = get_ranks();
	
	foreach( $ranks as $key => $rank ) {

		if( $rank['id'] != $rank_id ) {
		
			// Remove ranks that don't match
			unset( $ranks[ $key ] );
		
		}

	}
	
	return array_values( $ranks );
}

/**
 * Get ALL Ranks
 *
 * @since 1.0
 * @return array
 */
function get_ranks() {
	$ranks = affiliate_wp()->settings->get( 'ranks', array() );
	return array_values( $ranks );
}

/**
 * Get Ranks List for dropdown values
 *
 * @since 1.0
 * @return array
 */
function get_ranks_list() {

	// Get all ranks
	$ranks = get_ranks();

	// Build an array of rank IDs and rank names for the list
	$ranks_list = array();
	
	if ( $ranks && ! empty( $ranks ) ) {

		foreach( $ranks as $key => $rank ) {

			$rank_name = $rank['name'];
			$rank_id = $rank['id'];
			$ranks_list[ $rank_id ] = $rank_name;

		}

	}

	// return apply_filters( 'affwp_ranks_get_rank_list', $ranks );
	return apply_filters( 'affwp_ranks_get_ranks_list', $ranks_list );
}

/**
 * Get All Ranks in Level Mode
 *
 * @since 1.0
 * @return array
 */
function get_level_ranks() {

	$ranks = get_ranks();
	
	foreach( $ranks as $key => $rank ) {

		if( $rank['mode'] != 'level' ) {
		
			// Remove all other ranks
			unset( $ranks[ $key ] );
		
		}

	}
	
	return array_values( $ranks );
}

/**
 * Creates a unique rank id
 *
 * @since 1.0
 * @return int
 */
function affwp_ranks_new_rank_id() {

	// Generate a random number
	$rank_id = rand( 1, 9999 );
	
	if( $rank_id ) {
		return $rank_id;
	}
}

/**
 * Retrieves an array of allowed rank modes
 *
 * @since 1.0
 * @return array
 */
function affwp_ranks_get_rank_modes() {
	// Allowed modes
	$modes = array(
		'category' => __( 'Category', 'affiliatewp-ranks' ),
		'level'  => __( 'Level', 'affiliatewp-ranks' )
	);
	return apply_filters( 'affwp_ranks_get_rank_modes', $modes );
}

/**
 * Retrieves an array of allowed rank types
 *
 * @since 1.0
 * @return array
 */
function affwp_ranks_get_rank_types() {
	
	// Disable this function if performance bonuses isn't active
	if( ! function_exists( 'affwp_pb_get_bonus_types' ) )
		return $types;
	
	// Use supported performance bonus types
	$bonus_types = affwp_pb_get_bonus_types();

	$unsupported_types = array(
		'rank_promotion'  => __( 'Rank Promotion', 'affiliatewp-ranks' ),
		'earnings_percentage'  => __( 'Earnings Percentage', 'affiliatewp-group-commissions' )
	);
	
	// Remove unsupported rank types
	foreach( $bonus_types as $key => $bonus_type ) {

		if( in_array( $bonus_type, $unsupported_types ) )
			unset( $bonus_types[ $key ] );

	}
	
	$types = $bonus_types;
	
	return apply_filters( 'affwp_ranks_get_rank_types', $types );
}

/**
 * Returns the rank id of the current rank for a given affiliate
 *
 * If no affiliate ID is given, it will check the currently logged in affiliate
 *
 * @since 1.0
 * @return int
 */
function affwp_ranks_get_affiliate_rank( $affiliate_id = 0 ) {
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}
	
	$affiliate = affwp_get_affiliate( $affiliate_id );
	
	if( ! $affiliate )
		return;
	
	// Returns an array
	$affiliate_rank = affwp_get_affiliate_meta( $affiliate_id, 'current_rank' );
	
	$rank_id = (int)$affiliate_rank[0] ? (int)$affiliate_rank[0] : 0;
	
	return $rank_id;

}

/**
 * Returns the rank id of the last rank a given affiliate has earned
 *
 * If no affiliate ID is given, it will check the currently logged in affiliate
 *
 * @since 1.0
 * @return int
 */
function affwp_ranks_get_affiliate_last_rank( $affiliate_id ) {
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}
	
	// Returns an array
	$last_rank = affwp_get_affiliate_meta( $affiliate_id, 'last_rank' );
	
	$rank_id = (int)$last_rank[0] ? (int)$last_rank[0] : 0;
	
	return $rank_id;

} 

/**
 * Returns the rank id of the next rank a given affiliate will earn
 *
 * If no affiliate ID is given, it will check the currently logged in affiliate
 * If no rank ID is given, it will get the affiliate's current rank
 *
 * @since 1.0
 * @return int
 */
function affwp_ranks_get_affiliate_next_rank( $affiliate_id = 0, $rank_id = 0 ) {
	
	$next_rank_id = 0;
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}
	
	if ( empty( $rank_id ) ) {
		// Returns an array
		$current_rank = affwp_ranks_get_affiliate_rank( $affiliate_id );
		
		if( empty( $current_rank ) )
			return;
		
		$rank_id = $current_rank;
	}
	
	// Get the current rank's data
	$rank = get_rank_by_id( $rank_id );
	$rank_mode = $rank[0]['mode'];
	$rank_order = $rank[0]['order'];
	$next_rank_order = $rank_order + 1;
	
	// Make sure current rank is in level mode
	if( $rank_mode == 'level' ) {
		
		// Get all ranks in level mode
		$level_ranks = get_level_ranks();
		
		foreach( $level_ranks as $key => $level_rank ) {
			
			// Get the next rank's id
			if( $level_rank['order'] == $next_rank_order ) {
				$next_rank_id = $level_rank['id'];
			}
		
		}
		
	}
	
	return $next_rank_id;

} 

/**
 * Returns a list of rank ids for ranks a given affiliate has earned
 *
 * If no affiliate ID is given, it will check the currently logged in affiliate
 *
 * @since 1.0
 * @return int
 */
function affwp_ranks_get_affiliate_earned_ranks( $affiliate_id ) {
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}
	
	// Returns an array
	$earned_ranks = affwp_get_affiliate_meta( $affiliate_id, 'earned_ranks' );
	
	// Get the array inside the array
	$rank_ids = (array)$earned_ranks[0] ? (array)$earned_ranks[0] : 0;
	
	return $rank_ids;

}

/**
 * Returns the rank id of the previous rank for a given rank
 *
 * @since 1.0
 * @return int
 */
function affwp_ranks_get_previous_rank( $rank_id = 0 ) {
	
	$prev_rank_id = 0;
	
	if ( empty( $rank_id ) ) {
		return;
	}

	// Get the current rank's data
	$rank = get_rank_by_id( $rank_id );
	$rank_mode = $rank[0]['mode'];
	$rank_order = $rank[0]['order'];
	$prev_rank_order = $rank_order - 1;
	
	// Make sure current rank is in level mode
	if( $rank_mode == 'level' ) {
		
		// Get all ranks in level mode
		$level_ranks = get_level_ranks();
		
		foreach( $level_ranks as $key => $level_rank ) {
			
			// Get the previous rank's id
			if( $level_rank['mode']  == 'level' && $level_rank['order'] == $prev_rank_order ) {
				$prev_rank_id = $level_rank['id'];
			}
		
		}
		
	}
	
	return $prev_rank_id;

} 

/**
 * Returns the rank id of the next rank for a given rank
 *
 * @since 1.0
 * @return int
 */
function affwp_ranks_get_next_rank( $rank_id = 0 ) {
	
	$next_rank_id = 0;
	
	if ( empty( $rank_id ) ) {
		return;
	}
	
	// Get the current rank's data
	$rank = get_rank_by_id( $rank_id );
	$rank_mode = $rank[0]['mode'];
	$rank_order = $rank[0]['order'];
	$next_rank_order = $rank_order + 1;
	
	// Make sure current rank is in level mode
	if( $rank_mode == 'level' ) {
		
		// Get all ranks in level mode
		$level_ranks = get_level_ranks();
		
		foreach( $level_ranks as $key => $level_rank ) {
			
			// Get the next rank's id
			if( $level_rank['mode']  == 'level' && $level_rank['order'] == $next_rank_order ) {
				$next_rank_id = $level_rank['id'];
			}
		
		}
		
	}
	
	return $next_rank_id;

} 

/**
 * Checks to see if an affiliate has a given rank
 *
 * If no affiliate ID is given, it will check the currently logged in affiliate
 *
 * @since 1.0
 * @return bool
 */
function affwp_ranks_has_rank( $affiliate_id = 0, $rank_id = 0 ) {
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}
	
	$affiliate_rank_id = affwp_ranks_get_affiliate_rank( $affiliate_id );
	
	// Return false if the affiliate has no rank
	if ( empty( $affiliate_rank_id ) ) {
		return false;
	} 
	
	// Check if affiliate's rank matches
	if ( !empty( $rank_id ) && $rank_id == $affiliate_rank_id ) {
		return true;
	}

}

/**
 * Checks to see if an affiliate had a given rank in the past
 *
 * If no affiliate ID is given, it will check the currently logged in affiliate
 *
 * @since 1.0
 * @return bool
 */
function affwp_ranks_had_rank( $affiliate_id = 0, $rank_id = 0 ) {
	
	if ( empty( $affiliate_id ) ) {
		$affiliate_id = affwp_get_affiliate_id();
	}
	
	if ( empty( $rank_id ) ) {
		return;
	}
	
	$earned_rank_ids = affwp_ranks_get_affiliate_earned_ranks( $affiliate_id );
	
	if ( is_array( $earned_rank_ids ) && in_array( $rank_id, $earned_rank_ids ) ) {
		return true;
	}
	return false;
}

/**
 * Check if One Affiliate Out-Ranks Another 
 *
 * @since 1.0
 * @return bool
 */
function affwp_ranks_is_out_ranked( $affiliate_1_id = 0, $affiliate_2_id = 0 ) {
	
	if ( empty( $affiliate_1_id ) || empty( $affiliate_2_id ) )
		return;
	
		$affiliate_1_rank_id = affwp_ranks_get_affiliate_rank( $affiliate_1_id );
		$affiliate_2_rank_id = affwp_ranks_get_affiliate_rank( $affiliate_2_id );
	
	// Make sure both affiliates have ranks
	if ( !empty( $affiliate_1_rank_id ) && !empty( $affiliate_2_rank_id ) ) {
		
		// Get rank data
		$affiliate_1_rank = get_rank_by_id( $affiliate_1_rank_id );
		$affiliate_2_rank = get_rank_by_id( $affiliate_2_rank_id );
		
		// Compare rank order for both ranks
		if ( $affiliate_1_rank[0]['order'] < $affiliate_2_rank[0]['order'] ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Delete the Affiliate's Rank
 *
 * @since  1.0
 * @return void
 */
function affwp_ranks_delete_affiliate_rank( $affiliate_id = 0 ) {
	
	if( empty( $affiliate_id ) ) {
		return;
	}
	
	affwp_delete_affiliate_meta( $affiliate_id, 'current_rank' );

}

/**
 * Delete the Affiliate's Rank history
 *
 * @since  1.0
 * @return void
 */
function affwp_ranks_delete_affiliate_rank_history( $affiliate_id = 0 ) {
	
	if( empty( $affiliate_id ) ) {
		return;
	}
	
	// Delete last rank
	affwp_delete_affiliate_meta( $affiliate_id, 'last_rank' );
	
	// Delete earned ranks
	affwp_delete_affiliate_meta( $affiliate_id, 'earned_ranks' );

}
