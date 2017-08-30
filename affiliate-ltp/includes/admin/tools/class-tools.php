<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\tools;

use Psr\Log\LoggerInterface;
use AffiliateLTP\admin\tools\Commissions_Importer;

/**
 * Adds additional tools to the affiliatewp plugin.
 *
 * @author snielson
 */
class Tools implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    
    /**
     *
     * @var Commissions_Importer 
     */
    private $importer;
    
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(LoggerInterface $logger, Commissions_Importer $importer ) {
        $this->importer = $importer;
        $this->logger = $logger;
    }
    
    public function register_hooks_and_actions() {
        add_action( 'affwp_tools_tab_export_import', array($this, 'add_import_commissions_tool' ), 20);
        
        // add the import action
        add_action( 'affwp_import_commissions', array($this, 'process_commissions_import' ) );
    }
    
    public function add_import_commissions_tool() {
        $templatePath = affiliate_wp()->templates->get_template_part('admin-tools', 'commission-import', false);
        include_once $templatePath;
    }
    
    public function process_commissions_import() {
        $this->logger->info("import called");
        $nonce = filter_input(INPUT_POST, 'affwp_import_nonce', FILTER_SANITIZE_STRING);
        if( empty( $nonce ) ) {
            $this->logger->error("commission import called without nonce");
            return;
        }

	if( ! wp_verify_nonce( $nonce, 'affwp_import_nonce' ) ) {
            $this->logger->error("commission import nonce failed validation");
            return;
        }

	if( ! current_user_can( 'manage_options' ) ) {
            $this->logger->error("attempted import for user without permission");
            return;
        }
        
        $skip_life_validation = filter_input(INPUT_POST, 'skip_life_licensed_check', FILTER_SANITIZE_NUMBER_INT) == 1;

        
	$extension = end( explode( '.', $_FILES['import_file']['name'] ) );

        if( $extension != 'csv' ) {
            $this->logger->error("attempted import with invalid extension");
            wp_die( __( 'Please upload a valid .csv file', 'affiliate-ltp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 400 ) );
        }

	$import_file = $_FILES['import_file']['tmp_name'];

	if( empty( $import_file ) ) {
            $this->logger->error("Invalid import. tmp name missing");
		wp_die( __( 'Please upload a file to import', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 400 ) );
	}

	// Retrieve the settings from the file and convert the json object to an array
        try {
            $filename = $_FILES['import_file']['tmp_name'];
            
            $this->importer->import_from_file($filename, $skip_life_validation);
            
            $notice = 'policies_imported';
            $message = urlencode(__('Commissions imported', 'affiliate-ltp'));
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-ltp-policies'
                    . '&affwp_ltp_notice=' . $notice) );
            exit;
        }
        catch (\Exception $ex) {
            // TODO: stephen handle the exception
            $this->logger->error($ex);
            $message = "Commissions failed to import for the following reasons: " . $ex->getMessage();
            wp_safe_redirect( admin_url( 'admin.php?page=affiliate-wp-tools' 
                    . '&tab=export_import&affwp_notice=commissions-import-failed' 
                    . '&affwp_message=' . rawurlencode( $message ) ) );
            exit;
        }
        
        
    }
}
