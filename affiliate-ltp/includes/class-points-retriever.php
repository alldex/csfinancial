<?php
namespace AffiliateLTP;

use AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB;
use AffiliateLTP\Points_Record;
use AffiliateLTP\Commission_Type;

/**
 * Retrieves all of the points data for a specific date range.
 *
 * @author snielson
 */
class Points_Retriever {
    
    /**
     * Affiliate_WP_Referral_Meta_DB
     * @var type 
     */
    private $referralMetaDb;
    
    public function __construct(Affiliate_WP_Referral_Meta_DB $metaDb = null) {
        $this->referralMetaDb = $metaDb;
    }
    
    public function get_points( $affiliate_id, $date_range, $include_super_shop = false ) {
        
        $date  = array(
                'start' => $date_range['start_date'],
                'end'   => $date_range['end_date']
        );
        $difference = ( strtotime( $date['end'] ) - strtotime( $date['start'] ) );

        $referrals = affiliate_wp()->referrals->get_referrals( array(
                'orderby'      => 'date',
                'order'        => 'ASC',
                'date'         => $date,
                'number'       => -1,
                'affiliate_id' => $affiliate_id
        ) );
        
        $paidReferrals = wp_list_filter( $referrals, array( 'status' => 'paid' ) );
        var_dump(count($paidReferrals));
        $pointsData = array();
                
        $lifePointsSum = 0;
        $nonLifePointsSum = 0;

        foreach ( $paidReferrals as $referral ) {

            if ( in_array( $date_range['range'], array( 'this_year', 'last_year' ), true )
                    || $difference >= YEAR_IN_SECONDS
            ) {
                    $date = date( 'Y-m-d', strtotime( $referral->date ) );
            } else {
                    $date = date( 'Y-m-d', strtotime( $referral->date ) );
            }
            
            if (!$include_super_shop
                    && $this->is_referral_in_super_shop($referral)) {
                continue;
            }
            
            $points = absint( $this->referralMetaDb->get_meta($referral->referral_id, 'points', true) );
            $context = absint( $referral->context );

            if (empty($pointsData[$date])) {
                $pointsData[$date] = array();
            }

            if ( Commission_Type::TYPE_LIFE === $context ) {
                $lifePointsSum += $points;
                $pointsData[$date][] = new Points_Record($date, $points, 0);
            }
            else if (Commission_Type::TYPE_NON_LIFE === $context ) {
                $nonLifePointsSum += $points;
                $pointsData[$date][] = new Points_Record($date, 0, $points);
            }
        }

        return $pointsData;
    }
    
    private function is_referral_in_super_shop($referral) {
        $gen_count = $this->referralMetaDb->get_meta($referral->referral_id, 'generation_count', true);
        if (empty($gen_count)) {
            return false;
        }
        // make sure it's a number which it should be, but better to be safe.
        if (absint($gen_count) > 0) {
            return true;
        }
        return false;
    }
}
