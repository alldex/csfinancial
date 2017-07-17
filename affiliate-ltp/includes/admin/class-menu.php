<?php
namespace AffiliateLTP\admin;

use AffiliateLTP\admin\Referrals;

class Menu implements \AffiliateLTP\I_Register_Hooks_And_Actions {

    // TODO: stephen we should probably setup some kind of registry here
    /**
     * 
     * @var Referrals
     */
    private $referrals;

	public function __construct(Referrals $referrals) {
                $this->referrals = $referrals;
	}
        
        public function register_hooks_and_actions() {
            add_action( 'admin_menu', array( $this, 'register_menus' ), 100 );
        }

	public function register_menus() {
            global $wp_filter;
                
		//add_menu_page( __( 'Affiliates', 'affiliate-wp' ), __( 'Affiliates', 'affiliate-wp' ), 'view_affiliate_reports', 'affiliate-wp', 'affwp_affiliates_dashboard' );
            
                remove_submenu_page('affiliate-wp', 'affiliate-wp-visits');
                remove_submenu_page('affiliate-wp', 'affiliate-wp-creatives');
                remove_submenu_page('affiliate-wp', 'affiliate-wp-referrals');
                
                // remove the actual action so we don't execute it.
                $hookname = get_plugin_page_hookname( 'affiliate-wp-referrals', 'affiliate-wp');
                remove_action($hookname, 'affwp_referrals_admin');
                
                // add it again but we are redoing the work here
                add_submenu_page( 'affiliate-wp', __( 'Referrals', 'affiliate-wp' ), __( 'Referrals', 'affiliate-wp' ), 'manage_referrals', 'affiliate-wp-referrals', array($this->referrals, 'handleAdminSubMenuPage') );
            
//                add_menu_page( __( 'Affiliates', 'affiliate-wp' ), __( 'Affiliates', 'affiliate-wp' ), 'view_affiliate_reports', 'affiliate-wp', 'affwp_affiliates_dashboard' );
//		add_submenu_page( 'affiliate-wp', __( 'Overview', 'affiliate-wp' ), __( 'Overview', 'affiliate-wp' ), 'view_affiliate_reports', 'affiliate-wp', 'affwp_affiliates_dashboard' );
//		add_submenu_page( 'affiliate-wp', __( 'Affiliates', 'affiliate-wp' ), __( 'Affiliates', 'affiliate-wp' ), 'manage_affiliates', 'affiliate-wp-affiliates', 'affwp_affiliates_admin' );
//		add_submenu_page( 'affiliate-wp', __( 'Referrals', 'affiliate-wp' ), __( 'Referrals', 'affiliate-wp' ), 'manage_referrals', 'affiliate-wp-referrals', 'affwp_referrals_admin' );
//		add_submenu_page( 'affiliate-wp', __( 'Visits', 'affiliate-wp' ), __( 'Visits', 'affiliate-wp' ), 'manage_visits', 'affiliate-wp-visits', 'affwp_visits_admin' );
//		add_submenu_page( 'affiliate-wp', __( 'Creatives', 'affiliate-wp' ), __( 'Creatives', 'affiliate-wp' ), 'manage_creatives', 'affiliate-wp-creatives', 'affwp_creatives_admin' );
//		add_submenu_page( 'affiliate-wp', __( 'Reports', 'affiliate-wp' ), __( 'Reports', 'affiliate-wp' ), 'view_affiliate_reports', 'affiliate-wp-reports', 'affwp_reports_admin' );
//		add_submenu_page( 'affiliate-wp', __( 'Tools', 'affiliate-wp' ), __( 'Tools', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-tools', 'affwp_tools_admin' );
//		add_submenu_page( 'affiliate-wp', __( 'Settings', 'affiliate-wp' ), __( 'Settings', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-settings', 'affwp_settings_admin' );
//		add_submenu_page( null, __( 'AffiliateWP Migration', 'affiliate-wp' ), __( 'AffiliateWP Migration', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-migrate', 'affwp_migrate_admin' );
//		add_submenu_page( 'affiliate-wp', __( 'Add-ons', 'affiliate-wp' ), __( 'Add-ons', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-add-ons', 'affwp_add_ons_admin' );
	}

}