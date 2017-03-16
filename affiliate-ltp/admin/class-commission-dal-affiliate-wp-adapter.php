<?php

namespace AffiliateLTP\admin;

use \Affiliate_WP_Referral_Meta_DB;

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
    
    public function __construct(Affiliate_WP_Referral_Meta_DB $meta_db) {
        $this->referral_meta_db = $meta_db;
    }
    
    public function add_commission( $commission ) {
        $insert_referral = $commission;
        $insert_referral['affiliate_id'] = $commission['agent_id'];
        unset($insert_referral['agent_id']);
        unset($insert_referral['meta']);
        
        $commission_id = affiliate_wp()->referrals->add( $insert_referral );
        
        
        
        if (!empty($commission_id)) {
             // TODO: stephen should we refactor so we add in the client info here?
            foreach ($commission['meta'] as $key => $value) {
                $this->add_commission_meta($commission_id, $key, $value );
            }
//            
//            $this->add_commission_meta($commission_id, 'agent_rate', $commission['agent_rate'] );
//            $this->add_commission_meta($commission_id, 'points', $commission['points']);
            $this->connect_commission_to_client($commission_id, $commission['client']);
            
//            if (!empty($commission['coleadership_id'])) {
//                $this->add_commission_meta($commission_id, 'coleadership_id', $commission['coleadership_id']);
//            }
//            
//            if (!empty($commission['coleadership_rate'])) {
//                $this->add_commission_meta($commission_id, 'coleadership_rate', $commission['coleadership_rate']);
//            }
        }
        
        return $commission_id;
    }
    
    public function get_commission( $commission_id ) {
        return affwp_get_referral( $commission_id );
    }
    
    /**
     * Get the payout record if this commission has been paid out already to an
     * agent.
     * @param int $payout_id The id of the payout record
     */
    public function get_commission_payout( $payout_id ) {
        return affwp_get_payout( $payout_id );
    }
    
    public function add_commission_meta( $commission_id, $key, $value ) {
        $this->referral_meta_db->add_meta( $commission_id, $key, $value );
    }
    
    public function delete_commission_meta( $agent_id, $key ) {
        $this->referral_meta_db->delete_meta($agent_id, $key);
    }
    
    public function delete_commission_meta_all( $commission_id ) {
        $this->delete_commission_meta( $commission_id, 'client_id' );
        $this->delete_commission_meta( $commission_id, 'client_id' );
        $this->delete_commission_meta( $commission_id, 'client_contract_number' );
        $this->delete_commission_meta( $commission_id, 'points' );
        $this->delete_commission_meta( $commission_id, 'agent_rate' );
    }

    public function connect_commission_to_client( $commission_id, $client_data ) {
        // add the connection for the client.
        $this->add_commission_meta($commission_id, 'client_id', $client_data['id']);
        $this->add_commission_meta($commission_id, 'client_contract_number', $client_data['contract_number']);
    }

    public function get_commission_agent_rate( $commission_id ) {
        return $this->referral_meta_db->get_meta( $commission_id, 'agent_rate', true );
    }
    
    public function get_commission_agent_points($commission_id) {
        return $this->referral_meta_db->get_meta( $commission_id, 'points', true );
    }
    
    public function get_commission_client_id( $commission_id ) {
        return $this->referral_meta_db->get_meta( $commission_id, 'client_id', true );
    }
}