<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * User migration class that handles importing existing user accounts as affiliates
 *
 * @since 1.3
 * @return void
 */
class Affiliate_WP_Migrate_Users extends Affiliate_WP_Migrate_Base {

	/**
	 * Migrate users belonging to these roles
	 *
	 * @var array
	 */
	public $roles = array();

	/**
	 * Process the migration routine
	 *
	 * @since  1.3
	 * @param  int    $step
	 * @param  string $part
	 * @return void
	 */
	public function process( $step = 1, $part = '' ) {

		if ( 'affiliates' !== $part || ! $this->roles ) {
			return;
		}

		$inserted = $this->do_users( $step );

		if ( $inserted ) {

			$this->step_forward( $step, 'affiliates' );

		} else {

			$this->finish();

		}

	}

	/**
	 * Move forward one step
	 *
	 * @since  1.3
	 * @param  int    $step
	 * @param  string $part
	 * @return void
	 */
	public function step_forward( $step = 1, $part = '' ) {

		$step++;

		$redirect = add_query_arg(
			array(
				'page'  => 'affiliate-wp-migrate',
				'type'  => 'users',
				'part'  => $part,
				'step'  => $step,
				'roles' => implode( ',', array_map( 'sanitize_text_field', $this->roles ) )
			),
			admin_url( 'index.php' )
		);

		wp_safe_redirect( $redirect );

		exit;

	}

	/**
	 * Import one batch of users
	 *
	 * @since  1.3
	 * @param  int     $step
	 * @return boolean
	 */
	public function do_users( $step = 1 ) {

		if ( ! $this->roles ) {
			return false;
		}

		$args = array(
			'number'     => 100,
			'offset'     => ( $step - 1 ) * 100,
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'role__in'   => $this->roles
		);

		$users = get_users( $args );

		if ( empty( $users ) ) {
			return false;
		}

		$inserted = array();

		foreach ( $users as $user ) {

			$affiliate_exists = affiliate_wp()->affiliates->get_by( 'user_id', $user->data->ID );

			if ( $affiliate_exists ) {
				continue;
			}

			$args = array(
				'status'          => 'active',
				'user_id'         => $user->data->ID,
				'payment_email'	  => $user->data->user_email,
				'date_registered' => $user->data->user_registered
			);

			$inserted[] = affiliate_wp()->affiliates->insert( $args, 'affiliate' );

		}

		if ( ! $inserted ) {
			return false;
		}

		return true;

	}

	/**
	 * Done creating affiliate accounts for users
	 *
	 * @since  1.7
	 * @return void
	 */
	public function finish() {

		$redirect = add_query_arg(
			array(
				'page'         => 'affiliate-wp-affiliates',
				'affwp_notice' => 'affiliate_added',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );

		exit;

	}

}
