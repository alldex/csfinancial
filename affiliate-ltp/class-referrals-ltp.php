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
    
    function createReferral($affiliateId, $amount, $reference, $directAffiliate, $levelCount = 0) {

        $custom = 'direct';
        $description = 'Direct referral';
        if (!$directAffiliate) {
            $custom = 'indirect';
            $description = 'Indirect referral';
            if ($levelCount > 0) {
                $description .= ". Level $levelCount";
            }
        }
        // Process cart and get amount
        $data = array();
        $data['affiliate_id'] = $affiliateId;
        $data['description']  = $description;
        $data['amount']       = $amount;
        $data['reference']    = $reference;
        $data['custom']       = $custom; // Add referral type as custom referral data
        $data['context']      = 'ltp-commission';
        $data['status']       = 'paid';


        // create referral
        $referral_id = affiliate_wp()->referrals->add( $data );
        echo "id created: " . $referral_id;

        if ( $referral_id ) {
            do_action( 'affwp_ltp_referral_created', $referral_id, $data );
        }
    }
    
    function display_referral_amount() {
        $parent_affiliate_id = 3;
        $base_amount = 100;
        $reference = 'Policy: #5723422';
        
        
        // TODO: stephen need to handle active/inactive affiliates.
        $upline = affwp_mlm_get_upline( $parent_affiliate_id );
        if ($upline) {
            $upline = affwp_mlm_filter_by_status( $upline );
            
        }
        
        $affiliates = array_merge(array($parent_affiliate_id), $upline);
        echo "<h3>affiliates</h3><pre>";
        var_dump($affiliates);
        echo "</pre>";
        $level_count = 0;
        $priorAffiliateRate = 0;
        
        
        do {
            $affiliateId = array_shift($affiliates);
            $parentId = empty($affiliates) ? 'None' : $affiliates[0];
            $level_count++;
            $affiliateRate = affwp_get_affiliate_rate($affiliateId);
            
            $adjustedRate = ($affiliateRate > $priorAffiliateRate) ? $affiliateRate - $priorAffiliateRate : 0;
            $adjustedAmount = $base_amount * $adjustedRate;
            $priorAffiliateRate = $affiliateRate;
            
            echo "<h3>Affiliate: $affiliateId -> Parent Affiliate: $parentId"
                . "</h3><pre>Adjusted Rate: $adjustedRate, Adjusted Amount: "
                    . "$adjustedAmount</pre>";
            
            $directAffiliate = ($level_count === 1);
            $this->createReferral($affiliateId, $adjustedAmount, $reference, 
                    $directAffiliate, $level_count);
            
        } while (!empty($affiliates));
        
    }
}
