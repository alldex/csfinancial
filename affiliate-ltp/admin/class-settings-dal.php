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
}
