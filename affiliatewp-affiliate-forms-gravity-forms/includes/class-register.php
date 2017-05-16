<?php

class Affiliate_WP_Gravity_forms_Register {

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_filter( 'gform_submit_button', array( $this, 'hidden_affwp_action' ), 10, 2 );
	}

	/**
	 * Adds a hidden affwp_action input field to prevent an extra email being sent
	 * when "Auto Register New Users" is enabled
	 *
	 * @since 1.0.9.2
	 */
	function hidden_affwp_action( $button, $form ) {
		$hidden_field = '<input type="hidden" name="affwp_action" value="affiliate_register" />';
		return $hidden_field . $button;
	}

	/**
	 * Register the affiliate / user
	 *
	 * @since 1.0
	 */
	public function register_user( $entry, $form ) {

		$email = isset( $entry[ affwp_afgf_get_field_id( 'email' ) ] ) ? $entry[ affwp_afgf_get_field_id( 'email' ) ] : '';

		// email is always required for logged out users
		if ( ! is_user_logged_in() && ! $email ) {
			return;
		}

		$password         = affwp_afgf_get_field_value( $entry['id'], 'password' );
		$username         = affwp_afgf_get_field_value( $entry['id'], 'username' );
		$website          = affwp_afgf_get_field_value( $entry['id'], 'website' );
		$payment_email    = affwp_afgf_get_field_value( $entry['id'], 'payment_email' );
		$promotion_method = affwp_afgf_get_field_value( $entry['id'], 'promotion_method' );
		$website_url      = affwp_afgf_get_field_value( $entry['id'], 'website' );

		if ( ! $username ) {
			$username = $email;
		}

		$name_ids    = affwp_afgf_get_name_field_ids();
		$first_name  = '';
		$last_name   = '';

		if ( $name_ids ) {

			// dual first name/last name field
			$name_ids = array_filter ( affwp_afgf_get_name_field_ids() );

			if ( count( $name_ids ) > 2 ) {

				// extended
				$first_name = isset( $entry[ (string) $name_ids[1] ] ) ? $entry[ (string) $name_ids[1] ] : '';
				$last_name  = isset( $entry[ (string) $name_ids[3] ] ) ? $entry[ (string) $name_ids[3] ] : '';

			} else if ( count( $name_ids ) == 2 ) {

				// normal
				$first_name = isset( $entry[ (string) $name_ids[0] ] ) ? $entry[ (string) $name_ids[0] ] : '';
				$last_name  = isset( $entry[ (string) $name_ids[1] ] ) ? $entry[ (string) $name_ids[1] ] : '';

			} else {

				// simple
				$first_name = isset( $entry[ affwp_afgf_get_field_id( 'name' ) ] ) ? $entry[ affwp_afgf_get_field_id( 'name' ) ] : '';

			}

		}

		// AffiliateWP will show the user as "user deleted" unless a display name is given
		if ( $first_name ) {

			if ( $last_name ) {
				$display_name = $first_name . ' ' . $last_name;
			} else {
				$display_name = $first_name;
			}

		} else {
			$display_name = $username;
		}

		$status = affiliate_wp()->settings->get( 'require_approval' ) ? 'pending' : 'active';

		if ( ! is_user_logged_in() ) {

			// use password fields if present, otherwise randomly generate one
			$password = $password ? $password : wp_generate_password( 12, false );

			$args = apply_filters( 'affiliatewp_afgf_insert_user', array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $display_name,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'entry_id'     => $entry['id']
			), $username, $email, $password, $display_name, $first_name, $last_name, $entry['id'] );

			$user_id = wp_insert_user( $args );

		} else {

			$user_id                  = get_current_user_id();
			$user                     = (array) get_userdata( $user_id );
			$args                     = (array) $user['data'];
			$args['has_user_account'] = true;
			$args['entry_id']         = $entry['id'];

		}

		if ( $promotion_method ) {
			update_user_meta( $user_id, 'affwp_promotion_method', $promotion_method );
		}

		if ( $website_url ) {
			wp_update_user( array( 'ID' => $user_id, 'user_url' => $website_url ) );
		}

		// add affiliate
		$affiliate_id = affwp_add_affiliate( array(
			'status'        => $status,
			'user_id'       => $user_id,
			'payment_email' => $payment_email
		) );

		if ( ! is_user_logged_in() ) {
			$this->log_user_in( $user_id, $username );
		}

		// Retrieve affiliate ID. Resolves issues with caching on some hosts, such as GoDaddy
		$affiliate_id = affwp_get_affiliate_id( $user_id );

		// store entry ID in affiliate meta so we can retrieve it later
		affwp_update_affiliate_meta( $affiliate_id, 'gravity_forms_entry_id', $entry['id'] );

		do_action( 'affwp_register_user', $affiliate_id, $status, $args );

	}

	/**
	 * Log the user in
	 *
	 * @since 1.0
	 */
	private function log_user_in( $user_id = 0, $user_login = '', $remember = false ) {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		wp_set_current_user( $user_id, $user_login );
		wp_set_auth_cookie( $user_id, $remember );

		do_action( 'wp_login', $user_login, $user );

	}

}
