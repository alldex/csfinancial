<?php
/**
 * Description of class-referrals
 *
 * @author snielson
 */
class AffiliateLTPReferrals {
    
    public function __construct() {
        remove_action('affwp_add_referral', 'affwp_process_add_referral');
        add_action('affwp_add_referral', array($this, 'processAddReferralRequest'));
        
        add_action( 'affwp_new_referral_bottom', array($this, 'addNewReferralClientFields'), 10, 1);
        add_action( 'affwp_edit_referral_bottom', array($this, 'addEditReferralClientFields'), 10, 1);
        
        add_action( 'wp_ajax_affwp_ltp_search_clients', array($this, 'ajaxSearchClients') );
    }
    
    /**
     * Handle ajax requests for searching through a list of clients.
     */
    public function ajaxSearchClients() {
        // TODO: stephen would it be better to just make searchAccounts conform
        // to what we return to the client instead of what it's returning now?
        $instance = SugarCRMDAL::instance();
        
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
    
    public function addEditReferralClientFields( $referral ) {
        // load up the template.. defaults to our templates/admin-referral-edit.php
        // if no one else has overridden it.
        $client = array(
            "name" => "John"
            ,"street_address" => "105S 3rd E"
            ,"city_address" => "Rexburg"
            ,"zipcode" => "83440"
            ,"phone" => "801-610-9014"
            ,"email" => "stephen@nielson.org"
        );
        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 
                'edit', false);
        
        include_once $templatePath;
        
    }
    
    public function addNewReferralClientFields( $referral ) {
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
            $this->createReferralHeirarchy($data['affiliate_id'], $data['amount'], 
                    $data['context'], $data['reference'], $data['description'], $data['status']);
            
            $this->createClient($referralData['client']);
            
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_added' ) );
        }
        catch (\Exception $ex) {
            // TODO: stephen need to log the exception.
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals&affwp_notice=referral_add_failed' ) );
        }
        exit;
    }
    
    private function createClient($clientData) {
        // we already have a client id we don't need to create a client here
        if (!empty($clientData['id'])) {
            // TODO: stephen add the connection between the referrals and the
            // clients here.
            return;
        }
        // create the client on the sugar CRM system.
        $instance = SugarCRMDAL::instance();
        $instance->createAccount($clientData);
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
        
        
        // Process cart and get amount
        $companyData = array();
        $companyData['affiliate_id'] = absint($companyAgentId);
        $companyData['description']  = "Company commission for " . $data['reference'];
        $companyData['amount']       = $companyAmount;
        $companyData['reference']    = $data['reference'];
        $companyData['custom']       = 'indirect';
        $companyData['context']      = 'ltp-commission';
        $companyData['status']       = 'paid';
        
        // create referral
        $referral_id = affiliate_wp()->referrals->add( $companyData );
        if (empty($referral_id)) {
            error_log("Failed to calculate company commission.  Data array: "
                    . var_export($companyData, true));
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
		'description'  => ! empty( $data['description'] ) ? sanitize_text_field( $data['description'] ) : '',
		'reference'    => ! empty( $data['reference'] )   ? sanitize_text_field( $data['reference'] )   : '',
		'context'      => ! empty( $data['context'] )     ? sanitize_text_field( $data['context'] )     : '',
		'status'       => 'paid',
                'client'       => $client_args
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
            do_action( 'affwp_ltp_referral_created', $referral_id, $description, $amount, $reference, $custom, $context, $status);
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
