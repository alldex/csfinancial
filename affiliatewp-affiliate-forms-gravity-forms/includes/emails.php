<?php
/**
 * Emails
 *
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Send email to customer if password fields aren't present and the password has been auto generated
 */
function affwp_afgf_email_password( $affiliate_id, $status, $args ) {

	$entry_id         = isset( $args['entry_id'] ) ? $args['entry_id'] : '';
	$has_user_account = isset( $args['has_user_account'] ) ? $args['has_user_account'] : '';

	// only send if password was auto generate
	if ( empty( $affiliate_id ) ) {
		return;
	}

	// if the user was already logged in when registering we don't want to send login details via email
	if ( $has_user_account ) {
		return;
	}

	// already has a password
	if ( affwp_afgf_get_field_value( $entry_id, 'password' ) ) {
		return;
	}

	$emails  = new Affiliate_WP_Emails;

	$email      = affwp_get_affiliate_email( $affiliate_id );

	$subject    = __( 'Your login details', 'affiliatewp-afgf' );

	$username   = isset( $args['user_login'] ) ? $args['user_login'] : '';
	$password   = isset( $args['user_pass'] ) ? $args['user_pass'] : '';
	$first_name = isset( $args['first_name'] ) ? $args['first_name'] : '';

	$message  = sprintf( __( 'Hi %s!', 'affiliatewp-afgf' ), affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id ) ) . "\n\n";
	$message  .= sprintf( __( 'Your username is: %s', 'affiliatewp-afgf' ), $username ) . "\n\n";
	$message  .= sprintf( __( 'Your password is: %s', 'affiliatewp-afgf' ), $password ) . "\n\n";
	$message  .= sprintf( __( 'Log into your affiliate area at %s', 'affiliatewp-afgf' ), affiliate_wp()->login->get_login_url() ) . "\n\n";

	// send email
	$emails->send( $email, $subject, $message );

}
add_action( 'affwp_register_user', 'affwp_afgf_email_password', 10, 3 );
