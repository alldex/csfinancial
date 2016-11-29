<?php

/**
 * Set the Affiliate's Rank
 *
 * @since  1.0
 * @return void
 */
function affwp_ranks_set_affiliate_rank( $affiliate_id = 0, $rank_id = 0 ) {
	
	if( empty( $affiliate_id ) || empty( $rank_id ) ) {
		return;
	}

	$old_rank_id = affwp_ranks_get_affiliate_rank( $affiliate_id );
	
	do_action( 'affwp_ranks_pre_set_affiliate_rank', $affiliate_id, $rank_id, $old_rank_id );
	
	if ( empty( $old_rank_id ) ) {
		
		// Add new rank
		affwp_add_affiliate_meta( $affiliate_id, 'current_rank', $rank_id );
		
	} elseif( $old_rank_id != $rank_id ) {
		
		// Update existing rank
		affwp_update_affiliate_meta( $affiliate_id, 'current_rank', $rank_id );
	
	}
	
	$new_rank_id = $rank_id;

	do_action( 'affwp_ranks_post_set_affiliate_rank', $affiliate_id, $new_rank_id, $old_rank_id );

}

/**
 * Save the Affiliate's Last Rank
 *
 * @since  1.0
 * @return void
 */
function affwp_ranks_save_affiliate_last_rank( $affiliate_id = 0, $new_rank_id = 0, $old_rank_id = 0 ) {
	
	if( empty( $affiliate_id ) || empty( $old_rank_id ) ) {
		return;
	}

	$last_rank_id = affwp_ranks_get_affiliate_last_rank( $affiliate_id );
	
	if ( empty( $last_rank_id ) ) {
		
		// Store old rank
		affwp_add_affiliate_meta( $affiliate_id, 'last_rank', $old_rank_id );
		
	} elseif( $last_rank_id != $old_rank_id ) {
		
		// Update last rank
		affwp_update_affiliate_meta( $affiliate_id, 'last_rank', $old_rank_id );
	
	}
	
	$new_last_rank_id = affwp_ranks_get_affiliate_last_rank( $affiliate_id );
	$old_last_rank_id = $last_rank_id;

	do_action( 'affwp_ranks_post_save_affiliate_last_rank', $affiliate_id, $new_last_rank_id, $old_last_rank_id );

}
add_action( 'affwp_ranks_post_set_affiliate_rank', 'affwp_ranks_save_affiliate_last_rank', 10, 3 );

/**
 * Add the new rank to the earned ranks list
 *
 * @since  1.0
 * @return void
 */
function affwp_ranks_save_affiliate_earned_ranks( $affiliate_id = 0, $new_rank_id = 0, $old_rank_id = 0 ) {
	
	if( empty( $affiliate_id ) || empty( $new_rank_id ) ) {
		return;
	}

	$earned_rank_ids = affwp_ranks_get_affiliate_earned_ranks( $affiliate_id );
	// $earned_rank_ids = affwp_get_affiliate_meta( $affiliate_id, 'earned_ranks' );
	
	if ( empty( $earned_rank_ids ) ) {
		
		if( !is_array( $earned_rank_ids ) )
			$earned_rank_ids = array();
			
		$earned_rank_ids[] = $new_rank_id;
		
		// Create the earned ranks list with the new rank
		affwp_add_affiliate_meta( $affiliate_id, 'earned_ranks', $earned_rank_ids );
		
	} elseif( !in_array( $new_rank_id, $earned_rank_ids ) ) {
		
		// Add new rank to earned ranks list
		$earned_rank_ids[] = $new_rank_id;
		
		// Update earned ranks list
		affwp_update_affiliate_meta( $affiliate_id, 'earned_ranks', $earned_rank_ids );
	
	}

	do_action( 'affwp_ranks_post_save_affiliate_earned_rank', $affiliate_id, $earned_rank_ids );

}
add_action( 'affwp_ranks_post_set_affiliate_rank', 'affwp_ranks_save_affiliate_earned_ranks', 10, 3 );



/**
 * Check if the affiliate's rank was promoted
 *
 * @since  1.0
 * @return bool
 */
function affwp_ranks_was_promoted( $affiliate_id = 0, $current_rank_id = 0, $last_rank_id = 0 ) {
	
	if ( empty( $affiliate_id ) ) {
		return;
	}
	
	if ( empty( $last_rank_id ) )
		$last_rank_id = affwp_ranks_get_affiliate_last_rank( $affiliate_id );
	
	if ( empty( $current_rank_id ) )
		$current_rank_id = affwp_ranks_get_affiliate_rank( $affiliate_id );
	
	// Get the current rank's data
	$current_rank = get_rank_by_id( $current_rank_id );
	$current_rank_mode = $current_rank[0]['mode'];
	$current_rank_order = $current_rank[0]['order'];

	// Get the last rank's data
	$last_rank = get_rank_by_id( $last_rank_id );
	$last_rank_mode = $last_rank[0]['mode'];
	$last_rank_order = $last_rank[0]['order'];
	
	// Make sure both ranks are in level mode
	if ( $last_rank_mode == 'level' && $current_rank_mode == 'level' ) {
		
		// Check for promotion by comparing rank order
		if ( $current_rank_order > $last_rank_order ) {
			
			do_action( 'affwp_ranks_affiliate_rank_promoted', $affiliate_id, $current_rank_id, $last_rank_id );
				
			return true;
		}
	}
	return false;
}
add_action( 'affwp_ranks_post_set_affiliate_rank', 'affwp_ranks_was_promoted', 10, 3 );

/**
 * Check if the affiliate's rank was demoted
 *
 * @since  1.0
 * @return bool
 */
function affwp_ranks_was_demoted( $affiliate_id = 0, $current_rank_id = 0, $last_rank_id = 0 ) {
	
	if ( empty( $affiliate_id ) ) {
		return;
	}
	
	if ( empty( $last_rank_id ) )
		$last_rank_id = affwp_ranks_get_affiliate_last_rank( $affiliate_id );
	
	if ( empty( $current_rank_id ) )
		$current_rank_id = affwp_ranks_get_affiliate_rank( $affiliate_id );
	
	// Get the current rank's data
	$current_rank = get_rank_by_id( $current_rank_id );
	$current_rank_mode = $current_rank[0]['mode'];
	$current_rank_order = $current_rank[0]['order'];

	// Get the last rank's data
	$last_rank = get_rank_by_id( $last_rank_id );
	$last_rank_mode = $last_rank[0]['mode'];
	$last_rank_order = $last_rank[0]['order'];
	
	// Make sure both ranks are in level mode
	if ( $last_rank_mode == 'level' && $current_rank_mode == 'level' ) {
		
		// Check for promotion by comparing rank order
		if ( $current_rank_order < $last_rank_order ) {
			
			do_action( 'affwp_ranks_affiliate_rank_demoted', $affiliate_id, $current_rank_id, $last_rank_id );
				
			return true;
		}
	}
	return false;
}
add_action( 'affwp_ranks_post_set_affiliate_rank', 'affwp_ranks_was_demoted', 10, 3 );


/**
 * Give new affiliate's the default rank during registration
 *
 * @since  1.0
 * @return void
 */
function affwp_ranks_set_affiliate_default_rank( $affiliate_id = 0 ) {
	
	if( empty( $affiliate_id ) )
		return;
	
	// Returns an array
	$default_rank = affiliate_wp()->settings->get( 'default_rank' );
	
	if( ! empty( $default_rank ) ) {

			affwp_ranks_set_affiliate_rank( $affiliate_id, $default_rank[0] );
	}

}
add_action( 'affwp_insert_affiliate', 'affwp_ranks_set_affiliate_default_rank', 10, 1 );


/**
 * Update the affiliate's rank manually
 *
 * @since  1.0
 */
function affwp_ranks_process_update_affiliate( $data = array() ) {

	if ( empty( $data['affiliate_id'] ) ) {
		return false;
	}

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_affiliates' ) ) {
		wp_die( __( 'You do not have permission to manage affiliates', 'affiliate-wp' ) );
	}

	$affiliate_id = absint( $data['affiliate_id'] );

	// If no rank is set remove the affiliate's rank
	if ( empty( $data['affiliate_rank_id'] ) ) {
		
		affwp_ranks_delete_affiliate_rank( $data['affiliate_id'] );

		return;
	} else {
		
			$rank_id = $data['affiliate_rank_id'];
			$has_rank = affwp_ranks_has_rank( $affiliate_id, $rank_id );
		
			// Stop if affiliate has this rank
			if ( $has_rank ) {
				return;
			}
			
		affwp_ranks_set_affiliate_rank( $affiliate_id, $rank_id );
	}

}
add_action( 'affwp_update_affiliate', 'affwp_ranks_process_update_affiliate', 5, 1 );

/**
 * Delete the affiliate's rank on affiliate deletion
 *
 * @since  1.0
 */
function affwp_ranks_process_affiliate_deletion( $data = array() ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_affiliates' ) ) {
		wp_die( __( 'You do not have permission to delete affiliate accounts', 'affiliate-wp' ) );
	}

	if ( ! wp_verify_nonce( $data['affwp_delete_affiliates_nonce'], 'affwp_delete_affiliates_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ) );
	}

	if ( empty( $data['affwp_affiliate_ids'] ) || ! is_array( $data['affwp_affiliate_ids'] ) ) {
		wp_die( __( 'No affiliate IDs specified for deletion', 'affiliate-wp' ) );
	}

	$to_delete = array_map( 'absint', $data['affwp_affiliate_ids'] );

	foreach ( $to_delete as $affiliate_id ) {
		affwp_ranks_delete_affiliate_rank( $affiliate_id );
	}

}
add_action( 'affwp_delete_affiliates', 'affwp_ranks_process_affiliate_deletion', 5, 1 );
