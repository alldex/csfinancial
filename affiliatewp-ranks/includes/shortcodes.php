<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
* [affiliate_rank] shortcode
*
* @since  1.0.3
*/
function affwp_affiliate_rank_shortcode( $atts, $content = null ) {
	if ( ! ( is_user_logged_in() && affwp_is_affiliate() ) ) {
		return $content;
	}
	ob_start();
	include AFFWP_RANKS_PLUGIN_DIR . '/templates/affiliate-rank-dashboard.php';
	$content = ob_get_clean();
	return $content;
}
add_shortcode( 'affiliate_rank', 'affwp_affiliate_rank_shortcode' );