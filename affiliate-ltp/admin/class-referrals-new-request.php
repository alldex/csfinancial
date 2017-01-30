<?php
namespace AffiliateLTP\admin;

require_once 'class-referrals-agent-request.php';

use AffiliateLTP\CommissionType;

/**
 * Holds all of the settings for a new referral.
 * TODO: stephen rename this class to keep with the commissions nomenclature.
 * @author snielson
 */
class Referrals_New_Request {
    public $client;
    public $agents;
    public $amount;
    public $date;
    public $points;
    // TODO: stephen look at removing company_referral_id;
    public $company_referral_id;
    public $type;
    
    /**
     * Whether the company haircut should be taken off or not.
     * @var boolean
     */
    public $skipCompanyHaircut;
    
    /**
     * Whether all of the commission should go to the company haircut.
     * @var boolean
     */
    public $companyHaircutAll;

    public function __construct() {
        $this->agents = array();
        $this->client = null;
        $this->amount = 0;
        $this->type = CommissionType::TYPE_NON_LIFE;
        $this->skipCompanyHaircut = false;
        $this->companyHaircutAll = false;
    }
}
