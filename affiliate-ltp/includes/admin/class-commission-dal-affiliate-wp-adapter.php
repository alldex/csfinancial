<?php

namespace AffiliateLTP\admin;

use AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB;
use AffiliateLTP\Commission_Type;
use AffiliateLTP\Commission_Request_DB;
use AffiliateLTP\Commission;

/**
 * Wraps around all of the affiliate_wp() methods to implement what we need
 * for the commission dal.
 *
 * @author snielson
 */
class Commission_Dal_Affiliate_WP_Adapter implements Commission_DAL {

    /**
     * The affiliate referral meta database handler that saves referral records
     * back and forth to the datbase.
     * @var Affiliate_WP_Referral_Meta_DB
     */
    private $referral_meta_db;

    /**
     * Handles the saving and creating of commission request objects.
     * @var Commission_Request_DB
     */
    private $commission_request_db;

    /**
     * Cache of the commission request during the php request lifecycle
     * Requests are cached by their commission_request_id
     * @var array
     */
    private $commission_request_cache_by_id;

    public function __construct(Affiliate_WP_Referral_Meta_DB $meta_db) {
        $this->referral_meta_db = $meta_db;
        $this->commission_request_db = new Commission_Request_DB();
        $this->commission_request_cache_by_id = [];
    }

    public function add_commission_request($commission_request) {
        $record_id = $this->commission_request_db->add($commission_request);
        return $record_id;
    }

    public function add_commission($commission) {
        
        if ($commission instanceof Commission) {
            $insert_referral = $this->adapt_commission_to_referral($commission);
        }
        else {
            $insert_referral = $commission;
            
        }
        
        $meta = $insert_referral['meta'];
        $insert_referral['affiliate_id'] = $insert_referral['agent_id'];
        $client = $insert_referral['client'];
        unset($insert_referral['agent_id']);
        unset($insert_referral['meta']);
        unset($insert_referral['client']);
        if (empty($insert_referral['payout_id'])) {
            unset($insert_referral['payout_id']);
        }

        $commission_id = affiliate_wp()->referrals->add($insert_referral);

        if (!empty($commission_id)) {
            // TODO: stephen should we refactor so we add in the client info here?
            foreach ($meta as $key => $value) {
                $this->add_commission_meta($commission_id, $key, $value);
            }
//            
            $this->connect_commission_to_client($commission_id, $client);
        }

        return $commission_id;
    }

    public function get_commission($commission_id) {
        $referral = affwp_get_referral($commission_id);
        if (empty($referral)) {
            return null;
        }
        
        $commission = new Commission();
        $commission->commission_id = $referral->referral_id;
        $commission->agent_id = $referral->affiliate_id;
        $vars = get_object_vars($referral);
        foreach ($vars as $key => $value) {
            if (property_exists($commission, $key)) {
                $commission->$key = $value;
            }
        }
        return $commission;
    }
    
    private function adapt_commission_to_referral( Commission $commission ) {
        // TODO: stephen is there an easier function to convert public class props to an array?
        $vars = get_object_vars($commission);
        $referral = [];
        foreach ($vars as $key => $value) {
            $referral[$key] = $value;
        }
        return $referral;
    }

    /**
     * Get the payout record if this commission has been paid out already to an
     * agent.
     * @param int $payout_id The id of the payout record
     */
    public function get_commission_payout($payout_id) {
        return affwp_get_payout($payout_id);
    }

    public function add_commission_meta($commission_id, $key, $value) {
        $this->referral_meta_db->add_meta($commission_id, $key, $value);
    }

    public function delete_commission_meta($agent_id, $key) {
        $this->referral_meta_db->delete_meta($agent_id, $key);
    }

    public function delete_commission_meta_all($commission_id) {
        // TODO: stephen this is so silly... look at making this into a SQL query
        $this->delete_commission_meta($commission_id, 'client_id');
        $this->delete_commission_meta($commission_id, 'client_contract_number');
        $this->delete_commission_meta($commission_id, 'company_commission');
        $this->delete_commission_meta($commission_id, 'company_commission');
        $this->delete_commission_meta($commission_id, 'points');
        $this->delete_commission_meta($commission_id, 'agent_rate');
        $this->delete_commission_meta($commission_id, 'commission_request_id');
        $this->delete_commission_meta($commission_id, 'agent_parent_id');
        $this->delete_commission_meta($commission_id, 'generation_count');
        $this->delete_commission_meta($commission_id, 'new_business');
        $this->delete_commission_meta($commission_id, 'original_amount');
        $this->delete_commission_meta($commission_id, 'rank_id');
        $this->delete_commission_meta($commission_id, 'split_rate');
        $this->delete_commission_meta($commission_id, 'agent_real_rate');
        $this->delete_commission_meta($commission_id, 'chargeback_commission_id');
    }

    public function connect_commission_to_client($commission_id, $client_data) {
        // add the connection for the client.
        $this->add_commission_meta($commission_id, 'client_id', $client_data['id']);
        $this->add_commission_meta($commission_id, 'client_contract_number', $client_data['contract_number']);
    }

    public function get_commission_agent_rate($commission_id) {
        return $this->referral_meta_db->get_meta($commission_id, 'agent_rate', true);
    }

    public function get_commission_agent_points($commission_id) {
        return $this->referral_meta_db->get_meta($commission_id, 'points', true);
    }

    public function get_commission_client_id($commission_id) {
        return $this->referral_meta_db->get_meta($commission_id, 'client_id', true);
    }

    public function get_repeat_commission_data($contract_number) {
        // select * FROM wp_affiliate_wp_referrals ref
        // JOIN wp_affiliate_wp_referralmeta refm ON ref.referral_id = refm.referral_id
        // WHERE ref.referral_id IN (
        // select DISTINCT r.referral_id from wp_affiliate_wp_referrals r 
        // JOIN wp_affiliate_wp_referralmeta rm ON r.referral_id = rm.referral_id
        // WHERE r.reference = '$contract_number'
        // AND meta_key = 'new_business' and meta_value = 'Y'
        // )
        // ORDER BY ref.date asc
        // complex query here...
//        $results = $this->get_repeat_commission_from_database( $contract_number );
        $commission_request = $this->get_repeat_commission_record($contract_number);
        if (empty($commission_request)) {
            return null;
        }

        $hydrated_request = json_decode($commission_request['request']);
        $hydrated_request->commission_request_id = $commission_request['commission_request_id'];
        return $hydrated_request;
    }

    public function get_repeat_commission_record($contract_number) {
        $commission_request = $this->commission_request_db->get_new_commission_request($contract_number);
        if (empty($commission_request)) {
            return null;
        }
        return $commission_request;
    }

    public function get_commission_request_id_from_commission($commission_id) {
        $id = $this->referral_meta_db->get_meta($commission_id, 'commission_request_id', true);
        if (empty($id)) {
            return null;
        }
        return absint($id);
    }

    public function get_commission_request($commission_request_id) {
        if (empty($this->commission_request_cache_by_id[$commission_request_id])) {
            $this->commission_request_cache_by_id[$commission_request_id] = $this->commission_request_db->get_commission_request($commission_request_id);
        }

        return $this->commission_request_cache_by_id[$commission_request_id];
    }

    public function delete_commissions_for_request($commission_request_id) {
        // first clear out the cache
        if (!empty($this->commission_request_cache_by_id[$commission_request_id])) {
            unset($this->commission_request_cache_by_id[$commission_request_id]);
        }

        $commission_ids = $this->referral_meta_db->get_commission_ids_by_commission_request_id($commission_request_id);
        if (empty($commission_ids)) {
            return $this->delete_commission_request($commission_request_id);
        }

        foreach ($commission_ids as $commission_id) {
            // TODO
            if (!affwp_delete_referral($commission_id)) {
                error_log("Failed to delete referral with id $commission_id");
                return false;
            }
        }
        return $this->delete_commission_request($commission_request_id);
    }

    public function get_commission_ids_for_request($commission_request_id) {
        return $this->referral_meta_db->get_commission_ids_by_commission_request_id($commission_request_id);
    }

    public function delete_commission_request($commission_request_id) {
        if ($this->commission_request_db->delete($commission_request_id)) {
            return true;
        }
        return false;
    }

}
