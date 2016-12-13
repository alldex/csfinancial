<?php

/*
        Plugin Name: Life Test Prep Affiliate-WP and Hookpress extensions
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
        
	add_action ('plugins_loaded', array($this, 'fix_dependent_plugin_init_order'));
        add_action( 'init', array($this, 'load_ltp_affiliate_ranks_translation' ) );

	add_filter( 'hookpress_actions', array($this, 'add_hookpress_actions'), 10, 1);
	add_action( 'hookpress_hook_fired', array($this, 'log_hookpress_fired'), 10, 1);
        
        add_filter( 'affwp_affiliate_area_show_tab', array($this, 'remove_unused_tabs'), 10, 2 );
    }

    public function fix_dependent_plugin_init_order() {
	if (!(function_exists('affwp_do_actions') || function_exists('hookpress_init'))) {
		error_log("Cannot reorder init sequence. affiliates-wp plugin or hookpress plugin has changed init logic.");
		return;
	}
	remove_action( 'init', 'affwp_do_actions' );
	remove_action( 'init', 'hookpress_init' );

	// add these in the order that they need to execute
	add_action( 'init', 'hookpress_init', 10);
	add_action( 'init', 'affwp_do_actions', 20);
    }

    public function log_hookpress_fired( $desc ) {
	error_log(var_export($desc, true));
    }

    public function add_hookpress_actions( $hookpress_actions ) {
	$hookpress_actions['affwp_ltp_referral_created'] = array('referral_id', 'description', 'amount', 'reference', 'custom', 'context', 'status');
	return $hookpress_actions;
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
    
    /**
     * Remove the tabs that are not used for the agent system.
     * @param boolean $currentValue the current value of displaying the tab or not.
     * @param string $tab the tab to check if we need to remove
     */
    public function remove_unused_tabs( $currentValue, $tab ) {
        $shouldDisplay = true;
        
        // if the current value is not true just escape.
        if (!$currentValue) {
            return $currentValue;
        }
        
        switch ($tab) {
            case 'urls':
            case 'visits':
            case 'creatives': {
                $shouldDisplay = false;
            }
            break;
            default: {
                $shouldDisplay = true;
            }
        }
        
        return $shouldDisplay;
    }

}

new AffiliateLTP();
