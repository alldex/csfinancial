<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AffiliateLTP\admin;

/**
 * Description of class-settings-dal-affiliate-wp-adapter
 *
 * @author snielson
 */
class Settings_DAL_Affiliate_WP_Adapter implements Settings_DAL {
    public function get_setting( $setting_name ) {
        return affiliate_wp()->settings->get( $setting_name );
    }
    
    public function get_company_rate() {
        return $this->get_setting('affwp_ltp_company_rate');
    }
    
    public function get_company_agent_id() {
        return $this->get_setting('affwp_ltp_company_agent_id');
    }
}
