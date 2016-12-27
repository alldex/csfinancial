<?php

require_once 'class-referrals-new-request-builder.php';

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

        add_action('wp_ajax_affwp_ltp_search_clients', array($this, 'ajaxSearchClients'));

        add_action('affwp_delete_referral', array($this, 'cleanupReferralMetadata'), 10, 1);

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

        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 'list', false);

        include_once $templatePath;
    }

    /**
     * Handles the display of the different admin referral pages.
     */
    public function handleAdminSubMenuPage() {
        // filter our post variables
        $action = filter_input(INPUT_GET, 'action');

        if (isset($action) && 'add_referral' == $action) {
            $this->handleDisplayNewReferralScreen();
        } else if (isset($action) && 'edit_referral' == $action) {
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
        $instance = AffiliateLTP::instance()->getSugarCRM();

        // TODO: stephen have this use the filter_input functions.
        $searchQuery = htmlentities2(trim($_REQUEST['term']));

        $results = $instance->searchAccounts($searchQuery);
        
        $jsonResults = array();
        foreach ($results as $id => $record) {
            $record['label'] = $record['contract_number'] . " - " . $record['name'];
            $record['value'] = $record['contract_number'];
            $record['client_id'] = $id;
            $jsonResults[] = $record;
        }


        wp_die(json_encode($jsonResults)); // this is required to terminate immediately and return a proper response
    }

    public function handleDisplayEditReferralScreen() {
        // load up the template.. defaults to our templates/admin-referral-edit.php
        // if no one else has overridden it.

        $referral_id = filter_input(INPUT_GET, 'referral_id');
        $referral = affwp_get_referral(absint($referral_id));

        $payout = affwp_get_payout($referral->payout_id);

        $disabled = disabled((bool) $payout, true, false);
        $payout_link = add_query_arg(array(
            'page' => 'affiliate-wp-payouts',
            'action' => 'view_payout',
            'payout_id' => $payout ? $payout->ID : 0
                ), admin_url('admin.php'));

        $referralId = $referral->referral_id;
        $agentRate = $this->referralMetaDb->get_meta($referralId, 'agent_rate', true);
        $points = $this->referralMetaDb->get_meta($referralId, 'points', true);
        $clientId = $this->referralMetaDb->get_meta($referralId, 'client_id', true);

        if (!empty($clientId)) {
            $instance = AffiliateLTP::instance()->getSugarCRM();
            $client = $instance->getAccountById($clientId);
        } else {
            $client = array(
                "contract_number" => ""
                , "name" => ""
                , "street_address" => ""
                , "city_address" => ""
                , "zipcode" => ""
                , "phone" => ""
                , "email" => ""
            );
        }

        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 'edit', false);

        include_once $templatePath;
    }

    public function handleDisplayNewReferralScreen() {
        // load up the template.. defaults to our templates/admin-referral-edit.php
        // if no one else has overridden it.
        $templatePath = affiliate_wp()->templates->get_template_part('admin-referral', 'new', true);

        include_once $templatePath;
    }

    public function processAddReferralRequest($requestData) {
        if (!is_admin()) {
            return false;
        }

        if (!current_user_can('manage_referrals')) {
            wp_die(__('You do not have permission to manage referrals', 'affiliate-wp'), __('Error', 'affiliate-wp'), array('response' => 403));
        }

        if (!wp_verify_nonce($requestData['affwp_add_referral_nonce'], 'affwp_add_referral_nonce')) {
            wp_die(__('Security check failed', 'affiliate-wp'), array('response' => 403));
        }

        try {
            $request = AffiliateLTPReferralsNewRequestBuilder::build($requestData);
            $this->processCompanyCommission($request);
            $request->client['id'] = $this->createClient($request->client);
            $this->processAgentSplits($request);
            $this->connectCompanyToClient($request, $request->client);

            wp_safe_redirect(admin_url('admin.php?page=affiliate-wp-referrals&affwp_notice=referral_added'));
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
            var_dump($ex->getTraceAsString());
            exit;
            // TODO: stephen need to log the exception.
            wp_safe_redirect(admin_url('admin.php?page=affiliate-wp-referrals&affwp_notice=referral_add_failed'));
        }
        exit;
    }

    public function cleanupReferralMetadata($referralId) {
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
        $this->connectReferralToClient($companyData->company_referral_id, $clientData);
    }

    /**
     * Creates the client and returns the id of the client that was created.
     * @param array $clientData
     * @return string
     */
    private function createClient($clientData) {
        // we already have a client id we don't need to create a client here
        if (!empty($clientData['id'])) {
            // we return the id to keep the code flow the same.
            return $clientData['id'];
        }
        // create the client on the sugar CRM system.
        $instance = AffiliateLTP::instance()->getSugarCRM();
        return $instance->createAccount($clientData);
    }

    private function processCompanyCommission(AffiliateLTPReferralsNewRequest $request) {
        $companyCommission = affiliate_wp()->settings->get("affwp_ltp_company_rate");
        $companyAgentId = affiliate_wp()->settings->get("affwp_ltp_company_agent_id");

        // if we have no company agent
        if (empty($companyAgentId)) {
            return $request;
        }

        // make the commission 0 if we don't have anything here so that we get
        // a line item here.
        if (empty($companyCommission)) {
            $companyCommission = 0;
        } else {
            $companyCommission = absint($companyCommission) / 100;
        }

        $amount = $request->amount;
        $companyAmount = round($companyCommission * $amount, 2);
        $amountRemaining = $amount - $companyAmount;

        // create the records for the company commission

        $request->amount = $amountRemaining;
        $request->points = $amount;

        // Process cart and get amount
        $companyReferral = array(
            "affiliate_id" => absint($companyAgentId)
            , "description" => __("Override", "affiliate-ltp")
            , "reference" => $request->client['contract_number']
            , "amount" => $companyAmount
            , "custom" => "indirect"
            , "context" => "ltp-commission"
            , "status" => self::STATUS_DEFAULT
            , "date" => $request->date
        );


        // create referral
        $referral_id = affiliate_wp()->referrals->add($companyReferral);
        if (empty($referral_id)) {
            error_log("Failed to calculate company commission.  Data array: "
                    . var_export($companyReferral, true));
        } else {
            $request->company_referral_id = $referral_id;
            $this->addReferralMeta($referral_id, $companyCommission, $request->points);
        }

        return $request;
    }

    private function createReferral($affiliateId, $amount, $reference, $directAffiliate, $paymentRate, $points, $date) {

        $custom = 'direct';
        $description = __("Personal sale", "affiliate-ltp");
        if (!$directAffiliate) {
            $custom = 'indirect';
            $description = __("Override", "affiliate-ltp");
        }
        // Process cart and get amount
        $referral = array(
            "affiliate_id" => $affiliateId
            , "description" => $description
            , "amount" => $amount
            , "reference" => $reference
            , "custom" => $custom
            , "context" => "ltp-commission"
            , "status" => self::STATUS_DEFAULT
            , "date" => $date
        );

        // create referral
        $referral_id = affiliate_wp()->referrals->add($referral);

        if ($referral_id) {
            $this->addReferralMeta($referral_id, $paymentRate, $points);

            // TODO: stephen not sure this is needed anymore... or if it is pass the array of data.
            do_action('affwp_ltp_referral_created', $referral_id, $description, $amount, $reference, $custom, $context, $status);
            return $referral_id;
        } else {
            // TODO: stephen add more details here.
            throw new \Exception("Failed to create referral id for referral data: " . var_export($referral, true));
        }
    }

    private function addReferralMeta($referralId, $paymentRate, $points) {
        // TODO: stephen should we refactor so we add in the client info here?
        $this->referralMetaDb->add_meta($referralId, 'agent_rate', $paymentRate);
        $this->referralMetaDb->add_meta($referralId, 'points', $points);
    }

    private function processAgentSplits(AffiliateLTPReferralsNewRequest $request) {
        foreach ($request->agents as $agent) {
            $currentAmount = $request->amount;
            $splitPercent = $agent->split / 100;
            $computedAmount = $request->amount * $splitPercent;
            $request->amount = $computedAmount;
            $this->createReferralHeirarchy($agent->id, $computedAmount, $request);
            $request->amount = $currentAmount; // keep amount at the same to prevent modification lower down.
        }
    }

    function createReferralHeirarchy($agentId, $amount, $referralData) {
        $reference = $referralData->client['contract_number'];
        $points = $referralData->points;

        $upline = affwp_mlm_get_upline($agentId);
        if ($upline) {
            $upline = affwp_mlm_filter_by_status($upline);
        }

        $affiliates = array_merge(array($agentId), $upline);
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
            $referralId = $this->createReferral($affiliateId, $adjustedAmount, $reference, $directAffiliate, $adjustedRate, $points, $referralData->date);
            $this->connectReferralToClient($referralId, $referralData->client);
        } while (!empty($affiliates));
    }

    private function connectReferralToClient($referralId, $clientData) {
        // add the connection for the client.
        $this->referralMetaDb->add_meta($referralId, 'client_id', $clientData['id']);
        $this->referralMetaDb->add_meta($referralId, 'client_contract_number', $clientData['contract_number']);
    }

}
