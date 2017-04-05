<?php
namespace AffiliateLTP\admin;

use \Affiliate_WP_Referral_Payout_Export;
use AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB;
use AffiliateLTP\Commission_Type;

//require_once '../affiliate-wp/includes/admin/tools/export/class-export-referrals-payout.php';
require_once AFFILIATEWP_PLUGIN_DIR . '/includes/admin/add-ons.php';

class Commission_Payout_Export extends Affiliate_WP_Referral_Payout_Export {

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
    
    /**
     *
     * @var Affiliate_WP_Referral_Meta_DB
     */
    private $referralMetaDb;
    
    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;
    
    public function __construct(Affiliate_WP_Referral_Meta_DB $referralMetaDb, Settings_DAL $settings_dal) {
       $this->commissionType = Commission_Type::TYPE_LIFE;
       $this->referralMetaDb = $referralMetaDb;
       $this->settings_dal = $settings_dal;
        parent::__construct();
        
        add_filter( 'affwp_export_get_data_' . $this->export_type, array($this, 'addExtraData'), 10, 1);
        add_filter( 'affwp_export_get_data_' . $this->export_type, array($this, 'remove_company_agent'), 20, 1);
    }
    
    /**
     * We make sure that no matter what we don't pay the company by removing it
     * from the list.
     * @param array $export_data
     * @return array
     */
    public function remove_company_agent( $export_data ) {
        $company_agent_id = absint($this->settings_dal->get_company_agent_id());
        
        if (empty($company_agent_id)) {
            return;
        }
        
        $new_data = [];
        foreach ($export_data as $agent_id => $row) {
//            var_dump("$company_agent_id === " . absint($agent_id));
            if ($company_agent_id !== absint($agent_id)) {
                $new_data[] = $row;
            }
        }
//        exit;
        
        return $new_data;
    }
    
    // TODO: rename this funtion to conform with naming standards
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
//                ,'currency' => $row['currency'] // leaving this in for historical reasons.
                ,"check_no" => "" // make the currency empty
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
                'check_no'      => __( 'Check No', 'affiliate-ltp' ),
                'description'    => __('Description', 'affiliate-ltp' ),
        );
        return $cols;
    }
}