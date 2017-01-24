<?php 
namespace AffiliateLTP\admin;

use AffiliateLTP\CommissionType;
use \Affiliate_WP_Referral_Meta_DB;

class Commissions_Table {

    /**
     * @var Affiliate_WP_Referral_Meta_DB
     */
    private $referralMetaDb;

    /**
	 * @param  Affiliate_WP_Referral_Meta_DB $referralMetaDb The meta database for commissions
	 */
	public function __construct(  Affiliate_WP_Referral_Meta_DB $referralMetaDb ) {
		$this->referralMetaDb = $referralMetaDb;
		add_filter( 'affwp_referral_table_columns', array($this, 'addTableColumns' ) );
		add_filter( 'affwp_referral_table_points', array($this, 'columnPoints'), 10, 2);
		add_filter( 'affwp_referral_table_type', array($this, 'columnType'), 10, 2);
	}

	public function addTableColumns($columns) {
		$new = array(
			'cb' => $columns['cb']
			,'amount' => $columns['amount']
			,'affiliate' => $columns['affiliate']
			,'type' => __('Type', 'affiliate-ltp')
			,'reference' => $columns['reference']
			,'description' => $columns['description']
			,'date' => $columns['date']
			,'status' => $columns['status']
			,'points' => __('Points', 'affiliate-ltp')
			,'actions' => $columns['actions']
		);
		return $new;
	}

	public function columnType($value, $referral) {
		$value = $referral->context;
		if ($value == CommissionType::TYPE_LIFE) {
			return __('Life Insurance', 'affiliate-ltp');
		}
		else {
			return __('Non-Life Insurance', 'affiliate-ltp');
		}
	}

	public function columnPoints($value, $referral) {
		$referralId = $referral->referral_id;

		$value = $this->referralMetaDb->get_meta($referralId, 'points', true);
        if (empty($value)) {
			return '';
		}
		return $value;
	}
}