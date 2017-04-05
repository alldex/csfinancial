<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

require_once 'csv/class-commissions-importer.php';

use AffiliateLTP\admin\csv\Commissions_Importer;
use AffiliateLTP\SugarCRMDAL;
use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\admin\Settings_DAL;

/**
 * Adds additional tools to the affiliatewp plugin.
 *
 * @author snielson
 */
class Tools {
    
    /**
     *
     * @var Commissions_Importer 
     */
    private $importer;
    
    public function __construct(Agent_DAL $agent_dal, SugarCRMDAL $sugar_dal
            , Commission_DAL $commission_dal, Settings_DAL $settings_dal ) {
        add_action( 'affwp_tools_tab_export_import', array($this, 'add_import_commissions_tool' ), 20);
        
        // add the import action
        add_action( 'affwp_import_commissions', array($this, 'process_commissions_import' ) );
        $this->importer = new Commissions_Importer($agent_dal, $sugar_dal, $commission_dal, $settings_dal);
    }
    
    public function add_import_commissions_tool() {
        $templatePath = affiliate_wp()->templates->get_template_part('admin-tools', 'commission-import', false);
        include_once $templatePath;
    }
    
    public function process_commissions_import() {
        error_log("import called");
        if( empty( $_POST['affwp_import_nonce'] ) ) {
            error_log("commission import called without nonce");
            return;
        }

	if( ! wp_verify_nonce( $_POST['affwp_import_nonce'], 'affwp_import_nonce' ) ) {
            error_log("commission import nonce failed validation");
            return;
        }

	if( ! current_user_can( 'manage_options' ) ) {
            error_log("attempted import for user without permission");
            return;
        }

        
	$extension = end( explode( '.', $_FILES['import_file']['name'] ) );

        if( $extension != 'csv' ) {
            wp_die( __( 'Please upload a valid .csv file', 'affiliate-ltp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 400 ) );
        }

	$import_file = $_FILES['import_file']['tmp_name'];

	if( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 400 ) );
	}

	// Retrieve the settings from the file and convert the json object to an array
        try {
            $filename = $_FILES['import_file']['tmp_name'];
            
            $this->importer->import_from_file($filename);
            
            $notice = 'commissions-imported';
            $message = urlencode(__('Commissions imported', 'affiliate-ltp'));
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-referrals'
                    . '&affwp_notice=' . $notice
                    . '&affwp_message=' . $message) );
            exit;
        }
        catch (\Exception $ex) {
            // TODO: stephen handle the exception
            error_log($ex);
            $message = "Commissions failed to import for the following reasons: " . $ex->getMessage();
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-tools' 
                    . '&tab=export_import&affwp_notice=commissions-import-failed' 
                    . '&affwp_message=' . rawurlencode( $message ) ) );
            exit;
        }
        
        
    }
}
