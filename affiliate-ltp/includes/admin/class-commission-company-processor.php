<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AffiliateLTP\admin;

use AffiliateLTP\Commission_Type;
use AffiliateLTP\admin\Referrals_New_Request;

require_once 'class-commission-status.php';

/**
 * Prepares and creates a company commission record.  Since other agent
 * commissions are based upon the initial cut of the company 
 * prepare_company_commission must always be called first to prepare a 
 * commission request.  Once all agent commissions have been calculated the
 * commissions are passed back into the company processor to create the final
 * company commission record.
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
    
    /**
     *
     * @var type 
     */
    private $company_cut;
    
    /**
     * The original commission request.
     * @var Referrals_New_Request
     */
    private $orig_request;
    
    public function __construct(Commission_DAL $commission_dal, Settings_DAL $settings_dal) {
        $this->commission_dal = $commission_dal;
        $this->settings_dal = $settings_dal;
    }
    
    /**
     * finalizes the creation of the 
     * @param array $agent_commissions
     * @return type
     * @throws \LogicException
     * @throws \Exception
     */
    public function create_company_commission(array $agent_commissions, Referrals_New_Request $request, $commission_request_id) {
        
        if (empty($this->orig_request)) {
            throw new \LogicException("prepare_company_commission must be called before calling create_company_commission");
        }
        
        // there is no company commission because there is no agent id.
        if (empty($this->company_cut)) {
            return null;
        }
        
        // start the commissions at the company
        $total_agent_commisions = 0;
        if (!empty($agent_commissions)) {
            foreach ($agent_commissions as $commission) {
                if (is_numeric($commission['amount'])) {
                    $total_agent_commisions += $commission['amount'];
                }
                else {
                    throw new \Exception("commission found with non-numeric amount: " . var_export($commission, true));
                }
            }
        }
        
        
        // grab the original amount
        $orig_amount = $this->orig_request->amount;
        if ($total_agent_commisions > $orig_amount) {
            // total agent commissions can't be greater... so we set it to be the amount
            // TODO: stephen log this logic error as we shouldn't be hitting this...
            $total_agent_commisions = $orig_amount;
        }
        $company_amount = round($orig_amount - $total_agent_commisions, 2);
        $this->company_cut['amount'] = $company_amount;
        $this->company_cut['meta']['agent_real_rate'] = round( ($company_amount / $orig_amount), 4);
        
        if ($this->orig_request->type != Commission_Type::TYPE_LIFE) {
            $this->company_cut['meta']['points'] = round($company_amount);
        }
        else {
            $this->company_cut['meta']['points'] = $this->company_cut['agent_rate'] * $this->orig_request->points;
        }
        
        $this->company_cut['meta']['commission_request_id'] = $commission_request_id;
        
        // create commission
        $commission_id = $this->commission_dal->add_commission( $this->company_cut );
        if (empty($commission_id)) {
            error_log("Failed to create company commission.  Data array: "
                    . var_export($this->company_cut, true));
        }
        
        return $commission_id;
    }
    
    /**
     * Removes the initial company commission from the request and returns the
     * updated request.
     * @param Referrals_New_Request $orig_request
     * @return Referrals_New_Request
     */
    public function prepare_company_commission(Referrals_New_Request $orig_request) {
        $this->orig_request = $orig_request;
        
        $new_request = clone $orig_request;
        
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
        // Or make it 0 if the company haircut is set to nothing so we can
        // prepare all the data for a company commission if there is any money
        // left over to be paid to the company if the agents don't finish
        // paying out.
        if (empty($company_commission_rate) || $new_request->skipCompanyHaircut) {
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
        if ($new_request->type != Commission_Type::TYPE_LIFE) {
            $new_request->points = $amount_remaining;
        }

        // Process cart and get amount
        $company_commission = array(
            "agent_id" => absint($company_agent_id)
            , "description" => __("Override", "affiliate-ltp")
            , "reference" => $new_request->client['contract_number']
            , "amount" => $company_amount
            , "custom" => "indirect"
            , "context" => $new_request->type
            , "status" => CommissionStatus::PAID
            , "date" => $new_request->date
            , "meta" => [
                "points" => $company_amount
                , "agent_rate" => $company_commission_rate
                , "original_amount" => $orig_request->amount
                , "new_business" => "Y"
                , "company_commission" => "Y"
            ]
            , "client" => $new_request->client
        );
        
        // save it off so we can use it in the finalize process.
        $this->company_cut = $company_commission;

        return $new_request;
    }
}
