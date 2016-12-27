<?php

require_once 'class-referrals-agent-request.php';

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
    
    public function __construct() {
        $this->agents = array();
        $this->client = null;
        $this->amount = 0;
    }
}
