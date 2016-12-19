<?php
/**
 * Description of class-referrals
 *
 * @author snielson
 */
class AffiliateLTPReferrals {
    
    /**
     *
     * @var Affiliate_WP_Referral_Meta_DB
     */
    private $referralMetaDb;
    const STATUS_DEFAULT = 'paid';
    
    public function __construct(Affiliate_WP_Referral_Meta_DB $referralMetaDb) {
        remove_action('affwp_add_referral', 'affwp_process_add_referral');
        add_action('affwp_add_referral', array($this, 'processAddReferralRequest'));
        
        add_action( 'wp_ajax_affwp_ltp_search_clients', array($this, 'ajaxSearchClients') );
        
        add_action( 'affwp_delete_referral', array($this, 'cleanupReferralMetadata'), 10, 1 );
        
        // TODO: stephen when dealing with rejecting / overridding commissions uncomment this piece.
        //add_filter( 'affwp_referral_row_actions', array($this, 'disableEditsForOverrideCommissions'), 10, 2);
        
        $this->referralMetaDb = $referralMetaDb;
    }
    
    public function disableEditsForOverrideCommissions($actions, $referral) {
        if (isset($referral) && $referral->custom == 'indirect') {
            $actions = array();
        }
        return $actions;
    }
    public function handleDisplayListReferralsScreen() {
        $referrals_table = new AffWP_Referrals_Table();
        $referrals_table->prepare_items();
        
        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 
                'list', false);
        
        include_once $templatePath;
    }
    
    /**
     * Handles the display of the different admin referral pages.
     */
    public function handleAdminSubMenuPage() {
        // filter our post variables
        $action = filter_input(INPUT_GET, 'action');
        
        if( isset( $action ) && 'add_referral' == $action ) {
            $this->handleDisplayNewReferralScreen();

	} else if( isset( $action ) && 'edit_referral' == $action ) {
            $this->handleDisplayEditReferralScreen();
	} else {
            $this->handleDisplayListReferralsScreen();
        }
    }

    
    /**
     * Handle ajax requests for searching through a list of clients.
     */
    public function ajaxSearchClients() {
        // TODO: stephen would it be better to just make searchAccounts conform
        // to what we return to the client instead of what it's returning now?
        $instance = SugarCRMDAL::instance();
        
        // TODO: stephen have this use the filter_input functions.
        $searchQuery = htmlentities2( trim( $_REQUEST['term'] ) );
        
        $results = $instance->searchAccounts($searchQuery);
        error_log(var_export($results, true));
        $jsonResults = array();
        foreach ($results as $id => $record) {
            $record['label'] = $record['contract_number'] . " - " . $record['name'];
            $record['value'] = $record['contract_number'];
            $record['client_id'] = $id;
            $jsonResults[] = $record;
        }
        
        
        wp_die(json_encode($jsonResults)); // this is required to terminate immediately and return a proper response
    }
    
    public function handleDisplayEditReferralScreen( ) {
        // load up the template.. defaults to our templates/admin-referral-edit.php
        // if no one else has overridden it.
        
        $referral_id = filter_input(INPUT_GET, 'referral_id');
        $referral = affwp_get_referral( absint( $referral_id ) );

        $payout = affwp_get_payout( $referral->payout_id );

        $disabled    = disabled( (bool) $payout, true, false );
        $payout_link = add_query_arg( array(
                'page'      => 'affiliate-wp-payouts',
                'action'    => 'view_payout',
                'payout_id' => $payout ? $payout->ID : 0
        ), admin_url( 'admin.php' ) );
        
        $referralId = $referral->referral_id;
        $agentRate = $this->referralMetaDb->get_meta($referralId, 'agent_rate', true);
        $points = $this->referralMetaDb->get_meta($referralId, 'points', true);
        $clientId = $this->referralMetaDb->get_meta($referralId, 'client_id', true);
        
        if (!empty($clientId)) {
            $instance = SugarCRMDAL::instance();
            $client = $instance->getAccountById($clientId);
        }
        else {
            $client = array(
                "contract_number" => ""
                ,"name" => ""
                ,"street_address" => ""
                ,"city_address" => ""
                ,"zipcode" => ""
                ,"phone" => ""
                ,"email" => ""
            );
        }
        
        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 
                'edit', false);
        
        include_once $templatePath;
        
    }
    
    public function handleDisplayNewReferralScreen( ) {
        // load up the template.. defaults to our templates/admin-referral-edit.php
        // if no one else has overridden it.
        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 
                'new', true);
        
        include_once $templatePath;
    }
    
    public function processAddReferralRequest( $requestData ) {
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
            $referralData = $this->getReferralDataFromRequest( $requestData );
            $data = $this->processCompanyCommission($referralData);
            $clientId = $this->createClient($data['client']);
            $data['client']['id'] = $clientId;
            $this->createReferralHeirarchy($data);
            
            $this->connectCompanyToClient($data, $data['client']);
            
            
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_added' ) );
        }
        catch (\Exception $ex) {
            // TODO: stephen need to log the exception.
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_add_failed' ) );
        }
        exit;
    }
    
    public function cleanupReferralMetadata( $referralId ) {
        // delete all the meta information.
        $this->referralMetaDb->delete_meta($referralId, 'client_id');
        $this->referralMetaDb->delete_meta($referralId, 'client_contract_number');
        $this->referralMetaDb->delete_meta($referralId, 'points');
        $this->referralMetaDb->delete_meta($referralId, 'agent_rate');
    }
    
    /**
     * Do any work to connect the company referral information to the client.
     * @param array $companyData
     * @param array $clientData
     */
    private function connectCompanyToClient($companyData, $clientData) {
        $this->connectReferralToClient($companyData['company_referral_id'], $clientData);
    }
    
    /**
     * Creates the client and returns the id of the client that was created.
     * @param type $clientData
     * @return string
     */
    private function createClient($clientData) {
        // we already have a client id we don't need to create a client here
        if (!empty($clientData['id'])) {
            // we return the id to keep the code flow the same.
            return $clientData['id'];
        }
        // create the client on the sugar CRM system.
        $instance = SugarCRMDAL::instance();
        return $instance->createAccount($clientData);
    }
    
    private function processCompanyCommission( $data ) {
        $companyCommission = affiliate_wp()->settings->get("affwp_ltp_company_rate");
        $companyAgentId = affiliate_wp()->settings->get("affwp_ltp_company_agent_id");
        
        // if we have no company agent
        if (empty($companyAgentId)) {
            return $data;
        }
        
        // make the commission 0 if we don't have anything here so that we get
        // a line item here.
        if (empty($companyCommission)) {
            $companyCommission = 0;
        }
        else {
            $companyCommission = absint($companyCommission) / 100;
        }
        
        $amount = $data[ 'amount' ];
        $companyAmount = round($companyCommission * $amount, 2);
        $amountRemaining = $amount - $companyAmount;
        
        // create the records for the company commission
        
        $data['amount'] = $amountRemaining;
        $data['points'] = $amount;
        
        // Process cart and get amount
        $companyData = array(); // copy the array
        $companyData['affiliate_id'] = absint($companyAgentId);
        $companyData['description']  = __("Override", "affiliate-ltp");
        $companyData['reference'] = $data['reference'];
        $companyData['amount']       = $companyAmount;
        $companyData['custom']       = 'indirect';
        $companyData['context']      = 'ltp-commission';
        $companyData['status']       = self::STATUS_DEFAULT;
        $companyData['points']       = $amount; // the original amount is the points we track.
        
        // create referral
        $referral_id = affiliate_wp()->referrals->add( $companyData );
        if (empty($referral_id)) {
            error_log("Failed to calculate company commission.  Data array: "
                    . var_export($companyData, true));
        }
        else {
            $data['company_referral_id'] = $referral_id;
            $this->addReferralMeta($referral_id, $companyCommission, $data['points']);
        }
        
        return $data;
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
        
        $client_args = array (
            'id'      => ! empty( $data['client_id'] ) ? sanitize_text_field( $data['client_id'] ) : null,
            'contract_number' => ! empty( $data['client_contract_number'] ) ? sanitize_text_field( $data['client_contract_number'] ) : null,
            'name'    => ! empty( $data['client_name'] ) ? sanitize_text_field( $data['client_name'] ) : '',
            'street_address' => ! empty( $data['client_street_address'] ) ? sanitize_text_field( $data['client_street_address'] ) : '',
            'city' => ! empty( $data['client_city_address'] ) ? sanitize_text_field( $data['client_city_address'] ) : '',
            'country' => 'USA', // TODO: stephen extract this to a setting or constant.
            'zip' => ! empty( $data['client_zip_address'] ) ? sanitize_text_field( $data['client_zip_address'] ) : '',
            'phone'   => ! empty( $data['client_phone'] ) ? sanitize_text_field( $data['client_phone'] ) : '',
            'email'   => ! empty( $data['client_email'] ) ? sanitize_text_field( $data['client_email'] ) : '',
        );
        
	$args = array(
		'affiliate_id' => absint( $data['affiliate_id'] ),
		'amount'       => ! empty( $data['amount'] )      ? sanitize_text_field( $data['amount'] )      : '',
                'client'       => $client_args
	);
        $args['reference'] = $client_args['contract_number'];
       
	if ( ! empty( $data['date'] ) ) {
		$args['date'] = date_i18n( 'Y-m-d H:i:s', strtotime( $data['date'] ) );
	}
        
        return $args;
    }
    
    private function createReferral($affiliateId, $amount, $reference, $directAffiliate, $levelCount = 0,
            $paymentRate = 0, $points = 0) {

        $custom = 'direct';
        $description = __("Personal sale", "affiliate-ltp");
        if (!$directAffiliate) {
            $custom = 'indirect';
            $description = __("Override", "affiliate-ltp");
        }
        // Process cart and get amount
        $data = array();
        $data['affiliate_id'] = $affiliateId;
        $data['description']  = $description;
        $data['amount']       = $amount;
        $data['reference']    = $reference;
        $data['custom']       = $custom; // Add referral type as custom referral data
        $data['context']      = 'ltp-commission';
        $data['status']       = self::STATUS_DEFAULT;
        
        
        // create referral
        $referral_id = affiliate_wp()->referrals->add( $data );

        if ( $referral_id ) {
            $this->addReferralMeta($referral_id, $paymentRate, $points);
            
            // TODO: stephen not sure this is needed anymore... or if it is pass the array of data.
            do_action( 'affwp_ltp_referral_created', $referral_id, $description, $amount, $reference, $custom, $context, $status);
            return $referral_id;
        }
        else {
            // TODO: stephen add more details here.
            throw new \Exception("Failed to create referral id");
        }
    }
    
    private function addReferralMeta($referralId, $paymentRate, $points) {
        // TODO: stephen should we refactor so we add in the client info here?
        $this->referralMetaDb->add_meta($referralId, 'agent_rate', $paymentRate);
        $this->referralMetaDb->add_meta($referralId, 'points', $points);
    }
    
    function createReferralHeirarchy($referralData) {
        $directAffiliateId = $referralData['affiliate_id'];
        $amount = $referralData['amount'];
        $reference = $referralData['client']['contract_number'];
        $points = $referralData['points'];
        
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
            $referralId = $this->createReferral($affiliateId, $adjustedAmount, $reference, 
                    $directAffiliate, $levelCount, $adjustedRate, $points);
            $this->connectReferralToClient($referralId, $referralData['client']);
            
        } while (!empty($affiliates));
    }
    
    private function connectReferralToClient($referralId, $clientData) {
        // add the connection for the client.
        $this->referralMetaDb->add_meta($referralId, 'client_id', $clientData['id']);
        $this->referralMetaDb->add_meta($referralId, 'client_contract_number', $clientData['contract_number']);
    }
}
