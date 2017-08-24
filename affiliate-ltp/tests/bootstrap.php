<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Affiliate_Ltp
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/../affiliate-wp/affiliate-wp.php';
	require dirname( dirname( __FILE__ ) ) . '/../affiliatewp-multi-level-marketing/affiliatewp-multi-level-marketing.php';
        require dirname( dirname( __FILE__ ) ) . '/../gravityforms/gravityforms.php';
        require dirname( dirname( __FILE__ ) ) . '/../Gravity_Forms_MailChimp_Add_On/class-gf-mailchimp.php';
	require dirname( dirname( __FILE__ ) ) . '/affiliate-ltp.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
