<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AffiliateLTP\admin;

require_once 'class-commission-status.php';

/**
 * Description of class-commission-company-processor
 *
 * @author snielson
 */
class Commission_Company_Processor {
    
    
    /**
     *
     * @var Commission_DAL 
     */
    private $commission_dal;
    
    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;
    
    public function __construct(Commission_DAL $commission_dal, Settings_DAL $settings_dal) {
        $this->commission_dal = $commission_dal;
        $this->settings_dal = $settings_dal;
    }
    
    public function create_company_commission(\AffiliateLTPReferralsNewRequest $orig_request) {
        
        $new_request = clone $orig_request;
        // do nothing here if we are to skip the company commissions.
        if ($new_request->skipCompanyHaircut) {
            return $new_request;
        }
        
        // TODO: stephen need to move these to our own class so we can abstract it.
        $company_commission_rate = $this->settings_dal->get_company_rate();
        $company_agent_id = $this->settings_dal->get_company_agent_id();
        
        // if the company is taking everything we set the commission to be 100%
        if ($new_request->companyHaircutAll) {
            $company_commission_rate = 100;
        }

        // if we have no company agent
        if (empty($company_agent_id)) {
            return $new_request;
        }

        // make the commission 0 if we don't have anything here so that we get
        // a line item here.
        if (empty($company_commission_rate)) {
            $company_commission_rate = 0;
        } else {
            $company_commission_rate = round(absint($company_commission_rate) / 100, 2);
        }

        $amount = $new_request->amount;
        $company_amount = round($company_commission_rate * $amount, 2);
        $amount_remaining = $amount - $company_amount;

        // create the records for the company commission

        $new_request->amount = $amount_remaining;
        
        // if we are not a life we will use the points after the company
        // 'haircut' or percentage they took out.
        if ($new_request->type != \AffiliateLTPCommissionType::TYPE_LIFE) {
            $new_request->points = $amount_remaining;
        }

        // Process cart and get amount
        $company_commission = array(
            // TODO: stephen we need to change this to be agent_id to be consistent in terminology...
            "affiliate_id" => absint($company_agent_id)
            , "description" => __("Override", "affiliate-ltp")
            , "reference" => $new_request->client['contract_number']
            , "amount" => $company_amount
            , "custom" => "indirect"
            , "context" => $new_request->type
            , "status" => CommissionStatus::UNPAID
            , "date" => $new_request->date
            , "points" => $new_request->points
            , "agent_rate" => $company_commission_rate
            , "client" => $new_request->client
        );


        // create referral
        $commission_id = $this->commission_dal->add_commission( $company_commission );
        if (empty($commission_id)) {
            error_log("Failed to create company commission.  Data array: "
                    . var_export($company_commission, true));
        } else {
            $new_request->company_referral_id = $commission_id;
        }

        return $new_request;
    }
}
