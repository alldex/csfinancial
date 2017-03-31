<?php
namespace AffiliateLTP\admin;

/**
 * Description of class-settings-dal
 *
 * @author snielson
 */
interface Settings_DAL {
    
    function get_setting( $setting_name );
    
    function get_company_rate();
    
    function get_company_agent_id();
    
    /**
     * Returns the rank id of the partner rank that triggers generational 
     * overrides
     */
    function get_partner_rank_id();
    
    /**
     * Returns the calculation for the generational override
     * @param int $override_level The current level to retrieve the rank for.
     */
    function get_generational_override_rate( $override_level );
    
    /**
     * Returns the minimum amount an agent must earn before they can receive
     * their payout.
     * @return double
     */
    function get_minimum_payout_amount();
}
