<?php

/*
        Plugin Name: Life Test Prep Affiliate extensions
        Plugin URI: https://www.lifetestprep.com/
        Version: 0.0.1
        Description: Shortcodes, customization, classes, and assessment functionality.
        Author: stephen@nielson.org
        Author URI: http://stephen.nielson.org
        License: All Rights Reserved
*/

class AffiliateLTP {
    public function __construct() {
        require_once "class-referrals-ltp.php";
        
        if( is_admin() ) {
            $includePath = plugin_dir_path( __FILE__ );
            require_once $includePath . '/admin/class-referrals.php';
        }
        add_shortcode('ltp_affiliate_display', array(__CLASS__, 'ltp_affiliate_display' ) );
    }
    
    public static function ltp_affiliate_display() {
        $affiliate = new ReferralsLTP();
        $affiliate->display_referral_amount();
    }
}

new AffiliateLTP();