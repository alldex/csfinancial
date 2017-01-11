<?php

require_once 'class-referrals-new-request-builder.php';
require_once 'class-commissions-table.php';
require_once 'class-commission-dal.php';
require_once 'class-commission-dal-affiliate-wp-adapter.php';
require_once 'class-agent-dal.php';
require_once 'class-agent-dal-affiliate-wp-adapter.php';

/**
 * Description of class-referrals
 *
 * @author snielson
 */
class AffiliateLTPReferrals {

    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    /**
     *
     * @var Affiliate_WP_Referral_Meta_DB
     */
    private $referralMetaDb;

    private $referralsTable;

    const STATUS_DEFAULT = 'unpaid';

    public function __construct(Affiliate_WP_Referral_Meta_DB $referralMetaDb) {
        remove_action('affwp_add_referral', 'affwp_process_add_referral');
        add_action('affwp_add_referral', array($this, 'processAddReferralRequest'));

        add_action('wp_ajax_affwp_ltp_search_clients', array($this, 'ajaxSearchClients'));

        add_action('affwp_delete_referral', array($this, 'cleanupReferralMetadata'), 10, 1);

        add_action('affwp_generate_commission_payout', array($this, 'generateCommissionPayoutFile') );

        // TODO: stephen when dealing with rejecting / overridding commissions uncomment this piece.
        //add_filter( 'affwp_referral_row_actions', array($this, 'disableEditsForOverrideCommissions'), 10, 2);

        $this->referralMetaDb = $referralMetaDb;
        $this->referralsTable = new AffiliateLTPCommissionsTable($this->referralMetaDb);
        $this->commission_dal = new AffiliateLTP\admin\Commission_Dal_Affiliate_WP_Adapter($referralMetaDb);
        $this->agent_dal = new AffiliateLTP\admin\Agent_DAL_Affiliate_WP_Adapter();
    }

    public function generateCommissionPayoutFile( $data ) {
        require_once 'class-commission-payout-export.php';

        
        $export = new AffiliateLTPCommissionPayoutExport($this->referralMetaDb);
        if (isset($data['is_life_commission'])) {
            $export->commissionType = AffiliateLTPCommissionType::TYPE_LIFE;
        }
        else {
            $export->commissionType = AffiliateLTPCommissionType::TYPE_NON_LIFE;
        }
        
        $export->date = array(
            'start' => $data['from'],
            'end'   => $data['to'] . ' 23:59:59'
        );
        $export->export();
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
        $referral = $this->commission_dal->get_commission( absint( $referral_id ) );

        $payout = $this->commission_dal->get_commission_payout( $referral->payout_id );

        $disabled = disabled((bool) $payout, true, false);
        $payout_link = add_query_arg(array(
            'page' => 'affiliate-wp-payouts',
            'action' => 'view_payout',
            'payout_id' => $payout ? $payout->ID : 0
                ), admin_url('admin.php'));

        $referralId = $referral->referral_id;
        $agentRate = $this->commission_dal->get_commission_agent_rate($referralId);
        $points = $this->commission_dal->get_commission_agent_points($referralId);
        $clientId = $this->commission_dal->get_commission_client_id($referralId);
        
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
            error_log($ex->getMessage() . "\nTrace: " . $ex->getTraceAsString());
            $message = urlencode("A server error occurred and we could not process the request.  Check the server logs for more details");
            wp_safe_redirect(admin_url('admin.php?page=affiliate-wp-referrals&affwp_notice=referral_add_failed&affwp_message=' . $message));
        }
        exit;
    }

    public function cleanupReferralMetadata( $referralId ) {
        // delete all the meta information.
        $this->commission_dal->delete_commission_meta_all( $referralId );
    }

    /**
     * Do any work to connect the company referral information to the client.
     * @param array $companyData
     * @param array $clientData
     */
    private function connectCompanyToClient($companyData, $clientData) {
        $this->commission_dal->connect_commission_to_client( $companyData->company_referral_id, $clientData );
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
        
        // do nothing here if we are to skip the company commissions.
        if ($request->skipCompanyHaircut) {
            return;
        }
        
        $companyCommission = affiliate_wp()->settings->get("affwp_ltp_company_rate");
        $companyAgentId = affiliate_wp()->settings->get("affwp_ltp_company_agent_id");
        
        // if the company is taking everything we set the commission to be 100%
        if ($request->companyHaircutAll) {
            $companyCommission = 100;
        }

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
        
        // if we are not a life we will use the points after the company
        // 'haircut' or percentage they took out.
        if ($request->type != AffiliateLTPCommissionType::TYPE_LIFE) {
            $request->points = $amountRemaining;
        }

        // Process cart and get amount
        $companyReferral = array(
            "affiliate_id" => absint($companyAgentId)
            , "description" => __("Override", "affiliate-ltp")
            , "reference" => $request->client['contract_number']
            , "amount" => $companyAmount
            , "custom" => "indirect"
            , "context" => $request->type
            , "status" => self::STATUS_DEFAULT
            , "date" => $request->date
        );


        // create referral
        $referral_id = $this->commission_dal->add_commission( $companyReferral );
        if (empty($referral_id)) {
            error_log("Failed to calculate company commission.  Data array: "
                    . var_export($companyReferral, true));
        } else {
            $request->company_referral_id = $referral_id;
            $this->addReferralMeta($referral_id, $companyCommission, $request->points);
        }

        return $request;
    }

    private function createReferral($affiliateId, $amount, $reference, $directAffiliate, $paymentRate, $points, $date, $type) {

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
            , "context" => $type
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
        $this->commission_dal->add_commission_meta($referralId, 'agent_rate', $paymentRate);
        $this->commission_dal->add_commission_meta($referralId, 'points', $points);
    }

    private function processAgentSplits(AffiliateLTPReferralsNewRequest $request) {
        // if the company is taking everything we don't process anything for other
        // agents
        if ($request->companyHaircutAll) {
            return;
        }
        
        foreach ($request->agents as $agent) {
            $currentAmount = $request->amount;
            $splitPercent = $agent->split / 100;
            $computedAmount = $request->amount * $splitPercent;
            $request->amount = $computedAmount;
            $this->createReferralHeirarchy($agent->id, $computedAmount, $request);
            $request->amount = $currentAmount; // keep amount at the same to prevent modification lower down.
        }
    }

    private function createReferralHeirarchy($agentId, $amount, $referralData) {
        $reference = $referralData->client['contract_number'];
        $points = $referralData->points;

        $upline = $this->agent_dal->get_agent_upline( $agentId );
        if ($upline) {
            $upline = $this->agent_dal->filter_agents_by_status($upline, 'active');
        }

        $affiliates = array_merge(array($agentId), $upline);
        
        if ($referralData->type == AffiliateLTPCommissionType::TYPE_LIFE) {
            $affiliates = $this->agent_dal->filter_agents_by_licensed_life_agents( $affiliates );
        }
        // if there are no licensed agents
        // TODO: stephen if there are no licensed agents for this commission how do we handle that?
        if (empty($affiliates)) {
            return;
        }
        
        $levelCount = 0;
        $priorAffiliateRate = 0;


        do {
            $affiliateId = array_shift($affiliates);
            $levelCount++;
            $affiliateRate = $this->agent_dal->get_agent_commission_rate( $affiliateId );

            $adjustedRate = ($affiliateRate > $priorAffiliateRate) ? $affiliateRate - $priorAffiliateRate : 0;
            $adjustedAmount = $amount * $adjustedRate;
            $priorAffiliateRate = $affiliateRate;

            $directAffiliate = ($levelCount === 1);
            $referralId = $this->createReferral($affiliateId, $adjustedAmount, $reference, $directAffiliate, $adjustedRate, $points, $referralData->date, $referralData->type);
            $this->connectReferralToClient($referralId, $referralData->client);
        } while (!empty($affiliates));
    }
    
    private function connectReferralToClient($referral_id, $client_data) {
        $this->commission_dal->connect_commission_to_client( $referral_id, $client_data );
    }
}
