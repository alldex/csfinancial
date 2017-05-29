<?php
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
    
     /**
     * Returns the rank id of the trainer rank that has permission to update
     * settings for agents underneath them.
     */
    public function get_trainer_rank_id() {
        // for now since we have no company setting we will grab the highest rank
        // based on order.
        
        $rank_id = absint($this->get_setting('affwp_ltp_trainer_rank_id'));
        if (empty($rank_id)) {
            return 0;
        }
        
        return $rank_id;
    }

    public function get_generational_override_rate($override_level) {
        // absint will set everything to 0 if it can't parse the value
        
        $level = absint($override_level);
        $key = 'affwp_ltp_generational_override_' . $level . '_rate';
        $rate = floatval($this->get_setting($key));
        
        // convert the rate to percentage value.
        if ($rate > 0) {
            $rate /= 100;
        }
        
        return $rate;
    }
    
    public function get_minimum_payout_amount() {
        $val = $this->get_setting("affwp_ltp_minimum_default_payout_amount");
        if (!empty($val)) {
            return floatval($val);
        }
        return 0;
    }
    
    public function get_errors_and_ommissions_current_secret_api_key() {
        $mode = $this->get_setting('affwp_ltp_stripe_errors_and_ommissions_mode');
        $mode = ($mode === 'test') ? 'test' : 'live';
        if ($mode === 'test') {
            return $this->get_setting('affwp_ltp_stripe_errors_and_ommissions_test_secret_key');
        }
        return $this->get_setting('affwp_ltp_stripe_errors_and_ommissions_live_secret_key');
    }

}
