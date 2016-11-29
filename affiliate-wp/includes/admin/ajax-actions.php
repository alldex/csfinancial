<?php

// retrieves a list of users via live search
function affwp_search_users() {
	if ( empty( $_REQUEST['term'] ) ) {
		wp_die( -1 );
	}

	if ( ! current_user_can( 'manage_affiliates' ) ) {
		wp_die( -1 );
	}

	$search_query = htmlentities2( trim( $_REQUEST['term'] ) );

	do_action( 'affwp_pre_search_users', $search_query );

	$args = array(
		'search_columns' => array( 'user_login', 'display_name', 'user_email' )
	);

	if ( isset( $_REQUEST['status'] ) ) {
		$status = mb_strtolower( htmlentities2( trim( $_REQUEST['status'] ) ) );

		switch ( $status ) {
			case 'none':
				$affiliates = affiliate_wp()->affiliates->get_affiliates(
					array(
						'number' => 9999,
					)
				);
				$args = array( 'exclude' => array_map( 'absint', wp_list_pluck( $affiliates, 'user_id' ) ) );
				break;
			case 'any':
				$affiliates = affiliate_wp()->affiliates->get_affiliates(
					array(
						'number' => 9999,
					)
				);
				$args = array( 'include' => array_map( 'absint', wp_list_pluck( $affiliates, 'user_id' ) ) );
				break;
			default:
				$affiliates = affiliate_wp()->affiliates->get_affiliates(
					array(
						'number' => 9999,
						'status' => $status,
					)
				);
				$args = array( 'include' => array_map( 'absint', wp_list_pluck( $affiliates, 'user_id' ) ) );
		}
	}

	// Add search string to args.
	$args['search'] = '*' . mb_strtolower( htmlentities2( trim( $_REQUEST['term'] ) ) ) . '*';

	// Get users matching search.
	$found_users = get_users( $args );

	$user_list = array();

	if ( $found_users ) {
		foreach( $found_users as $user ) {
			$user_list[] = array(
				'label'   => $user->user_login . " ({$user->user_email})",
				'value'   => $user->user_login,
				'user_id' => $user->ID
			);
		}
	}

	wp_die( json_encode( $user_list ) );
}
add_action( 'wp_ajax_affwp_search_users', 'affwp_search_users' );
