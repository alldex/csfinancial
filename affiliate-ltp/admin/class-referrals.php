<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-referrals
 *
 * @author snielson
 */
class AffiliateLTPReferrals {
    public function __construct() {
        remove_action('affwp_add_referral', 'affwp_process_add_referral');
        add_action('affwp_add_referral', array($this, 'processAddReferralRequest'));
    }
    function processAddReferralRequest( $requestData ) {
        if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_referrals' ) ) {
		wp_die( __( 'You do not have permission to manage referrals', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $requestData['affwp_add_referral_nonce'], 'affwp_add_referral_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ), array( 'response' => 403 ) );
	}
        
        try {
            $data = $this->getReferralDataFromRequest( $requestData );
            $this->createReferralHeirarchy($data['affiliate_id'], $data['amount'], 
                    $data['context'], $data['reference'], $data['description'], $data['status']);
            
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_added' ) );
        }
        catch (\Exception $ex) {
            // TODO: stephen need to log the exception.
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_add_failed' ) );
        }
        exit;
    }
    
    private function getReferralDataFromRequest( $data ) {
        if ( empty( $data['user_id'] ) && empty( $data['affiliate_id'] ) ) {
            throw new Exception("user_id and affiliate_id is missing");
	}

	if ( empty( $data['affiliate_id'] ) ) {

            $user_id      = absint( $data['user_id'] );
            $affiliate_id = affiliate_wp()->affiliates->get_column_by( 'affiliate_id', 'user_id', $user_id );

            if ( ! empty( $affiliate_id ) ) {

                    $data['affiliate_id'] = $affiliate_id;

            } else {
                throw new Exception("affiliate_id could not be found from user_id");
            }
	}

	$args = array(
		'affiliate_id' => absint( $data['affiliate_id'] ),
		'amount'       => ! empty( $data['amount'] )      ? sanitize_text_field( $data['amount'] )      : '',
		'description'  => ! empty( $data['description'] ) ? sanitize_text_field( $data['description'] ) : '',
		'reference'    => ! empty( $data['reference'] )   ? sanitize_text_field( $data['reference'] )   : '',
		'context'      => ! empty( $data['context'] )     ? sanitize_text_field( $data['context'] )     : '',
		'status'       => 'paid',
	);

	if ( ! empty( $data['date'] ) ) {
		$args['date'] = date_i18n( 'Y-m-d H:i:s', strtotime( $data['date'] ) );
	}
        
        return $args;
    }
    
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

        if ( $referral_id ) {
            do_action( 'affwp_ltp_referral_created', $referral_id, $data );
        }
    }
    
    function createReferralHeirarchy($directAffiliateId, $amount, $context, $reference, $description, $status = 'paid') {
        
        $upline = affwp_mlm_get_upline( $directAffiliateId );
        if ($upline) {
            $upline = affwp_mlm_filter_by_status( $upline );
            
        }
        
        $affiliates = array_merge(array($directAffiliateId), $upline);
        $levelCount = 0;
        $priorAffiliateRate = 0;
        
        
        do {
            $affiliateId = array_shift($affiliates);
            $levelCount++;
            $affiliateRate = affwp_get_affiliate_rate($affiliateId);
            
            $adjustedRate = ($affiliateRate > $priorAffiliateRate) ? $affiliateRate - $priorAffiliateRate : 0;
            $adjustedAmount = $amount * $adjustedRate;
            $priorAffiliateRate = $affiliateRate;
            
            $directAffiliate = ($levelCount === 1);
            $this->createReferral($affiliateId, $adjustedAmount, $reference, 
                    $directAffiliate, $levelCount);
            
        } while (!empty($affiliates));
        
    }
}

new AffiliateLTPReferrals();