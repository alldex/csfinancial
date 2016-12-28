<?php 
class AffiliateLTPCommissionsTable {

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
	}

	public function addTableColumns($columns) {
		$new = array(
			'cb' => $columns['cb']
			,'amount' => $columns['amount']
			,'affiliate' => $columns['affiliate']
			,'reference' => $columns['reference']
			,'description' => $columns['description']
			,'date' => $columns['date']
			,'status' => $columns['status']
			,'points' => __('Points', 'affiliate-ltp')
			,'actions' => $columns['actions']
		);
		return $new;
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