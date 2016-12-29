<?php

require_once 'class-referrals-agent-request.php';
require_once 'class-commission-type.php';

/**
 * Description of class-referrals-new-request
 *
 * @author snielson
 */
class AffiliateLTPReferralsNewRequest {
    public $client;
    public $agents;
    public $amount;
    public $date;
    public $points;
    public $company_referral_id;
    public $type;

    public function __construct() {
        $this->agents = array();
        $this->client = null;
        $this->amount = 0;
        $this->type = AffiliateLTPCommissionType::TYPE_NON_LIFE;
    }
}
