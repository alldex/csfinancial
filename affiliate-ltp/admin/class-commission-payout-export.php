<?php
//require_once '../affiliate-wp/includes/admin/tools/export/class-export-referrals-payout.php';
require_once AFFILIATEWP_PLUGIN_DIR . '/includes/admin/add-ons.php';

class AffiliateLTPCommissionPayoutExport extends Affiliate_WP_Referral_Payout_Export {

    /**
     * Let people set what commission type they want to filter on.
     * @var type 
     */
    public $commissionType;
    
    /**
     *
     * @var Affiliate_WP_Referral_Meta_DB 
     */
    private $referralsByAffiliateId;
    
    public function __construct(Affiliate_WP_Referral_Meta_DB $referralMetaDb) {
       $this->commissionType = AffiliateLTPCommissionType::TYPE_LIFE;
       $this->referralMetaDb = $referralMetaDb;
       
        parent::__construct();
        
        add_filter( 'affwp_export_get_data_' . $this->export_type, array($this, 'addExtraData'), 10, 1);
    }
    
    public function addExtraData( $exportData ) {
        $newData = array();
        foreach ($exportData as $affiliateId => $row) {
            $userId = affwp_get_affiliate_user_id($affiliateId);
            $userData = get_userdata( $userId );
            
            $description = $this->getDescriptionForAffiliate($affiliateId);
            
            $newData[$affiliateId] = array(
                "first_name" => $userData->first_name
                ,"last_name" => $userData->last_name
                ,"business_name" => "" // we don't know what goes here for now.
                ,"email" => $row['email']
                ,"amount" => $row['amount']
                ,'currency' => $row['currency']
                ,'description' => $description
            );
        }
        return $newData;
        
    }
    
        
    private function getDescriptionForAffiliate( $affiliateId ) {
        
        $contractNumbers = array();
        if (array_key_exists($affiliateId, $this->referralsByAffiliateId)) {
            $referrals = $this->referralsByAffiliateId[$affiliateId];
            foreach ($referrals as $referral) {
                // it looks like we don't need to get additional meta information here for now
                $contractNumbers[] = $referral->reference;
            }
        }
        return sprintf(__("Contract numbers: %s", 'affiliate-ltp'), join(",", $contractNumbers));
    }
        

    /**
     * Override the get_referrals to filter comissions by the context so we can
     * return life vs non-life referrals.
     * @param type $args
     * @return type
     */
    public function get_referrals_for_export( $args = array() ) {
        $args = wp_parse_args( $args, array(
			'status' => 'unpaid',
			'date'   => ! empty( $this->date ) ? $this->date : '',
			'number' => -1
                        ,'context' => $this->commissionType
		) );
        
        $referrals = affiliate_wp()->referrals->get_referrals( $args );
        
        $referralsByAffiliateId = array();
        foreach ($referrals as $referral) {
            if (!array_key_exists($referral->affiliate_id, $referralsByAffiliateId)) {
                $referralsByAffiliateId[$referral->affiliate_id] = array();
            }
            $referralsByAffiliateId[$referral->affiliate_id][] = $referral;
        }
        $this->referralsByAffiliateId = $referralsByAffiliateId;

        return $referrals;
    }
    
    public function csv_cols() {
        $cols = array(
                'first_name'    => __('First Name', 'affiliate-ltp' ),
                'last_name'    => __('Last Name', 'affiliate-ltp' ),
                'business_name'    => __('Business Name', 'affiliate-ltp' ),
                'email'         => __( 'Email', 'affiliate-wp' ),
                'amount'        => __( 'Amount', 'affiliate-wp' ),
                'currency'      => __( 'Currency', 'affiliate-wp' ),
                'description'    => __('Description', 'affiliate-ltp' ),
        );
        return $cols;
    }
}