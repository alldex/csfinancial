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
            require_once $includePath . '/admin/class-menu.php';
        }
        add_shortcode('ltp_affiliate_display', array(__CLASS__, 'ltp_affiliate_display' ) );
        
        // come in last here.
        add_filter( 'load_textdomain_mofile', array($this, 'load_ltp_en_mofile'), 50, 2 );
        
        add_action( 'init', array($this, 'load_ltp_affiliate_ranks_translation' ) );
    }
    
    public static function ltp_affiliate_display() {
        $affiliate = new ReferralsLTP();
        $affiliate->display_referral_amount();
    }
    
    public function load_ltp_en_mofile( $mofile, $domain )
    {
        if ( 'affiliate-wp' == $domain )
        {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliate-wp-en.mo";
        }
        else if ( 'affiliatewp-multi-level-marketing' == $domain )
        {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliatewp-multi-level-marketing-en.mo";
        }
        else if ( 'affiliatewp-ranks' == $domain )
        {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliatewp-ranks-en.mo";
        }
        return $mofile;
    }
    
    public function load_ltp_affiliate_ranks_translation() {
        load_plugin_textdomain( 'affiliatewp-ranks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

}

new AffiliateLTP();