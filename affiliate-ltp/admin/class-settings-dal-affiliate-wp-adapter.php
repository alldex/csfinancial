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
        $ranks = get_level_ranks();
        usort($ranks, function ($item1, $item2) {
            if ($item1['order'] == $item2['order']) return 0;
            return $item1['order'] < $item2['order'] ? -1 : 1;
        });
        reset($ranks);
        $last_rank = end($ranks);
        
        return absint($last_rank['id']);
    }

    public function get_generational_override_rate($override_level) {
        switch ($override_level) {
            case 1: {
                $rate = 0.17;
            }
            break;
            case 2: {
                $rate = 0.09;
            }
            break;
            case 3: {
                $rate = 0.04;
            }
            break;
            default: {
                $rate = 0;
            }
            break;
        }
        return $rate;
    }

}
