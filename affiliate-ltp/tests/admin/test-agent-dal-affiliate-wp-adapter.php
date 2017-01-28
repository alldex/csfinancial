<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

//use AffiliateLTP\admin\Agent_DAL_Affiliate_WP_Adapter;

require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/admin/class-agent-dal.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/admin/class-agent-dal-affiliate-wp-adapter.php';

/**
 * Test the Agent DAL Affiliate WP Adapter
 *
 * @author snielson
 */
class Test_Agent_DAL_Affiliate_WP_Adapter extends \WP_UnitTestCase {
     function test_get_coleadership_sponsored_agent_ids() {
         
         affiliate_wp()->affiliates->create_table();
         affiliate_wp()->affiliate_meta->create_table();
         
         $email = "test-user@example.com";
         $coleadership_email = 'coleadership@example.com';
         $user_id = wp_create_user("test-user", "test-password", $email);
         $coleadership_user_id = wp_create_user("coleadership-user", "password", $coleadership_email);
         
         $affiliate_id = affiliate_wp()->affiliates->add([
             'payment_email' => $email
             ,'user_id' => $user_id
         ]);
         
         $coleadership_id = affiliate_wp()->affiliates->add([
             'payment_email' => $coleadership_email
             ,'user_id' => $coleadership_user_id
         ]);
         
         affiliate_wp()->affiliate_meta->add_meta($affiliate_id, 
                 Agent_DAL_Affiliate_WP_Adapter::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID, 
                 $coleadership_id);
         
         $agent_dal = new Agent_DAL_Affiliate_WP_Adapter();
         $ids = $agent_dal->get_coleadership_sponsored_agent_ids($coleadership_id);
         $this->assertEquals([$affiliate_id], $ids, "affiliate id for coleadership should have been found");
     }
}
