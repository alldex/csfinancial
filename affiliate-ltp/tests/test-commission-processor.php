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
    
    const DEFAULT_AMOUNT = 1000;
    const DEFAULT_AGENT_RATE = .20;
    const DEFAULT_COMMISSION_ID = 2;
    const DEFAULT_CHILD_AGENT_ID = 1;
    const DEFAULT_COMMISSION_TYPE = \AffiliateLTPCommissionType::TYPE_NON_LIFE;
    const DEFAULT_CONTRACT_NUMBER = "#555-555-5555";
    const DEFAULT_COMMISSION_DATE = "01/17/2017";
    const DEFAULT_PARENT_AGENT_ID = 2;
    const DEFAULT_PARENT_AGENT_RATE = .70;
    const DEFAULT_COLEADERSHIP_RATE = .75;
    
    
    
        private function get_agent_dal_mock() {
            $agent_dal_stub = $this->getMockBuilder('\AffiliateLTP\admin\Agent_DAL')
                     ->setMethods([
                         'is_active'
                         ,'doSomething'
                         ,'is_life_licensed'
                         ,'get_parent_agent_id'
                         ,'get_agent_rank'
                         ,'get_agent_commission_rate'
                         ,'get_agent_coleadership_agent_id'
                         ,'get_agent_coleadership_agent_rate'
                        ])
                     ->getMock();
            $agent_dal_stub->method('is_active')
                    ->will($this->returnValue(true));
            
            $agent_dal_stub->method('get_agent_rank')
                    ->will($this->returnValue(1));
            
            $agent_dal_stub->method('is_life_licensed')
                    ->willReturn(true);
            
            // initial is to return null
//            $agent_dal_stub->method('get_agent_coleadership_agent_id')
//                    ->willReturn(null);
            
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
        
        private function get_default_expected_commission_obj() {
            $amount = self::DEFAULT_AMOUNT;
            $agent_rate = self::DEFAULT_AGENT_RATE;
            
            $expected_commission_obj = array(
                "affiliate_id" => self::DEFAULT_CHILD_AGENT_ID
                , "description" => "Personal sale"
                , "amount" => $amount * $agent_rate
                , "reference" => self::DEFAULT_CONTRACT_NUMBER
                , "custom" => 'direct'
                , "context" => \AffiliateLTPCommissionType::TYPE_NON_LIFE
                , "status" => Commission_Processor::STATUS_DEFAULT
                , "date" => self::DEFAULT_COMMISSION_DATE
                , "points" => $amount
                , "agent_rate" => $agent_rate
                , "client" => $this->get_existing_client(self::DEFAULT_CONTRACT_NUMBER)
            );
            return $expected_commission_obj;
        }
        
        function get_default_new_request() {
            $agent_percentage_rate = 100;
            $request = new \AffiliateLTPReferralsNewRequest();
            $request->amount = self::DEFAULT_AMOUNT;
            $request->agents[] = $this->get_agent_request_obj($agent_percentage_rate,
                    self::DEFAULT_CHILD_AGENT_ID);
            
            // we won't create a client on this request.
            $request->client = $this->get_existing_client(self::DEFAULT_CONTRACT_NUMBER);
            
            $request->companyHaircutAll = false;
            $request->date = self::DEFAULT_COMMISSION_DATE;
            $request->points = self::DEFAULT_AMOUNT;
            $request->type = self::DEFAULT_COMMISSION_TYPE;
            $request->skipCompanyHaircut = true;
            return $request;
        }
    /**
	 * A single example test.
	 */
	function test_process_commission_request() {
            
            $agent_dal_stub = $this->get_agent_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->willReturn(null);
            
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->willReturn(self::DEFAULT_AGENT_RATE);
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn(self::DEFAULT_COMMISSION_ID);
            
            $expected_commission_obj = $this->get_default_expected_commission_obj();
            
            $commission_dal_stub->expects($this->once())
                    ->method('add_commission')
                    ->with($expected_commission_obj);
            
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $this->get_settings_dal_mock());
            
            $request = $this->get_default_new_request();
            $object->process_commission_request($request);
	}
        
        // TODO: stephen need to add in the parent_agent_id for commissions to track
        // them historically so we can calculate the correct rates.
        
        /**
	 * Processes the commission of a single parent who has a rate higher than
         * the child agent.
	 */
	function test_process_commission_request_with_parent_higher_rate() {
            
            $agent_dal_stub = $this->get_agent_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->will($this->returnValueMap([
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_PARENT_AGENT_ID]
                        ,[self::DEFAULT_PARENT_AGENT_ID, null]
                    ]));
            
            $rate_map = [
                [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_AGENT_RATE]
                ,[self::DEFAULT_PARENT_AGENT_ID, self::DEFAULT_PARENT_AGENT_RATE ]
            ];
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->will($this->returnValueMap($rate_map));
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn(self::DEFAULT_COMMISSION_ID);
            
            $expected_commission_obj = $this->get_default_expected_commission_obj();
            
            // copy the array with assignment by value
            $parent_expected_commission_obj = $expected_commission_obj;
            $parent_expected_commission_obj['affiliate_id'] = self::DEFAULT_PARENT_AGENT_ID;
            $parent_expected_commission_obj['description'] = "Override";
            $parent_expected_commission_obj['amount'] = 
                    (self::DEFAULT_PARENT_AGENT_RATE - self::DEFAULT_AGENT_RATE) 
                        * self::DEFAULT_AMOUNT;
            $parent_expected_commission_obj['custom'] = 'indirect';
            $parent_expected_commission_obj['agent_rate'] = self::DEFAULT_PARENT_AGENT_RATE - self::DEFAULT_AGENT_RATE;
            
            $commission_dal_stub->expects($this->exactly(2))
                    ->method('add_commission')
                    ->withConsecutive(
                            array($this->equalTo($expected_commission_obj))
                            ,array($this->equalTo($parent_expected_commission_obj)));
            
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $this->get_settings_dal_mock());
            
            $request = $this->get_default_new_request();
            $object->process_commission_request($request);
	}
        
        function test_process_commission_request_coleadership() {
            $coleadership_agent_id = 3;
            $agent_dal_stub = $this->get_agent_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            
            $agent1_commission_obj = $this->get_default_expected_commission_obj();
            $agent1_commission_obj['coleadership_id'] = $coleadership_agent_id;
            $agent1_commission_obj['coleadership_rate'] = self::DEFAULT_COLEADERSHIP_RATE;
            $amount = self::DEFAULT_AMOUNT;
            $points = $agent1_commission_obj['points'];
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->will($this->returnValueMap([
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_PARENT_AGENT_ID]
                        ,[self::DEFAULT_PARENT_AGENT_ID, null]
                    ]));
            
            $rate_map = [
                [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_AGENT_RATE]
                ,[self::DEFAULT_PARENT_AGENT_ID, self::DEFAULT_PARENT_AGENT_RATE]
                ,[$coleadership_agent_id, self::DEFAULT_PARENT_AGENT_RATE]
            ];
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->will($this->returnValueMap($rate_map));
            
            $coleadership_agent_map = [
                [self::DEFAULT_CHILD_AGENT_ID, $coleadership_agent_id]
                ,[self::DEFAULT_PARENT_AGENT_ID, null]
                ,[$coleadership_agent_id, null]
            ];
            $agent_dal_stub->method('get_agent_coleadership_agent_id')
                    ->will($this->returnValueMap($coleadership_agent_map));
            
            
            $agent_dal_stub->method('get_agent_coleadership_agent_rate')
                    ->willReturn(self::DEFAULT_COLEADERSHIP_RATE);
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn(self::DEFAULT_COMMISSION_ID);
            
            // 25% rate
            /*
             *  $expected_commission_obj = array(
                "affiliate_id" => self::DEFAULT_CHILD_AGENT_ID
                , "description" => "Personal sale"
                , "amount" => $amount * $agent_rate
                , "reference" => self::DEFAULT_CONTRACT_NUMBER
                , "custom" => 'direct'
                , "context" => \AffiliateLTPCommissionType::TYPE_NON_LIFE
                , "status" => Commission_Processor::STATUS_DEFAULT
                , "date" => self::DEFAULT_COMMISSION_DATE
                , "points" => $amount
                , "agent_rate" => $agent_rate
                , "client" => $this->get_existing_client(self::DEFAULT_CONTRACT_NUMBER)
            );
             */
            
            // coleadership should be $375 for 1000, ($1000 * (70%-20%) * 75%)
            $coleadership_commission_obj = $agent1_commission_obj;
            $coleadership_base_amount = round(self::DEFAULT_COLEADERSHIP_RATE * $amount, 2);
            $coleadership_rate = self::DEFAULT_PARENT_AGENT_RATE - self::DEFAULT_AGENT_RATE;
            $coleadership_amount = round($coleadership_rate * $coleadership_base_amount, 2);
            $coleadership_points = round(self::DEFAULT_COLEADERSHIP_RATE * $points, 2);
            $coleadership_commission_obj['amount'] = $coleadership_amount;
            $coleadership_commission_obj['custom'] = 'indirect';
            $coleadership_commission_obj['description'] = 'Override';
            $coleadership_commission_obj['points'] = $coleadership_points;
            $coleadership_commission_obj['agent_rate'] = $coleadership_rate;
            $coleadership_commission_obj['affiliate_id'] = $coleadership_agent_id;
            // these don't get saved on the parent object.
            unset($coleadership_commission_obj['coleadership_id']);
            unset($coleadership_commission_obj['coleadership_rate']);
            
            // passive should be ($1000 * (70%-20%) * 25%) = $125.
            $passive_base_amount = $amount - $coleadership_base_amount;
            $passive_amount = round($passive_base_amount * (self::DEFAULT_PARENT_AGENT_RATE - self::DEFAULT_AGENT_RATE), 2);
            $passive_points = $points - $coleadership_points;
            $passive_leader_commission_obj = $coleadership_commission_obj;
            $passive_leader_commission_obj['affiliate_id'] = self::DEFAULT_PARENT_AGENT_ID;
            $passive_leader_commission_obj['amount'] = $passive_amount;
            $passive_leader_commission_obj['points'] = $passive_points;
            
             $commission_dal_stub->expects($this->exactly(3))
                    ->method('add_commission')
                    ->withConsecutive(
                            array($this->equalTo($agent1_commission_obj))
                            ,array($this->equalTo($passive_leader_commission_obj))
                            ,array($this->equalTo($coleadership_commission_obj)));
            
            $request = $this->get_default_new_request();
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $this->get_settings_dal_mock());
            
            $object->process_commission_request($request);
        }
        
        
}
