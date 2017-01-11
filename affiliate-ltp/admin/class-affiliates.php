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
        $licenseNumber = esc_attr( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'life_license_number', true ) );
        $expirationDate = esc_attr( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'life_expiration_date', true ) );
        $templatePath = affiliate_wp()->templates->get_template_part('admin-affiliate', 'edit', false);
        include_once $templatePath;
    }
    
    public function addLifeInsuranceFieldsToNewScreen() {
        $templatePath = affiliate_wp()->templates->get_template_part('admin-affiliate', 'new', false);
        include_once $templatePath;
    }
    
    public function updateAffiliateLifeInsuranceData( $data ) {
        $affiliateId = $data['affiliate_id'];
        $prevLicenseNumber = affwp_get_affiliate_meta( $affiliateId, 'life_license_number');
        $licenseNumber = filter_input(INPUT_POST, 'life_license_number');
        $expirationDate = filter_input(INPUT_POST, 'life_expiration_date');
        
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
    }
    
    public function insertAffiliateLifeInsuranceData( $affiliate ) {
        $licenseNumber = filter_input(INPUT_POST, 'life_license_number');
        $expirationDate = filter_input(INPUT_POST, 'life_expiration_date');
        
        if (!empty($licenseNumber)) {
            affwp_add_affiliate_meta($affiliate->affiliate_id, 'life_license_number', $licenseNumber, true);
            affwp_add_affiliate_meta($affiliate->affiliate_id, 'life_expiration_date', $expirationDate, true);
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
}
new AffiliateLTPAffiliates();