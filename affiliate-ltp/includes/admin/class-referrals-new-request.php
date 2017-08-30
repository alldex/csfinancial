<?php
namespace AffiliateLTP\admin;

use AffiliateLTP\admin\Referrals_Agent_Request;

use AffiliateLTP\Commission_Type;

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
    
    /**
     * Wheather to skip the check to verify that the agents have a valid
     * life insurance policy when dealing with a life insurance commission.
     * @var boolean
     */
    public $skip_life_licensed_check;
    
    /**
     * Whether the request is for new business or not.
     * @var boolean
     */
    public $new_business;
    
    /**
     * Whether the request is a renewal that only goes to partner's or not.
     * @var boolean
     */
    public $renewal;
    
    /**
     * The percentage of the company haircut.
     * @var integer
     */
    public $companyHaircutPercent;

    public function __construct() {
        $this->agents = array();
        $this->client = null;
        $this->amount = 0;
        $this->type = Commission_Type::TYPE_NON_LIFE;
        $this->skipCompanyHaircut = false;
        $this->companyHaircutAll = false;
        $this->skip_life_licensed_check = false;
        $this->new_business = true;
    }
}
