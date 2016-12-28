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
    public $type;

    const TYPE_LIFE = 0;
    const TYPE_NON_LIFE = 1;
    
    public function __construct() {
        $this->agents = array();
        $this->client = null;
        $this->amount = 0;
        $this->type = self::TYPE_NON_LIFE;
    }
}
