<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-affiliates
 * TODO: stephen merge this class into the Agent_DAL class at some point.
 *
 * @author snielson
 */
class AffiliateLTPAffiliates {
    public function __construct() {
        // add options to the edit affiliate screen
        add_action( 'affwp_edit_affiliate_end', array( $this, 'addLifeInsuranceFieldsToEditScreen' ) );

        // update affiliate from edit affiliate screen
        add_action( 'affwp_update_affiliate', array( $this, 'updateAffiliateLifeInsuranceData' ), -1 );

        // allow admin to set custom slug when manually adding an affiliate
        add_action( 'affwp_new_affiliate_end', array( $this, 'addLifeInsuranceFieldsToNewScreen' ) );
        add_action( 'affwp_insert_affiliate', array( $this, 'insertAffiliateLifeInsuranceData' ) );
    }
    
    public function addLifeInsuranceFieldsToEditScreen( $affiliate ) {
        $coleadership_agent_rates = $this->get_coleadership_agent_rate_options();
        $coleadership_agent_id = esc_attr(affwp_get_affiliate_meta( $affiliate->affiliate_id, 'coleadership_agent_id', true) );
        $coleadership_agent_rate = esc_attr( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'coleadership_agent_rate', true) );
        
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
    
    public function addLifeInsuranceFieldsToNewScreen() {
        $templatePath = affiliate_wp()->templates->get_template_part('admin-affiliate', 'new', false);
        $coleadership_agent_rates = $this->get_coleadership_agent_rate_options();
        include_once $templatePath;
    }
    
    public function updateAffiliateLifeInsuranceData( $data ) {
        $affiliateId = $data['affiliate_id'];
        $prevLicenseNumber = affwp_get_affiliate_meta( $affiliateId, 'life_license_number');
        $licenseNumber = filter_input(INPUT_POST, 'life_license_number');
        $expirationDate = filter_input(INPUT_POST, 'life_expiration_date');
        $coleadership_user_id = filter_input(INPUT_POST, 'coleadership_user_id');
        $coleadership_agent_rate = absint(filter_input(INPUT_POST, 'coleadership_agent_rate'));
        
        
                
        // TODO: stephen the complexity of this all sucks... fix this.
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
        
        $prev_coleadership_agent_id = affwp_get_affiliate_meta( $affiliateId, 'coleadership_agent_id', true);
        
        if (empty($coleadership_user_id) || empty($coleadership_agent_rate)) {
            affwp_delete_affiliate_meta($affiliateId, 'coleadership_agent_id');
            affwp_delete_affiliate_meta($affiliateId, 'coleadership_agent_rate');
        }
        else if (empty($prev_coleadership_agent_id)) {
            $agent_id = affwp_get_affiliate_id($coleadership_user_id);
            if (!empty($agent_id) && $coleadership_agent_rate > 0) {
                affwp_add_affiliate_meta($affiliateId, 'coleadership_agent_id', $licenseNumber, true);
                affwp_add_affiliate_meta($affiliateId, 'coleadership_agent_rate', $coleadership_agent_rate, true);
            }
        }
        else {
            $agent_id = affwp_get_affiliate_id($coleadership_user_id);
            if (!empty($agent_id) && $coleadership_agent_rate > 0) {
                affwp_update_affiliate_meta($affiliateId, 'coleadership_agent_id', $agent_id);
                affwp_update_affiliate_meta($affiliateId, 'coleadership_agent_rate', $coleadership_agent_rate);
            }
        }
    }
    
    public function insertAffiliateLifeInsuranceData( $affiliate ) {
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
new AffiliateLTPAffiliates();