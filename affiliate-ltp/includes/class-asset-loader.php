<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

/**
 * Loads the javascript and stylesheets needed for the plugin
 *
 * @author snielson
 */
class Asset_Loader implements I_Register_Hooks_And_Actions {
    public function register_hooks_and_actions() {
         // setup our admin scripts.
        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts' ) );
        
        add_action( 'wp_enqueue_scripts', array($this, 'frontend_scripts') );
    }
    
    public function frontend_scripts() {
        $includePath = AFFILIATE_LTP_PLUGIN_URL;
        
        wp_enqueue_script("jqueryui-accordion", $includePath . 'assets/js/accordion.min.js', array('jquery'), '1.11.2');
        
        wp_enqueue_style('fancy-box', $includePath . 'assets/fancybox/source/jquery.fancybox.css');
        wp_enqueue_script('fancy-box', $includePath . 'assets/fancybox/source/jquery.fancybox.js', array('jquery'));
        
        wp_enqueue_style( 'affiliate-ltp', $includePath . 'assets/css/affiliate-ltp.css', array('fancy-box') );
        
        wp_enqueue_script( 'affiliate-ltp-core', $includePath . 'assets/js/affiliate-ltp-core.js', array( 'jquery', 'fancy-box', 'jqueryui-accordion'  ) );
        wp_localize_script( 'affiliate-ltp-core', 'wp_ajax_object',
            array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        // make sure it's always there...
        $wp_scripts = wp_scripts();
        $theme = 'ui-lightness';
        wp_enqueue_style('affiliate-ltp-jquery-ui-css',
                        'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/' . $theme . '/jquery-ui.css');
        wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.8.6');
    }
    
    /** Add any admin javascript files we need to load **/
    public function admin_scripts() {
        if( ! affwp_is_admin_page() ) {
		return;
	}

        $suffix = "";
//	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $plugin_url = AFFILIATE_LTP_PLUGIN_URL;
        $url = $plugin_url . 'assets/js/admin/ltp-admin' . $suffix . '.js';
        wp_enqueue_script( 'angular', $plugin_url . 'assets/js/bower_components/angular/angular.min.js');
	wp_enqueue_script( 'affiliate-ltp-admin', $url, array( 'jquery', 'jquery-ui-autocomplete', 'angular' ));
        
        wp_enqueue_style( 'affwp-admin', $plugin_url . 'assets/css/admin' . $suffix . '.css', array());
        
    }

}
