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
    
    private $settings;
    
    public function __construct() {
        require_once "class-sugarcrm-dal.php";
        
        if( is_admin() ) {
            $includePath = plugin_dir_path( __FILE__ );
            require_once $includePath . '/admin/class-referrals.php';
            require_once $includePath . '/admin/class-menu.php';
            require_once $includePath . "/admin/class-settings.php";
            
            // setup the settings.
            $this->settings = new AffiliateLTPSettings();
            
            // setup our admin scripts.
            add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts' ) );
        }
        
        // come in last here.
        add_filter( 'load_textdomain_mofile', array($this, 'load_ltp_en_mofile'), 50, 2 );
        
        // do some cleanup on the plugins
	add_action ('plugins_loaded', array($this, 'fix_dependent_plugin_init_order'));
        add_action ('plugins_loaded', array($this, 'remove_affiliate_wp_mlm_tab_hooks'));
        
        add_action( 'init', array($this, 'load_ltp_affiliate_ranks_translation' ) );

	add_filter( 'hookpress_actions', array($this, 'add_hookpress_actions'), 10, 1);
	add_action( 'hookpress_hook_fired', array($this, 'log_hookpress_fired'), 10, 1);
        
        add_filter( 'affwp_affiliate_area_show_tab', array($this, 'remove_unused_tabs'), 10, 2 );
        
        add_action( 'affwp_affiliate_dashboard_tabs', array( $this, 'add_organization_tab' ), 10, 2 );
			
        // Add template folder to hold the sub affiliates tab content
        add_filter( 'affwp_template_paths', array( $this, 'get_theme_template_paths' ) );
        
        // so we can check to see if its active
        add_filter( 'affwp_affiliate_area_tabs', function( $tabs ) {
                return array_merge( $tabs, array( 'organization' ) );
        } );
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
    
    /**
     * Since the AffiliateWP_Multi_Level_Marketing class does not use the affiliatewp
     * affwp_affiliate_area_show_tab() function we have to just remove the hook
     * alltogether.
     */
    public function remove_affiliate_wp_mlm_tab_hooks() {
        if (class_exists("AffiliateWP_Multi_Level_Marketing")) {
            $instance = AffiliateWP_Multi_Level_Marketing::instance();
            remove_action( 'affwp_affiliate_dashboard_tabs', array( $instance, 'add_sub_affiliates_tab' ));
        }
    }
    
    /** Add any admin javascript files we need to load **/
    public function admin_scripts() {
        if( ! affwp_is_admin_page() ) {
		return;
	}

	//$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $plugin_url = plugin_dir_url( __FILE__ );
        $url = $plugin_url . 'assets/js/admin/ltp-admin' . $suffix . '.js';
	wp_enqueue_script( 'affiliate-ltp-admin', $url, array( 'jquery', 'jquery-ui-autocomplete'  ));
    }

    public function log_hookpress_fired( $desc ) {
	error_log(var_export($desc, true));
    }

    public function add_hookpress_actions( $hookpress_actions ) {
	$hookpress_actions['affwp_ltp_referral_created'] = array('referral_id', 'description', 'amount', 'reference', 'custom', 'context', 'status');
	return $hookpress_actions;
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
        else if ( 'affiliate-ltp' == $domain ) {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliate-ltp-en.mo";
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
            // leaving in sub-affiliates in case the MLM plugin ever fixes
            // itself to use the right functions and can be filtered out...
            case 'sub-affiliates':
//            case 'urls':
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
    
    /**
     * Add the organization tab.
     * @param type $affiliate_id
     * @param type $active_tab
     */
    public function add_organization_tab( $affiliate_id, $active_tab ) {
        
        // make sure we only show the tab if it hasn't been filtered out.
        if (affwp_affiliate_area_show_tab( 'organization' )) {
            ?>
            <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'organization' ? ' active' : ''; ?>">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'organization' ) ); ?>"><?php _e( 'Organization', 'affiliate-ltp' ); ?></a>
            </li>
                <?php	
        }
    }
    
    /**
    * Add template folder to hold the organization tab content
    *
    *
    * @return void
    */
   public function get_theme_template_paths( $file_paths ) {
           $file_paths[80] = plugin_dir_path( __FILE__ ) . '/templates';

           return $file_paths;
   }
   
            

}

new AffiliateLTP();
