<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if(!class_exists('AffiliateWP_MLM_Base')) {
    require_once dirname(plugin_dir_path( __FILE__ ))
        . '/affiliatewp-multi-level-marketing/integrations/class-base.php';
}
class ReferralsLTP extends AffiliateWP_MLM_Base {
    
    function display_referral_amount() {
        $parent_affiliate_id = 3;
        $base_amount = 50;
        $reference = "";
        
        $upline = affwp_mlm_get_upline( $parent_affiliate_id );
        echo "<h3>Upline</h3><pre>";
        var_dump($upline);
        echo "</pre>";
        
        $affiliates = array_merge(array($parent_affiliate_id), $upline);
        do {
            $affiliateId = array_pop($affiliates);
            $amount = $this->calculate_referral_amount($affiliateId, $base_amount, $reference);

            echo "<h3>Affiliate: $affiliateId</h3><pre>Amount: " . $amount . "</pre>";
        } while (!empty($affiliates));
        
    }
}
