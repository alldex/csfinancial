<?php

namespace AffiliateLTP\admin;

use \Affiliate_WP_Referral_Meta_DB;
use AffiliateLTP\CommissionType;

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
    
    public function get_repeat_commission_data($contract_number, $include_override = false) {
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
        
        $results = $this->get_repeat_commission_from_database( $contract_number, $include_override );
        
        if (!empty($results)) {
            $commission = $this->convert_results_to_commission_data( $results, $contract_number );
            return $commission;
        }
        
        return null;
    }
    
    private function get_repeat_commission_from_database($contract_number, $include_override) {
        // if we want override sales agents here.
        $direct_sales_clause = $include_override  ? '' : "r.custom = 'direct' AND ";
        
        $sql = <<<EOD
select 
    a.affiliate_id, a.user_id, wu.user_email,
    ref.referral_id,ref.status,ref.amount,ref.custom, ref.context,
    ref.reference,ref.date, 
    refm.meta_id,refm.meta_key,refm.meta_value
FROM
    wp_affiliate_wp_referrals ref
    JOIN 
        wp_affiliate_wp_referralmeta refm 
            ON ref.referral_id = refm.referral_id
    JOIN 
        wp_affiliate_wp_affiliates a 
            ON ref.affiliate_id = a.affiliate_id
    JOIN
        wp_users wu
            ON a.user_id = wu.ID
WHERE 
    ref.referral_id IN (
        select 
            DISTINCT r.referral_id 
        FROM 
            wp_affiliate_wp_referrals r 
        JOIN 
            wp_affiliate_wp_referralmeta rm 
                ON r.referral_id = rm.referral_id
WHERE 
    $direct_sales_clause r.reference = '%s'
    AND meta_key = 'new_business' and meta_value = 'Y'
)
ORDER BY 
    ref.date asc
EOD;
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare($sql, $contract_number), ARRAY_A);
        if ($results->num_rows > 0) {
            error_log('rows returned ' . $results->num_rows);
            return $results;
        }
        else {
            error_log('num rows was 0');
        }
        return null;
    }
    
    private function convert_results_to_commission_data($results, $contract_number) {
        $commission = [
            "agents" => []
            ,"writing_agent" => []
            ,"contract_number" => $contract_number
            ,"is_life_commission" => true
            ,"split_commission" => false
        ];
        
         // the writing agent is the first agent of the commission created
         // we need to make sure he is first.
        $ordered_ids = [];
        $records_by_agent = array();
        $current_agent = null;
        foreach ($results as $record) {
            if ($record['affiliate_id'] != $current_agent) {
                $current_agent = $record['affiliate_id'];
                if (empty($records_by_agent[$current_agent])) {
                    error_log("adding agent $current_agent");
                    $records_by_agent[$current_agent] = [
                        "agent_id" => $current_agent
                        ,"user_id" => $record['user_id']
                        ,'email' => $record['user_email']
                    ];
                    $ordered_ids[] = $current_agent;
                }
            }
            // all of the contexts should be the same
            $commission['is_life_commission'] = absint($record['context']) == CommissionType::TYPE_LIFE;
            
            // this assumes there is only one value for the meta which there
            // should only be one value. First record takes precedence if there
            // are duplicate meta_keys
            $meta_key = $record['meta_key'];
            $meta_value = $record['meta_value'];
            if (empty($records_by_agent[$current_agent][$meta_key])) {
                $records_by_agent[$current_agent][$meta_key] = $meta_value;
            }
        }
        $commission['writing_agent'] = $records_by_agent[array_shift($ordered_ids)];
        $commission['agents'] = array_map(function($id) use($records_by_agent) {
                                            return $records_by_agent[$id]; }
                                , $ordered_ids);
        if (!empty($commission['agents'])) {
            $commission['split_commission'] = true;
        }
        return $commission;
    }
}