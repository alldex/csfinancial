<?php

namespace AffiliateLTP\admin;

require_once dirname( dirname( __FILE__ ) ) . '/admin/class-commission-processor.php';
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-referrals-agent-request.php';
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-referrals-new-request.php';
//require_once '../admin/class-commission-process.php
//

use AffiliateLTP\CommissionType;

/**
 * Runs the test scenariors for calculating commissions for the commission 
 * processor.
 *
 * @author snielson
 */
class Test_Commission_Processor extends \WP_UnitTestCase{
    
    const DEFAULT_AMOUNT = 1000;
    const DEFAULT_AGENT_RATE = .20;
    const DEFAULT_COMMISSION_ID = 2;
    const DEFAULT_CHILD_AGENT_ID = 1;
    const DEFAULT_COMMISSION_TYPE = CommissionType::TYPE_NON_LIFE;
    const DEFAULT_CONTRACT_NUMBER = "#555-555-5555";
    const DEFAULT_COMMISSION_DATE = "01/17/2017";
    const DEFAULT_PARENT_AGENT_ID = 2;
    const DEFAULT_PARENT_AGENT_RATE = .70;
    const DEFAULT_COLEADERSHIP_RATE = .75;
    const DEFAULT_PARTNER_RANK_ID = 3;
    const DEFAULT_RANK_ID = 1;
    
    
    
        private function get_agent_basic_dal_mock() {
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
            $agent_dal_stub->method('is_life_licensed')
                    ->willReturn(true);
            return $agent_dal_stub;
        }
        private function get_agent_dal_mock() {
            
            $agent_dal_stub = $this->get_agent_basic_dal_mock();
            
            $agent_dal_stub->method('get_agent_rank')
                    ->will($this->returnValue(1));
            
            return $agent_dal_stub;
        }
        
        private function get_settings_dal_mock() {
             $settings_stub = $this->getMockBuilder('\AffiliateLTP\admin\Settings_DAL')
                     ->setMethods([
                         'get_setting'
                         ,'get_company_rate'
                         ,'get_company_agent_id'
                         ,'get_partner_rank_id'
                         ,'get_generational_override_rate'
                        ])
                     ->getMock();
             $settings_stub->method('get_partner_rank_id')
                     ->willReturn(self::DEFAULT_PARTNER_RANK_ID);
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
                , "context" => CommissionType::TYPE_NON_LIFE
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
//            $this->markTestSkipped(
//              'debugging.'
//            );
            
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
        
        /**
         * Tests that coleadership works on the upline without dealing with
         * generational commissions
         */
        function test_process_commission_request_coleadership() {
//            $this->markTestSkipped(
//              'debugging.'
//            );
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
        
        /**
         * Tests that 1st, 2nd, and 3rd generation commission calculations are
         * handled properly
         */
        function test_process_commission_request_intergenerational_leadership()
        {
//            $this->markTestSkipped(
//              'debugging.'
//            );
            $agent_dal_stub = $this->get_agent_basic_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            $gen1_id = self::DEFAULT_PARENT_AGENT_ID + 1;
            $gen2_id = self::DEFAULT_PARENT_AGENT_ID + 2;
            $gen3_id = self::DEFAULT_PARENT_AGENT_ID + 3;
            $partner_rate = self::DEFAULT_PARENT_AGENT_RATE;
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->will($this->returnValueMap([
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_PARENT_AGENT_ID]
                        ,[self::DEFAULT_PARENT_AGENT_ID, $gen1_id]
                        ,[$gen1_id, $gen2_id]
                        ,[$gen2_id, $gen3_id]
                        ,[$gen3_id, null]
                    ]));
            
            $rate_map = [
                [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_AGENT_RATE]
                ,[self::DEFAULT_PARENT_AGENT_ID, $partner_rate]
                ,[$gen1_id, $partner_rate]
                ,[$gen2_id, $partner_rate]
                ,[$gen3_id, $partner_rate]
            ];
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->will($this->returnValueMap($rate_map));
            
            $agent_dal_stub->method('get_agent_coleadership_agent_id')
                    ->willReturn(null);
            
            $agent_dal_stub->method('get_agent_rank')
                    ->will($this->returnValueMap([
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_RANK_ID]
                        ,[self::DEFAULT_PARENT_AGENT_ID, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$gen1_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$gen2_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$gen3_id, self::DEFAULT_PARTNER_RANK_ID]
                    ]));
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn(self::DEFAULT_COMMISSION_ID);
            $gen1_rate = .17;
            $gen2_rate = .09;
            $gen3_rate = .04;
            $gen_rate_map = [
                [1, $gen1_rate]
                ,[2, $gen2_rate]
                ,[3, $gen3_rate]
            ];
            $settings_stub = $this->get_settings_dal_mock();
            $settings_stub->method("get_generational_override_rate")
                    ->will($this->returnValueMap($gen_rate_map));
            
            
            $request = $this->get_default_new_request();
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $settings_stub);
            
            $commissions_added = $object->process_commission_request($request);
            
            // child,parent, 3 generations
            $this->assertEquals(5, count($commissions_added), 
                    "Child, parent, and three generations of commissions should have been added");
            
// grab the 1st gen
            $gen1_arr = $commissions_added[2];
            $this->assertEquals($gen1_rate, $gen1_arr['agent_rate'], 
                    "1st Gen Agent rate should be set ");
            $this->assertEquals(self::DEFAULT_AMOUNT * $gen1_rate,
                    $gen1_arr['amount'], "1st Gen Agent amount should be set ");
            $this->assertEquals($gen1_id, $gen1_arr['affiliate_id'], "1st Gen Agent should be set ");
            
            $gen2_arr = $commissions_added[3];
            $this->assertEquals($gen2_rate, $gen2_arr['agent_rate'], 
                    "2nd Gen Agent rate should be set ");
            $this->assertEquals(self::DEFAULT_AMOUNT * $gen2_rate,
                    $gen2_arr['amount'], "2nd Gen Agent amount should be set ");
            $this->assertEquals($gen2_id, $gen2_arr['affiliate_id'], "2nd Gen Agent should be set ");
            
            $gen3_arr = $commissions_added[4];
            $this->assertEquals($gen3_rate, $gen3_arr['agent_rate'], 
                    "3rd Gen Agent rate should be set ");
            $this->assertEquals(self::DEFAULT_AMOUNT * $gen3_rate,
                    $gen3_arr['amount'], "3rd Gen Agent amount should be set ");
            $this->assertEquals($gen3_id, $gen3_arr['affiliate_id'], "3rd Gen Agent should be set ");
        }
        
        /**
         * Tests co-leadership when both up-lines are partners.
         */
        function test_process_commission_request_partner_coleadership() {
//            $this->markTestSkipped(
//              'debugging.'
//            );
            $agent_dal_stub = $this->get_agent_basic_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            $piper_id = self::DEFAULT_PARENT_AGENT_ID;
            $amy_id = $piper_id + 1;
            $kelly_id = $piper_id + 2;
            $scott_id = $piper_id + 3;
            $john_id = $piper_id + 4;
            $coleadership_agent_rate = .75;
            $partner_rate = self::DEFAULT_PARENT_AGENT_RATE;
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->will($this->returnValueMap([
                        // 
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_PARENT_AGENT_ID]
                        ,[$piper_id, $amy_id]
                        ,[$amy_id, $scott_id]
                        ,[$kelly_id, $john_id]
                    ]));
            
            $rate_map = [
                [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_AGENT_RATE]
                ,[$piper_id, $partner_rate]
                ,[$amy_id, $partner_rate]
                ,[$kelly_id, $partner_rate]
                ,[$scott_id, $partner_rate]
                ,[$john_id, $partner_rate]
            ];
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->will($this->returnValueMap($rate_map));
            
            $agent_dal_stub->method('get_agent_coleadership_agent_id')
                    ->will($this->returnValueMap([[$piper_id, $kelly_id]]));
            $agent_dal_stub->method('get_agent_coleadership_agent_rate')
                    ->will($this->returnValueMap([[$piper_id, $coleadership_agent_rate]]));
            
            $agent_dal_stub->method('get_agent_rank')
                    ->will($this->returnValueMap([
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_RANK_ID]
                        ,[$piper_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$amy_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$kelly_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$scott_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$john_id, self::DEFAULT_PARTNER_RANK_ID]
                    ]));
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn(self::DEFAULT_COMMISSION_ID);
            
            $settings_stub = $this->get_settings_dal_mock();
            $settings_stub->method("get_generational_override_rate")
                    ->will($this->returnValueMap($this->get_generation_rate_map()));
            
            
            $request = $this->get_default_new_request();
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $settings_stub);

            $commissions_added = $object->process_commission_request($request);
            // child agent joe 25%, piper 70% partner coleadership Amy 25%, Kelly 75%
            // Amy 1st Gen 25% of 17% -> Scott 25% of 4%
            // Kelly 1st Gen 75% of 17% -> John 75% of 4%
            
            // child,parent, 2 generations through one co-leadership, 2 generations through other co-leadership
            $this->assertEquals(6, count($commissions_added), 
                    "child,parent, 2 generations through one co-leadership, 2 generations through other co-leadership of commissions should have been added");
            
            $amy = $commissions_added[2];
            $amy_amount = (1 - $coleadership_agent_rate) * .17 * self::DEFAULT_AMOUNT;
            $amy_points = (1-$coleadership_agent_rate) * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($amy, $amy_points, $amy_amount, "1st Gen Piper->Amy ");
            
            $scott = $commissions_added[3];
            $scott_amount = (1-$coleadership_agent_rate) * .09 * self::DEFAULT_AMOUNT;
            $scott_points = (1-$coleadership_agent_rate) * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($scott, $scott_points, $scott_amount, "2nd Gen Piper->Amy->Scott ");
            
            $kelly = $commissions_added[4];
            $kelly_amount = $coleadership_agent_rate * .17 * self::DEFAULT_AMOUNT;
            $kelly_points = $coleadership_agent_rate * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($kelly, $kelly_points, $kelly_amount, "1st Gen Piper->Kelly");
            
            $john = $commissions_added[5];
            $john_amount = $coleadership_agent_rate * .09 * self::DEFAULT_AMOUNT;
            $john_points = $coleadership_agent_rate * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($john, $john_points, $john_amount, "2nd Gen Piper->Kelly->John");
        }
        
        /**
         * Test when the coleadership for a partner has one partner parent relationship
         * and one non-partner on the active chain.
         */
        function test_process_commission_generational_coleadership_no_partner() {
//            $this->markTestSkipped(
//              'debugging.'
//            );
            // child agent joe 25%, piper 70% partner coleadership Amy 25%, Kelly 75%
            // Amy 1st Gen 25% of 17% -> Scott 25% of 9%
            // Kelly (Non-Partner) 75% at 20% rate -> John 75% at 70% partner rate -> Scott 1st Gen 75% of 17%
            // Company keeps 75% of 9% 2nd Gen, 100% of 4% 3rd Gen
            
            $agent_dal_stub = $this->get_agent_basic_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            $piper_id = self::DEFAULT_PARENT_AGENT_ID;
            $amy_id = $piper_id + 1;
            $kelly_id = $piper_id + 2;
            $scott_id = $piper_id + 3;
            $john_id = $piper_id + 4;
            $coleadership_agent_rate = .75;
            $partner_rate = self::DEFAULT_PARENT_AGENT_RATE;
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->will($this->returnValueMap([
                        // 
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_PARENT_AGENT_ID]
                        ,[$piper_id, $amy_id]
                        ,[$amy_id, $scott_id]
                        ,[$kelly_id, $john_id]
                        ,[$john_id, $scott_id]
                    ]));
            
            $rate_map = [
                [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_AGENT_RATE]
                ,[$piper_id, $partner_rate]
                ,[$amy_id, $partner_rate]
                ,[$kelly_id, self::DEFAULT_AGENT_RATE]
                ,[$scott_id, $partner_rate]
                ,[$john_id, $partner_rate]
            ];
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->will($this->returnValueMap($rate_map));
            
            $agent_dal_stub->method('get_agent_coleadership_agent_id')
                    ->will($this->returnValueMap([[$piper_id, $kelly_id]]));
            $agent_dal_stub->method('get_agent_coleadership_agent_rate')
                    ->will($this->returnValueMap([[$piper_id, $coleadership_agent_rate]]));
            
            $agent_dal_stub->method('get_agent_rank')
                    ->will($this->returnValueMap([
                        [self::DEFAULT_CHILD_AGENT_ID, self::DEFAULT_RANK_ID]
                        ,[$piper_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$amy_id, self::DEFAULT_PARTNER_RANK_ID]
                            // Kelly is NOT a partner
                        ,[$kelly_id, self::DEFAULT_RANK_ID]
                        ,[$scott_id, self::DEFAULT_PARTNER_RANK_ID]
                        ,[$john_id, self::DEFAULT_PARTNER_RANK_ID]
                    ]));
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn(self::DEFAULT_COMMISSION_ID);
            
            $settings_stub = $this->get_settings_dal_mock();
            $settings_stub->method("get_generational_override_rate")
                    ->will($this->returnValueMap($this->get_generation_rate_map()));
            
            
            $request = $this->get_default_new_request();
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $settings_stub);

            $commissions_added = $object->process_commission_request($request);
            
            // child,parent, 2 generations through one co-leadership, 2 generations through other co-leadership
            $this->assertEquals(7, count($commissions_added), 
                    "child,piper, co-leadership 1st gen amy, 2nd gen scott, kelly (non-partner), john, and then 1st gen scott commissions should have been added");
            
            $amy = $commissions_added[2];
            $amy_amount = (1 - $coleadership_agent_rate) * .17 * self::DEFAULT_AMOUNT;
            $amy_points = (1-$coleadership_agent_rate) * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($amy, $amy_points, $amy_amount, "1st Gen Piper->Amy ");
            
            $scott = $commissions_added[3];
            $scott_amount = (1-$coleadership_agent_rate) * .09 * self::DEFAULT_AMOUNT;
            $scott_points = (1-$coleadership_agent_rate) * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($scott, $scott_points, $scott_amount, "2nd Gen Piper->Amy->Scott ");
            
            $kelly = $commissions_added[4];
            $kelly_amount = 0;
            $kelly_points = $coleadership_agent_rate * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($kelly, $kelly_points, $kelly_amount, "No commission due to lower rate Piper->Kelly");
            
            $john = $commissions_added[5];
            $john_amount = 0; // john is at 70%, but since piper is also at 70% the rate is 0.
            $john_points = $coleadership_agent_rate * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($john, $john_points, $john_amount, "Partner Piper->Kelly->John");
            
            $scott2 = $commissions_added[6];
            $scott2_amount = $coleadership_agent_rate * .17 * self::DEFAULT_AMOUNT;
            $scott2_points = $coleadership_agent_rate * self::DEFAULT_AMOUNT;
            $this->assert_amount_and_points($scott2, $scott2_points, 
                    $scott2_amount, "1st Gen Piper->Kelly->John->Scott");
        }
        
        /**
         * Tests that commission calculations that alternate between low and high
         * will still retain the high value.  Ie 35% -> 25% -> 45% will have the
         * last commission calculated at 10%(45%-35%) and not 20% (45%-25%).
         */
        function test_process_commission_request_alternating_rates() {
//            $this->markTestSkipped(
//              'debugging.'
//            );
            // piper 35% -> amy 25% -> scott 45%
            // becomes piper 35% -> amy 0% -> scott 10% in terms of commission calculations
            $agent_dal_stub = $this->get_agent_basic_dal_mock();
            $commission_dal_stub = $this->get_commission_dal_mock();
            $piper_id = self::DEFAULT_CHILD_AGENT_ID;
            $amy_id = $piper_id + 1;
            $scott_id = $piper_id + 2;
            
            
            $agent_dal_stub->method('get_parent_agent_id')
                    ->will($this->returnValueMap([
                        [$piper_id, $amy_id]
                        ,[$amy_id, $scott_id]
                    ]));
            
            $rate_map = [
                [$piper_id, .35]
                ,[$amy_id, .25]
                ,[$scott_id, .45]
            ];
            
            $agent_dal_stub->method('get_agent_commission_rate')
                    ->will($this->returnValueMap($rate_map));
            
            $agent_dal_stub->method('get_agent_rank')
                    ->will($this->returnValueMap([
                        [$piper_id, self::DEFAULT_RANK_ID]
                        ,[$amy_id, self::DEFAULT_RANK_ID]
                        ,[$scott_id, self::DEFAULT_RANK_ID]
                    ]));
            
            $commission_dal_stub->method('add_commission')
                    ->willReturn(self::DEFAULT_COMMISSION_ID);
            
            $request = $this->get_default_new_request();
            $object = new Commission_Processor($commission_dal_stub, 
                    $agent_dal_stub, $this->get_settings_dal_mock());

            $commissions_added = $object->process_commission_request($request);
            
            $this->assertEquals(3, count($commissions_added), 
                    "piper($piper_id),amy($amy_id),scott($scott_id) commissions should be added");
            
            $piper = $commissions_added[0];
            $this->assert_amount_and_points($piper, self::DEFAULT_AMOUNT, 350, "Piper");
            
            $amy = $commissions_added[1];
            $this->assert_amount_and_points($amy, self::DEFAULT_AMOUNT, 0, "Piper->Amy");
            
            $scott = $commissions_added[2];
            $this->assert_amount_and_points($scott, self::DEFAULT_AMOUNT, 100, "Piper->Amy->Scott");
        }
        
        function assert_amount_and_points($commission, $points, $amount, $messagePrefix) {
            $this->assertEquals($amount, $commission['amount'],
                "$messagePrefix should have the right amount");
            $this->assertEquals($points, $commission['points'],
                "$messagePrefix should have the right points");
        }
        
        function get_generation_rate_map() {
            $gen1_rate = .17;
            $gen2_rate = .09;
            $gen3_rate = .04;
            $gen_rate_map = [
                [1, $gen1_rate]
                ,[2, $gen2_rate]
                ,[3, $gen3_rate]
            ];
            return $gen_rate_map;
        }
        
}
