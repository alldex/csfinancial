<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *  Load the frontend styles
 *  
 *  @since 1.0
 *  @return void
 */
function affwp_pb_frontend_styles() {
	global $post;
		
	if ( affiliatewp_performance_bonuses()->is_bonuses_tab() 
		 || has_shortcode( $post->post_content, 'affiliate_area_bonuses' )
		 || ( function_exists('buddypress') && bp_is_user() ) ) {
	
		wp_enqueue_style( 'affwp-pb-frontend', AFFWP_PB_PLUGIN_URL . 'assets/css/bonuses.css', AFFWP_PB_VERSION );

	}
}
add_action( 'wp_enqueue_scripts', 'affwp_pb_frontend_styles' );