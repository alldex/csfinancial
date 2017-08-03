<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\dashboard;

use AffiliateLTP\Current_User;
use AffiliateWP_Multi_Level_Marketing;

/**
 * Handles adding and managing the different dashboard tabs that are part
 * of this plugin including any of their widgets / components that are used.
 *
 * @author snielson
 */
class Dashboard implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    
    /**
     *
     * @var Current_User
     */
    private $current_user;
    
    public function __construct(Current_User $current_user) {
        $this->current_user = $current_user;
    }
    
    public function register_hooks_and_actions() {
        $this->remove_affiliate_wp_mlm_tab_hooks();
        
        add_filter( 'affwp_affiliate_area_tabs', array($this, 'setup_affiliate_area_tabs') );
        
        add_filter( 'affwp_affiliate_area_show_tab', array($this, 'remove_unused_tabs'), 10, 2 );
        
        add_action( 'affwp_affiliate_dashboard_tabs', array( $this, 'add_agent_tabs' ), 10, 2 );
        
        add_filter('affwp_report_date_options', array($this, 'add_dashboard_date_options'), 10, 1);
        
        add_filter('affwp_report_dates', array($this, 'parse_dashboard_date_options'), 10, 1);
    }
    
    function parse_dashboard_date_options($dates) {
        $current_time = current_time( 'timestamp' );
        $range = $dates['range'];
        switch ($range) {
            case 'last_12_months': {
                $end_time = strtotime("-1 YEAR", $current_time);
                $end_month = date( 'm', $end_time);
                $end_year = date('Y', $end_time);
                $dates['day']       = date('d', $end_time);
                $dates['day_end']   = date('d', $current_time);
                $dates['m_start'] 	= $end_month;
                $dates['m_end']		= date('m', $current_time);
                $dates['year']		= $end_year;
                $dates['year_end']  = date( 'Y', $current_time );
            }
            break;
            case 'last_3_months': {
                $end_time = strtotime("-3 MONTHS", $current_time);
                $end_month = date( 'm', $end_time);
                $end_year = date('Y', $end_time);
                $dates['day']       = date('d', $end_time);
                $dates['day_end']   = date('d', $current_time);
                $dates['m_start'] 	= $end_month;
                $dates['m_end']		= date('m', $current_time);
                $dates['year']		= $end_year;
                $dates['year_end']  = date( 'Y', $current_time );
            }
            break;
        }
        return $dates;
        
        /*
         * case 'this_year' :
			$dates['day']       = 1;
			$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 12, date( 'Y', $current_time ) );
			$dates['m_start'] 	= 1;
			$dates['m_end']		= 12;
			$dates['year']		= date( 'Y', $current_time );
			$dates['year_end']  = date( 'Y', $current_time );
		break;

		case 'last_year' :
			$dates['day']       = 1;
			$dates['day_end']   = cal_days_in_month( CAL_GREGORIAN, 12, date( 'Y', $current_time ) - 1 );
			$dates['m_start'] 	= 1;
			$dates['m_end']		= 12;
			$dates['year']		= date( 'Y', $current_time ) - 1;
			$dates['year_end']  = date( 'Y', $current_time ) - 1;
		break;

	endswitch;

	return apply_filters( 'affwp_report_dates', $dates );
         */
    }
    
    function add_dashboard_date_options( $date_options ) {
        $new_options = array_merge([
            "last_12_months" => "Last 12 months"
            ,"last_3_months" => "Last 3 months"
        ], $date_options);
        
        return $new_options;
    }
    
    function setup_affiliate_area_tabs( $tabs ) {
        $is_partner = $this->current_user->is_partner();
        $add_tabs = ['commissions','organization', 'signup'];
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
            case 'referrals':
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
    
    private function echo_agent_tab($active_tab, $tab_name, $display_name ) {
        ?>
            <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == $tab_name ? ' active' : ''; ?>">
                <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_name ) ); ?>"><?php _e( $display_name, 'affiliate-ltp' ); ?></a>
            </li>
        <?php
    }
    
    /**
     * Add the organization tab.
     * @param type $affiliate_id
     * @param type $active_tab
     */
    public function add_agent_tabs( $affiliate_id, $active_tab ) {
        $tabs = [
            'commissions' => 'Commissions'
            ,'organization' => 'Organization'
            ,'signup' => 'Signup'
        ];
        
        foreach ( $tabs as $tab_name => $display_name ) {
            // make sure we only show the tab if it hasn't been filtered out.
            if ( affwp_affiliate_area_show_tab( $tab_name ) ) {
                $this->echo_agent_tab( $active_tab, $tab_name, $display_name );
            }
        }
        
        $is_partner = $this->current_user->is_partner();
        $partner_tabs = [
            'events' => 'Events'
            ,'promotions' => 'Promotions'
        ];
        foreach ( $partner_tabs as $tab_name => $display_name ) {
            // make sure we only show the tab if it hasn't been filtered out.
            if ( $is_partner && affwp_affiliate_area_show_tab( $tab_name ) ) {
                $this->echo_agent_tab( $active_tab, $tab_name, $display_name );
            }
        }
    }
}
