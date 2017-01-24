<?php

namespace AffiliateLTP\admin;
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-commission-company-processor.php';
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-referrals-agent-request.php';
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-referrals-new-request.php';

use AffiliateLTP\CommissionType;

/**
 * Verifies that the commission company calculations are all handled properly
 * for the various insurance rule situations.
 *
 * @author snielson
 */
class Test_Commission_Company_Processor extends \WP_UnitTestCase {
    
    const COMPANY_RATE = 15;
    const COMPANY_AGENT_ID = 1;
    
    private function get_settings_dal_mock() {
             $settings_stub = $this->getMockBuilder('\AffiliateLTP\admin\Settings_DAL')
                     ->setMethods([
                         'get_setting'
                         ,'get_company_rate'
                         ,'get_company_agent_id'
                        ])
                     ->getMock();
             
             $settings_stub->method('get_company_agent_id')
                ->willReturn(self::COMPANY_AGENT_ID);
             
              $settings_stub->method('get_company_rate')
                ->willReturn(self::COMPANY_RATE);
             
             return $settings_stub;
        }
        
        private function get_commission_dal_mock() {
            $commission_dal_stub = $this->getMockBuilder('\AffiliateLTP\admin\Commission_DAL')
                     ->setMethods([
                         'add_commission'
                     ])
                     ->getMock();
            
            $commission_dal_stub->method('add_commission')
                ->willReturn(1);
            
            return $commission_dal_stub;
        }
        
    function get_sample_request() {
        $request = new \AffiliateLTPReferralsNewRequest();
        $amount = 1000;
        $contract_number = "#555";
        $points = 1000;
        $type = CommissionType::TYPE_NON_LIFE;
        $date = "01/01/2017";
        
        $request->amount = $amount;
         // we won't create a client on this request.
         $request->client = $this->get_sample_client();

        $request->skipCompanyHaircut = true;
        $request->date = $date;
        $request->points = $points;
        $request->type = $type;
        return $request;
    }
    
    function get_sample_client() {
        return array(
                'id' => '5000'
                ,'contract_number' => "#555"
        );
    }
    
    function get_sample_commission_save($company_commission_rate, $company_amount) {
        $points = 1000;
        $type = CommissionType::TYPE_NON_LIFE;
        $date = "01/01/2017";
        $client = $this->get_sample_client();
        $company_commission = array(
            // TODO: stephen we need to change this to be agent_id to be consistent in terminology...
            "affiliate_id" => absint(self::COMPANY_AGENT_ID)
            , "description" => __("Override", "affiliate-ltp")
            , "reference" => $client['contract_number']
            , "amount" => $company_amount
            , "custom" => "indirect"
            , "context" => $type
            , "status" => CommissionStatus::UNPAID
            , "date" => $date
            , "points" => $points
            , "agent_rate" => $company_commission_rate
            , "client" => $client
        );
        return $company_commission;
    }
    function test_process_commission_request_haircut_skip() {

        $settings_dal = $this->get_settings_dal_mock();
        $commission_dal = $this->get_commission_dal_mock();

        $request = $this->get_sample_request();

        $processor = new Commission_Company_Processor($commission_dal, $settings_dal);
        $newRequest = $processor->create_company_commission($request);

        $amount = $request->amount;
        $points = $request->points;
        // make sure nothing changed in skipping the haircut.
        $this->assertNotSame($newRequest, $request);
        $this->assertEquals($newRequest->amount, $amount);
        $this->assertEquals($newRequest->points, $points);
    }
    
    function test_process_commission_request_all_haircut() {
        $settings_dal = $this->get_settings_dal_mock();
        $commission_dal = $this->get_commission_dal_mock();

        $request = $this->get_sample_request();
        $request->companyHaircutAll = true;
        $request->skipCompanyHaircut = false;
        $request->points = 5000;
        
        $processor = new Commission_Company_Processor($commission_dal, $settings_dal);
        $newRequest = $processor->create_company_commission($request);

        // make sure nothing changed in skipping the haircut.
        $this->assertNotSame($newRequest, $request);
        
        // amount set to 0, points are the same as the amount
        $this->assertEquals(0,$newRequest->amount, "entire amount should go to company");
        $this->assertEquals(0, $newRequest->points, "points should be none");
    }
    
    function test_process_commission_request_life() {
        $settings_dal = $this->get_settings_dal_mock();
        $commission_dal = $this->get_commission_dal_mock();

        $request = $this->get_sample_request();
        $request->companyHaircutAll = false;
        $request->skipCompanyHaircut = false;
        $request->points = 5000;
        $request->type = CommissionType::TYPE_LIFE;
        $remaining_amount = (1 - (self::COMPANY_RATE / 100)) * $request->amount;
        
        $expected_commission = $this->get_sample_commission_save( self::COMPANY_RATE/100, 
                $request->amount - $remaining_amount);
        $expected_commission['points'] = $request->amount - $remaining_amount;
        $expected_commission['context'] = $request->type;
        
        $commission_dal->expects($this->once())
                ->method('add_commission')
                ->with($expected_commission);
        
        $processor = new Commission_Company_Processor($commission_dal, $settings_dal);
        $newRequest = $processor->create_company_commission($request);

        // make sure nothing changed in skipping the haircut.
        $this->assertNotSame($newRequest, $request);
        
        // amount set to 0, points are the same as the amount
        $this->assertEquals($remaining_amount,$newRequest->amount, "Amount should have been adjusted");
        $this->assertEquals($request->points, $newRequest->points, "points should not be modified for life insurance");
    }
    
    function test_process_commission_request_nonlife() {
        $settings_dal = $this->get_settings_dal_mock();
        $commission_dal = $this->get_commission_dal_mock();

        $request = $this->get_sample_request();
        $request->companyHaircutAll = false;
        $request->skipCompanyHaircut = false;
        $request->points = 5000;
        $request->type = CommissionType::TYPE_NON_LIFE;
        $remaining_amount = (1 - (self::COMPANY_RATE / 100)) * $request->amount;
        
        $expected_commission = $this->get_sample_commission_save( self::COMPANY_RATE/100, 
                $request->amount - $remaining_amount);
        $expected_commission['points'] = $request->amount - $remaining_amount;
        $expected_commission['context'] = $request->type;
        
        $commission_dal->expects($this->once())
                ->method('add_commission')
                ->with($expected_commission);
        
        $processor = new Commission_Company_Processor($commission_dal, $settings_dal);
        $newRequest = $processor->create_company_commission($request);

        // make sure nothing changed in skipping the haircut.
        $this->assertNotSame($newRequest, $request);
        
        // amount set to 0, points are the same as the amount
        $this->assertEquals($remaining_amount,$newRequest->amount, "Amount should have been adjusted");
        $this->assertEquals($remaining_amount, $newRequest->points, "points should be the same as the amount for non-life insurance");
    }
}
