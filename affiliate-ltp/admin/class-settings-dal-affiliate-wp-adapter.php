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

    public function get_partner_rank_id() {
        // for now since we have no company setting we will grab the highest rank
        // based on order.
        
        $rank_id = absint($this->get_setting('affwp_ltp_partner_rank_id'));
        if (empty($rank_id)) {
            return 0;
        }
        
        return $rank_id;
    }

    public function get_generational_override_rate($override_level) {
        // absint will set everything to 0 if it can't parse the value
        
        $level = absint($override_level);
        $key = 'affwp_ltp_generational_override_' . $level . '_rate';
        $rate = absint($this->get_setting($key));
        
        // convert the rate to percentage value.
        if ($rate > 0) {
            $rate /= 100;
        }
        
        return $rate;
    }

}
