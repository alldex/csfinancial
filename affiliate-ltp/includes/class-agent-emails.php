<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

if (!class_exists('Affiliate_WP_Emails')) {
    require_once basename(AFFILIATE_LTP_PLUGIN_DIR) . DIRECTORY_SEPARATOR 
            . "affiliate-wp" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR
            . "emails" . DIRECTORY_SEPARATOR . "class-affwp-includes.php";
}

use AffiliateLTP\admin\Agent_DAL;
use Affiliate_WP_Emails;
use AffiliateLTP\admin\Settings_DAL;

/**
 * Description of class-agent-emails
 *
 * @author snielson
 */
class Agent_Emails implements I_Register_Hooks_And_Actions {
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;
    
    /**
     * The id stating that something is a partner.
     * @var int
     */
    private $partner_rank_id;
    
    public function __construct(Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
        
    }
    
    public function register_hooks_and_actions() {
        add_action("affwp_register_user", array($this, 'email_base_shop'), 10, 3);
    }
    
    public function email_base_shop($agent_id, $status, $agent_user_data) {
        
        $base_shop_agent_ids = $this->get_agent_baseshop_upline($agent_id);
        
        // now loop through the base shop ids and we will setup some emails
        foreach ($base_shop_agent_ids as $shop_agent_id) {
	    if ($shop_agent_id == $agent_id) {
		continue;
	    }
            $this->send_base_shop_agent_registration_email($shop_agent_id, $agent_id, $agent_user_data);
        }
    }
    public function send_base_shop_agent_registration_email($shop_agent_id, $agent_id, $agent_user_data) {
        $agent_display_name = $agent_user_data['display_name'];
        $agent_username = $agent_user_data['user_login'];
        $email = $this->agent_dal->get_agent_email($shop_agent_id);
        $subject = __("Base Shop Agent Registration", 'affiliate-ltp');
	$message  = sprintf( __( '%s has registered as an agent in your base shop.  Their  username is: %s'
                , 'affiliate-ltp' ), $agent_display_name, $agent_username ) . "\n\n";
        $emails  = new Affiliate_WP_Emails;
        $emails->send($email, $subject, $message);
    }
    
    /**
     * Retrieves all of the agent upline in their base shop (agents up to the
     * partner level).
     * @param int $agent_id
     */
    private function get_agent_baseshop_upline( $agent_id ) {
        $base_shop = [];
        $upline_tree = $this->agent_dal->get_agent_upline($agent_id);
        
        if ( !empty( $upline_tree ) ) {
            $this->aggregate_base_shop_ids($upline_tree, $base_shop);
        }
        
        // remove duplicate users
        $base_shop = array_unique($base_shop);
        
        return $base_shop;
    }
    private function aggregate_base_shop_ids($node, &$base_shop) {
        if (empty($node)) {
            return;
        } else if (!$node instanceof admin\Agent_Node) {
            throw new \BadMethodCallException("$node was not an instance of Agent_Node");
        }
        
        $base_shop[] = $node->id;
        
        if ($this->agent_dal->get_agent_rank($node->id) == $this->get_partner_rank_id() ) {
            return;
        }
        $this->aggregate_base_shop_ids($node->parent, $base_shop);
        $this->aggregate_base_shop_ids($node->coleadership, $base_shop);
    }
    
    private function get_partner_rank_id() {
        if (empty($this->partner_rank_id)) {
            $this->partner_rank_id = $this->settings_dal->get_partner_rank_id();
        }
        return $this->partner_rank_id;
    }
}
