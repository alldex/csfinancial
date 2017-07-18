<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\dashboard;

use AffiliateLTP\Current_User;
use AffiliateWP_Multi_Level_Marketing;

/**
 * Description of class-tabs
 *
 * @author snielson
 */
class Tabs implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    
    /**
     *
     * @var Current_User
     */
    private $current_user;
    
    public function __construct(Current_User $current_user) {
        $this->current_user = $current_user;
    }
    
    public function register_hooks_and_actions() {
        
        add_action ('plugins_loaded', array($this, 'remove_affiliate_wp_mlm_tab_hooks'));
        
        add_filter( 'affwp_affiliate_area_tabs', array($this, 'setup_affiliate_area_tabs') );
        
        add_filter( 'affwp_affiliate_area_show_tab', array($this, 'remove_unused_tabs'), 10, 2 );
        
        add_action( 'affwp_affiliate_dashboard_tabs', array( $this, 'add_agent_tabs' ), 10, 2 );
        
    }
    
    function setup_affiliate_area_tabs( $tabs ) {
        $is_partner = $this->current_user->is_partner();
        $add_tabs = ['organization', 'signup'];
        if ($is_partner) {
            $add_tabs[] = 'events';
            $add_tabs[] = 'promotions';
        }
        $new_tabs = array_merge( $tabs, $add_tabs );
        return $new_tabs;
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
    public function add_agent_tabs( $affiliate_id, $active_tab ) {
        
        $is_partner = $this->current_user->is_partner();
        
        // make sure we only show the tab if it hasn't been filtered out.
        if (affwp_affiliate_area_show_tab( 'organization' )) {
            ?>
            <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'organization' ? ' active' : ''; ?>">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'organization' ) ); ?>"><?php _e( 'Organization', 'affiliate-ltp' ); ?></a>
            </li>
                <?php	
        }
        
        if ($is_partner && affwp_affiliate_area_show_tab( 'events' )) {
            ?>
            <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'events' ? ' active' : ''; ?>">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'events' ) ); ?>"><?php _e( 'Events', 'affiliate-ltp' ); ?></a>
            </li>
                <?php	
        }
        
        if ($is_partner && affwp_affiliate_area_show_tab( 'promotions' )) {
            ?>
            <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'promotions' ? ' active' : ''; ?>">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'promotions' ) ); ?>"><?php _e( 'Promotions', 'affiliate-ltp' ); ?></a>
            </li>
                <?php	
        }
        
        if (affwp_affiliate_area_show_tab( 'signup' )) {
            ?>
            <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'signup' ? ' active' : ''; ?>">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'signup' ) ); ?>"><?php _e( 'Signup', 'affiliate-ltp' ); ?></a>
            </li>
                <?php	
        }
    }
}
