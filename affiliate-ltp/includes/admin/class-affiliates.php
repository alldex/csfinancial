<?php

namespace AffiliateLTP\admin;

/**
 * Description of class-affiliates
 * TODO: stephen merge this class into the Agent_DAL class at some point.
 *
 * @author snielson
 */
class Affiliates {
    
    /**
     * Used to store the agent id for a single table row item while
     * the table is looping through the agent columns.  The agent_id can then
     * be used to retrieve additional metadata information about the agent
     * that we can't access since the affiliate table default column only passes
     * the column value.
     * @var int
     */
    private $table_row_agent_id;
    
    public function __construct() {
        // add options to the edit affiliate screen
        add_action( 'affwp_edit_affiliate_end', array( $this, 'add_agent_fields_to_edit_screen' ) );

        // update affiliate from edit affiliate screen
        add_action( 'affwp_update_affiliate', array( $this, 'updateAffiliateLifeInsuranceData' ), -1 );
        add_action( 'affwp_update_affiliate', array( $this, 'update_agent_phone' ), -1 );

        // allow admin to set custom slug when manually adding an affiliate
        add_action( 'affwp_new_affiliate_end', array( $this, 'add_agent_fields_to_new_screen' ) );
        add_action( 'affwp_insert_affiliate', array( $this, 'insert_agent_data' ) );
        
        add_filter( 'affwp_affiliate_table_columns', array( $this, 'update_agent_columns' ), 10, 1 );
        
        // the username is rendered before the phone... we have no access to the agent info during the phone filter
        // so unfortunately we store it with this HACK.
        // TODO: stephen is there a better way we can pass the agent?
        add_filter( 'affwp_affiliate_table_username', array($this, 'store_agent_id_from_username' ), 10, 2 );
        add_filter( 'affwp_affiliate_table_phone', array( $this, 'render_agent_phone' ), 10, 1 );
    }
    
    public function store_agent_id_from_username( $username, $agent ) {
        $this->table_row_agent_id = $agent->affiliate_id;
        return $username;
    }
    
    public function render_agent_phone( $value ) {
        $agent_id = $this->table_row_agent_id;
        $phone = affwp_get_affiliate_meta($agent_id, 'cell_phone', true);
        echo $phone;
    }
    
    public function update_agent_columns( $columns ) {
        foreach (['unpaid', 'referrals', 'visits'] as $column) {
            if (!empty($columns[$column])) {
                unset($columns[$column]);
            }
        }
        $columns['phone'] = __("Phone", "affiliate-ltp");
        return $columns;
    }
    
    public function add_agent_fields_to_edit_screen( $affiliate ) {
        $coleadership_agent_rates = $this->get_coleadership_agent_rate_options();
        $coleadership_agent_id = esc_attr(affwp_get_affiliate_meta( $affiliate->affiliate_id, 'coleadership_agent_id', true) );
        $coleadership_agent_rate = esc_attr( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'coleadership_agent_rate', true) );
        
        $phone = esc_attr( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'cell_phone', true) );
        
        $coleadership_user_id = '';
        $coleadership_username = '';
        if (!empty($coleadership_agent_id)) {
            $coleadership_user_id = esc_attr( affwp_get_affiliate_user_id( $coleadership_agent_id ) );
            $coleadership_username = esc_attr( affwp_get_affiliate_username( $coleadership_agent_id ) );
        }
        
        $licenseNumber = esc_attr( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'life_license_number', true ) );
        $expirationDate = esc_attr( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'life_expiration_date', true ) );
        $templatePath = affiliate_wp()->templates->get_template_part('admin-affiliate', 'edit', false);
        include_once $templatePath;
    }
    
    public function add_agent_fields_to_new_screen() {
        $templatePath = affiliate_wp()->templates->get_template_part('admin-affiliate', 'new', false);
        $coleadership_agent_rates = $this->get_coleadership_agent_rate_options();
        include_once $templatePath;
    }
    
    public function update_agent_phone($data) {
         $agent_id = $data['affiliate_id'];
         $phone = filter_input(INPUT_POST, 'phone');
         affwp_delete_affiliate_meta($agent_id, 'cell_phone');
         affwp_add_affiliate_meta($agent_id, 'cell_phone', $phone, true);
    }
    
    public function updateAffiliateLifeInsuranceData( $data ) {
        $affiliateId = $data['affiliate_id'];
        $prevLicenseNumber = affwp_get_affiliate_meta( $affiliateId, 'life_license_number');
        $licenseNumber = filter_input(INPUT_POST, 'life_license_number');
        $expirationDate = filter_input(INPUT_POST, 'life_expiration_date');
        $coleadership_user_id = filter_input(INPUT_POST, 'coleadership_user_id');
        $coleadership_agent_rate = absint(filter_input(INPUT_POST, 'coleadership_agent_rate'));
        $coleadership_agent_id = empty($coleadership_user_id) ? null : affwp_get_affiliate_id($coleadership_user_id);
        
        if (empty($licenseNumber)) {
            affwp_delete_affiliate_meta($affiliateId, 'life_license_number');
            affwp_delete_affiliate_meta($affiliateId, 'life_expiration_date');
        }
        else if (empty($prevLicenseNumber)) {
            affwp_add_affiliate_meta($affiliateId, 'life_license_number', $licenseNumber, true);
            affwp_add_affiliate_meta($affiliateId, 'life_expiration_date', $expirationDate, true);
        }
        else {
            affwp_update_affiliate_meta($affiliateId, 'life_license_number', $licenseNumber);
            affwp_update_affiliate_meta($affiliateId, 'life_expiration_date', $expirationDate);
        }
        
        if (empty($coleadership_agent_id) || $coleadership_agent_rate <= 0) {
            affwp_delete_affiliate_meta($affiliateId, 'coleadership_agent_id');
            affwp_delete_affiliate_meta($affiliateId, 'coleadership_agent_rate');
        }
        else {
            affwp_update_affiliate_meta($affiliateId, 'coleadership_agent_id', $coleadership_agent_id);
            affwp_update_affiliate_meta($affiliateId, 'coleadership_agent_rate', $coleadership_agent_rate);
        }
    }
    
    public function insert_agent_data( $affiliate ) {
        $licenseNumber = filter_input(INPUT_POST, 'life_license_number');
        $expirationDate = filter_input(INPUT_POST, 'life_expiration_date');
        $coleadership_user_id = filter_input(INPUT_POST, 'coleadership_user_id');
        $coleadership_agent_rate = absint(filter_input(INPUT_POST, 'coleadership_agent_rate'));
        
        if (!empty($licenseNumber)) {
            affwp_add_affiliate_meta($affiliate->affiliate_id, 'life_license_number', $licenseNumber, true);
            affwp_add_affiliate_meta($affiliate->affiliate_id, 'life_expiration_date', $expirationDate, true);
        }
        
        if (!empty($coleadership_user_id)) {
            $agent_id = affwp_get_affiliate_id($coleadership_user_id);
            if (!empty($agent_id) && $coleadership_agent_rate > 0) {
                affwp_add_affiliate_meta($affiliate->affiliate_id, 'coleadership_agent_id', $agent_id, true);
                affwp_add_affiliate_meta($affiliate->affiliate_id, 'coleadership_agent_rate', $coleadership_agent_rate, true);
            }
            else {
                error_log("agent_id not found for $coleadership_user_id or rate invalid with $coleadership_agent_rate");
            }
        }
        
        $phone = filter_input(INPUT_POST, 'phone');
        affwp_add_affiliate_meta($affiliate->affiliate_id, 'cell_phone', $phone, true);
    }
    
    public static function isAffiliateCurrentlyLifeLicensed( $affiliateId ) {
        $expirationDate = affwp_get_affiliate_meta( $affiliateId, 'life_expiration_date', true);
        if (!empty($expirationDate)) {
            
            $time = strtotime($expirationDate);
            // if they are still farther out than today's date then we are good.
            if ($time >= time()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * The rate options that can be used for the coleadership
     * @return array
     */
    private function get_coleadership_agent_rate_options() {
        return [
            "75" => "75% / 25%"
            ,"50" => "50% / 50%"
        ];
    }
}