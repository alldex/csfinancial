<?php
/**
 * Shortcodes
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Removes the default shortcodes and loads the new form
 *
 * @since 1.0
 */
function affwp_afgf_replace_shortcode() {

	// return if no registration form exists
	if ( ! affwp_afgf_get_registration_form_id() ) {
		return;
	}

	// return if AffiliateWP does not exist
	if ( ! function_exists( 'affiliate_wp') ) {
		return;
	}

	$affiliatewp = affiliate_wp();

	// remove AffiliateWP's [affiliate_area] shortcode
	remove_shortcode( 'affiliate_area', array( $affiliatewp, 'affiliate_area' ) );

	// remove AffiliateWP's [affiliate_registration] shortcode
	remove_shortcode( 'affiliate_registration', array( $affiliatewp, 'affiliate_registration' ) );

	// add our new [affiliate_area] shortcode
	add_shortcode( 'affiliate_area', 'affwp_afgf_shortcode_affiliate_area' );

	// add our new [affiliate_registration] shortcode
	add_shortcode( 'affiliate_registration', 'affwp_afgf_shortcode_affiliate_registration' );

}
add_action( 'template_redirect', 'affwp_afgf_replace_shortcode' );

/**
 * Replaces AffiliateWP's [affiliate_area] shortcode with the shortcode
 *
 * @since 1.0
 */
function affwp_afgf_shortcode_affiliate_area( $atts, $content = null  ) {

	$form_id = affwp_afgf_get_registration_form_id();

	if ( function_exists( 'affwp_enqueue_script' ) ) {
		affwp_enqueue_script( 'affwp-frontend', 'affiliate_area' );
	}

	ob_start();

	if ( is_user_logged_in() && affwp_is_affiliate() ) {

		affiliate_wp()->templates->get_template_part( 'dashboard' );

	} elseif ( is_user_logged_in() && affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {

		echo gravity_form( $form_id );

	} else {

		if ( affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {

			echo gravity_form( $form_id );
		} else {
			affiliate_wp()->templates->get_template_part( 'no', 'access' );
		}

		if ( ! is_user_logged_in() ) {

			affiliate_wp()->templates->get_template_part( 'login' );

		}

	}

	return ob_get_clean();

}

/**
 *  Renders the affiliate registration form
 *
 *  @since 1.0
 *  @return string
 */
function affwp_afgf_shortcode_affiliate_registration( $atts, $content = null ) {

	if ( ! affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {
		return;
	}

	if ( affwp_is_affiliate() ) {
		return;
	}

	ob_start();

	gravity_form( affwp_afgf_get_registration_form_id() );

	return ob_get_clean();
}
