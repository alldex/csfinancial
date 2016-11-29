<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Scripts
 *
 * @since 1.0
 * @return void
 */
function affwp_mlm_admin_scripts() {

	if ( ! affwp_is_admin_page() ) {
		return;
	}

	wp_enqueue_script( 'affwp-mlm-select2', AFFWP_MLM_PLUGIN_URL . 'lib/select2/select2.min.js', array( 'jquery' ), '3.5.2' );
	wp_enqueue_script( 'affwp-mlm-admin', AFFWP_MLM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'affwp-mlm-select2' ), '0.1.0' );
	wp_enqueue_style( 'affwp-mlm-select2', AFFWP_MLM_PLUGIN_URL . 'lib/select2/select2.css', array(), '3.5.2' );

}
add_action( 'admin_enqueue_scripts', 'affwp_mlm_admin_scripts' );