<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\csv;
use AffiliateLTP\admin\csv\Commission_Parser;

use League\Csv\Reader;

/**
 * Description of test-commission-parser
 *
 * @author snielson
 */
class Test_Commission_Parser extends \WP_UnitTestCase {
    
    private function get_agent_dal_mock() {
        $agent_stub = $this->getMockBuilder('\AffiliateLTP\admin\Agent_DAL')
//                     ->setMethods([
//                         'get_agent_id_by_email'
//                        ])
                     ->getMock();
        return $agent_stub;
    }
    private function get_crm_mock() {
        $crm_stub = $this->getMockBuilder('\AffiliateLTP\SugarCRMDAL')
                     ->setMethods([
                         'getAccountById'
                        ])
                     ->getMock();
        return $crm_stub;
    }
    public function test_next_commission_request() {
        $amount = 1000;
        $splitCommission = 'N';
        $empty = '';
        
        $headers = [
                    'writing_agent_email', 'split_commission','split_1_percent'
                    ,'split_2_agent_email', 'split_2_percent'
                    ,'split_3_agent_email', 'split_3_percent'
                    ,'is_life_insurance', 'points','date','amount'
                    ,'skip_company_haircut','give_all_company_haircut'
                    ,'contract_number','client_name','client_street_address'
                    ,'client_city','client_zipcode','client_phone','client_email'
                    ];
        $values = [
                    'john@example.com', $splitCommission, 100
                    , $empty, $empty
                    , $empty, $empty
                    , 'N', 1000, '"Jan 31, 2017"', $amount
                    , 'N', 'N'
                    , '#555-555-555', $empty, $empty
                    , $empty ,$empty ,$empty ,$empty
        ];
        $testCSV = join(",", $headers) . "\n\r" 
                . join(",", $values) . "\n\r";
        $agent_id = 1;
                
        $reader = Reader::createFromString($testCSV);
        
        $agent_dal = $this->get_agent_dal_mock();
        $agent_dal->method('get_agent_id_by_email')
                ->willReturn($agent_id);
        
        $crm_dal = $this->get_crm_mock();
        
        $obj = new Commission_Parser($reader, $agent_dal, $crm_dal);
        $request = $obj->next_commission_request();
        
        $this->assertNotNull($request);
        $this->assertNotNull($request->agents);
        $this->assertEquals(count($request->agents), 1, "Split commission is false and there should only be one agent");
        $agent = $request->agents[0];
        
        $this->assertEquals($agent_id, $agent->id, "Agent id should be set");
        $this->assertEquals(100, $agent->split, "Single agent should have 100% for split percent");
        
        $this->assertEquals($amount, $request->amount, "Amount should have been parsed");
        $this->assertEquals(false, $request->companyHaircutAll, "All company haircut flag should be false");
        $this->assertEquals(false, $request->skipCompanyHaircut, "Company haircut should be skipped");
    }
}
