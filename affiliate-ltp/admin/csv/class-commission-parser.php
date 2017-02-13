<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\csv;
require_once "class-commission-csv-request.php";

use AffiliateLTP\admin\csv\Commission_CSV_Request;
use League\Csv\Reader;
use AffiliateLTP\admin\Referrals_Agent_Request;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\SugarCRMDAL;
use AffiliateLTP\CommissionType;



/**
 * Description of class-file-parser
 *
 * @author snielson
 */
class Commission_Parser {    
    /*
     * @var Iterator
     */
    private $readerIterator;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     *
     * @var SugarCRMDAL
     */
    private $crm_dal;
    
    /**
     * Keeps track of the current line number
     * @var number
     */
    private $line_number;
    
    private $csv_keys = ['writing_agent_email'
            ,'is_life_insurance', 'points'
            ,'date','amount'
            ,'split_commission'
            ,'split_1_percent'
            ,'split_2_agent_email', 'split_2_percent'
            ,'split_3_agent_email', 'split_3_percent'
            ,'skip_company_haircut'
            ,'give_all_company_haircut'
            ,'contract_number'
            ,'client_name'
            ,'client_street_address'
            ,'client_city'
            ,'client_zipcode'
            ,'client_phone'
            ,'client_email'
        ];
    
    // TODO: stephen need to have a situation of how to handle errors while we go through the import
    
    public function __construct(Reader $reader, Agent_DAL $agent_dal, SugarCRMDAL $crm_dal) {
        $keys = $this->csv_keys;
        
        $this->readerIterator = $reader->setOffset(1)->fetchAssoc($keys, array($this, 'format_rows'));
        $this->readerIterator->rewind(); // set it to the beginning.
        $this->agent_dal = $agent_dal;
        $this->crm_dal =$crm_dal;
        $this->line_number = 1;
    }
    
    public function format_rows($row) {
        $formatBool = function ($val) { return strtoupper($val) === 'Y'; };
        $formatDouble = function($val) { return filter_var($val, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE); };
        $filterInt = function ($val) { return filter_var($val, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE); };
        $filterText = function ($val) { return sanitize_text_field($val); };
        
        $row['amount'] = $formatDouble($row['amount']);
        $row['split_commission'] = $formatBool($row['split_commission']);
        $row['is_life_insurance'] = $formatBool($row['is_life_insurance']);
        $row['skip_company_haircut'] = $formatBool($row['skip_company_haircut']);
        $row['give_all_company_haircut'] = $formatBool($row['give_all_company_haircut']);
        
        if ($row['is_life_insurance']) {
            $row['points'] = $formatDouble($row['points']);
        }
        else {
            $row['points'] = $row['amount'];
        }
        
        $requiredInts = ['split_1_percent','split_2_percent', 'split_3_percent'];
        foreach ($requiredInts as $key) {
            $row[$key] = $filterInt($row[$key]);
        }
        
        $requiredTexts = ['writing_agent_email', 'split_2_agent_email', 'split_3_agent_email'];
        foreach ($requiredTexts as $key) {
            $row[$key] = $filterText($row[$key]);
        }
        
        $row['date'] = date_i18n( 'Y-m-d H:i:s', strtotime( $row['date'] ) );
        
        $row['line_number'] = $this->line_number++;
//        echo "<pre>";
//        var_dump($row);
//        echo "</pre>";
        return $row;
    }
    

    /**
     * 
     * @return Commission_CSV_Request
     */
    public function next_commission_request() {
        if (!$this->readerIterator->valid()) {
            return null;
        }
                
        $row = $this->readerIterator->current();
        //$this->validate_row($row);
        $request = new \AffiliateLTP\admin\csv\Commission_CSV_Request();
        
        $request->agents= $this->create_agents_for_row($row);
        $request->client = $this->get_client_for_row($row);
        $request->amount = $row['amount'];
        $request->points = $row['points'];
        $request->companyHaircutAll = $row['give_all_company_haircut'];
        $request->skipCompanyHaircut = $row['skip_company_haircut'];
        if ($row['is_life_insurance']) {
            $request->type = CommissionType::TYPE_LIFE;
        }
        else {
            $request->type = CommissionType::TYPE_NON_LIFE;
        }
        $request->date = $row['date'];
        $request->line_number = $row['line_number'];
        
        $this->readerIterator->next();
        
        return $request;
    }
    
    private function get_client_for_row($row) {
        // need to do a lookup for the client based on the contract number
        $client = null;
        
        $found_client = $this->crm_dal->getAccountById($row['contract_number']);
        if (!empty($found_client)) {
            $client = $found_client;
        }
        else {
            $client = array(
            'id' => null
            ,'contract_number' => $row['contract_number']
            ,'name'    => $row['client_name']
            ,'street_address' => $row['client_street_address']
            ,'city' => $row['client_city']
            ,'country' => 'USA' // TODO: stephen extract this to a setting or constant.
            ,'zip' => $row['client_zipcode']
            ,'phone'   => $row['client_phone']
            ,'email' => $row['client_email']
            );
        }
        return $client;
    }
    
    private function create_agents_for_row($row) {
        $agents = array();
        $agent1 = new Referrals_Agent_Request();
        if (!empty($row['writing_agent_email'])) {
            
            $agent1->id = $this->find_agent_id_by_email($row['writing_agent_email']);
            $agent1->email = $row['writing_agent_email'];
            $agent1->split = 100;
        }
        if ($row['split_commission']) {
            $agent1->split = $row['split_1_percent'];
            $agents[] = $agent1;
            
            if (isset($row['split_2_agent_email'])) {
                $agent2 = new Referrals_Agent_Request();
                $agent2->id = $this->find_agent_id_by_email($row['split_2_agent_email']);
                $agent2->email = $row['split_2_agent_email'];
                $agent2->split = $row['split_2_percent'];
                $agents[] = $agent2;
            }

            if (!empty($row['split_3_agent_email'])) {
                $agent3 = new Referrals_Agent_Request();
                $agent3->id = $this->find_agent_id_by_email($row['split_3_agent_email']);
                $agent3->email = $row['split_3_agent_email'];
                $agent3->split = $row['split_3_percent'];
                $agents[] = $agent3;
            }
        }
        else {
            $agents[] = $agent1;
        }
        
        return $agents;
    }
    
    private function find_agent_id_by_email($agent_email) {
        return $this->agent_dal->get_agent_id_by_email($agent_email);
    }
}
