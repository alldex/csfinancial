<?php

namespace AffiliateLTP\admin;

require_once dirname( dirname( __FILE__ ) ) . '/admin/class-commission-processor.php';
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-referrals-agent-request.php';
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-referrals-new-request.php';
//require_once '../admin/class-commission-process.php
//

/**
 * Description of test-commission-processor
 *
 * @author snielson
 */
class Test_Commission_Processor extends \WP_UnitTestCase{
    
        private function get_agent_dal_mock() {
            $agent_dal_stub = $this->getMockBuilder('\AffiliateLTP\admin\Agent_DAL')
                     ->setMethods([
                         'is_active'
                         ,'doSomething'
                         ,'is_life_licensed'
                         ,'get_parent_agent_id'
                         ,'get_agent_rank'
                         ,'get_agent_commission_rate'
                        ])
                     ->getMock();
            $agent_dal_stub->method('is_active')
                    ->will($this->returnValue(true));
            
            $agent_dal_stub->method('get_agent_rank')
                    ->will($this->returnValue(1));
            
            $agent_dal_stub->method('is_life_licensed')
                    ->willReturn(true);
            
            return $agent_dal_stub;
        }
        
        private function get_settings_dal_mock() {
             $settings_stub = $this->getMockBuilder('\AffiliateLTP\admin\Settings_DAL')
                     ->setMethods([
                         'get_setting'
                         ,'get_company_rate'
                         ,'get_company_agent_id'
                         ,'get_partner_rank_id'
                        ])
                     ->getMock();
             $settings_stub->method('get_partner_rank_id')
                     ->willReturn(3);
             return $settings_stub;
        }
        
        private function get_commission_dal_mock() {
            $commission_dal_stub = $this->getMockBuilder('\AffiliateLTP\admin\Commission_DAL')
                     ->setMethods([
                         'add_commission'
                         ,'add_commission_meta'
                     ])
                     ->getMock();
            return $commission_dal_stub;
        }
        
        private function get_agent_request_obj($split, $id) {
            $agent = new \AffiliateLTPReferralsAgentRequest();
            $agent->split = $split;
            $agent->id = $id;
            return $agent;
        }
        
        private function get_existing_client($contract_number) {
            return array(
                'id' => '5000'
                ,'contract_number' => $contract_number
            );
        }
    /**
	 * A single example test.
	 */
	function test_process_commission_request() {
            
            $agent_dal_stub = $this->get_agent_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            
            $commission_id = 2;
            $agent_rate = .20;
            $amount = 1000;
            $points = $amount;
            $agent_id = 1;
            $contract_number = '#555-5555-555';
            $type = \AffiliateLTPCommissionType::TYPE_NON_LIFE;
            $date = "01/01/2017";
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->willReturn(null);
            
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->willReturn($agent_rate);
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn($commission_id);
            
            $expected_commission_obj = array(
                "affiliate_id" => $agent_id
                , "description" => "Personal sale"
                , "amount" => $amount * $agent_rate
                , "reference" => $contract_number
                , "custom" => 'direct'
                , "context" => $type
                , "status" => Commission_Processor::STATUS_DEFAULT
                , "date" => $date
                , "points" => $points
                , "agent_rate" => $agent_rate
                , "client" => $this->get_existing_client($contract_number)
            );
            $commission_dal_stub->expects($this->once())
                    ->method('add_commission')
                    ->with($expected_commission_obj);
            
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $this->get_settings_dal_mock());
            
            $request = new \AffiliateLTPReferralsNewRequest();
            $request->amount = $amount;
            $request->agents[] = $this->get_agent_request_obj(100, $agent_id);
            
            // we won't create a client on this request.
            $request->client = $this->get_existing_client($contract_number);
            
            $request->companyHaircutAll = false;
            $request->date = $date;
            $request->points = $points;
            $request->type = $type;
            
            $object->process_commission_request($request);
	}
        
        /**
	 * Processes the commission of a single parent who has a rate higher than
         * the child agent.
	 */
	function test_process_commission_request_with_parent_higher_rate() {
            
            $agent_dal_stub = $this->get_agent_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            
            $commission_id = 2;
            $agent_rate = .20;
            $amount = 1000;
            $points = $amount;
            $agent_id = 1;
            $contract_number = '#555-5555-555';
            $type = \AffiliateLTPCommissionType::TYPE_NON_LIFE;
            $date = "01/01/2017";
            $parent_agent_id = 2;
            $parent_rate = .70;
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->will($this->returnValueMap([
                        [$agent_id, $parent_agent_id]
                        ,[$parent_agent_id, null]
                    ]));
            
            $rate_map = [
                [$agent_id, $agent_rate]
                ,[$parent_agent_id, $parent_rate]
            ];
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->will($this->returnValueMap($rate_map));
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn($commission_id);
            
            $expected_commission_obj = array(
                "affiliate_id" => $agent_id
                , "description" => "Personal sale"
                , "amount" => $amount * $agent_rate
                , "reference" => $contract_number
                , "custom" => 'direct'
                , "context" => $type
                , "status" => Commission_Processor::STATUS_DEFAULT
                , "date" => $date
                , "points" => $points
                , "agent_rate" => $agent_rate
                , "client" => $this->get_existing_client($contract_number)
            );
            
            // copy the array with assignment by value
            $parent_expected_commission_obj = $expected_commission_obj;
            $parent_expected_commission_obj['affiliate_id'] = $parent_agent_id;
            $parent_expected_commission_obj['description'] = "Override";
            $parent_expected_commission_obj['amount'] = ($parent_rate - $agent_rate) * $amount;
            $parent_expected_commission_obj['custom'] = 'indirect';
            $parent_expected_commission_obj['agent_rate'] = $parent_rate;
            
            $commission_dal_stub->expects($this->exactly(2))
                    ->method('add_commission')
                    ->will($this->onConsecutiveCalls($expected_commission_obj, 
                            $parent_expected_commission_obj));
            
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $this->get_settings_dal_mock());
            
            $request = new \AffiliateLTPReferralsNewRequest();
            $request->amount = $amount;
            $request->agents[] = $this->get_agent_request_obj(100, $agent_id);
            
            // we won't create a client on this request.
            $request->client = $this->get_existing_client($contract_number);
            
            $request->companyHaircutAll = false;
            $request->skipCompanyHaircut = true;
            $request->date = $date;
            $request->points = $points;
            $request->type = $type;
            
            $object->process_commission_request($request);
	}
}
