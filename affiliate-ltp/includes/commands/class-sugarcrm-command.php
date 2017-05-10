<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\commands;

use AffiliateLTP\Sugar_CRM_DAL;

/**
 * Description of class-sugarcrm-command
 *
 * @author snielson
 */
class SugarCRM_Command extends \WP_CLI_Command {
    
    public function __construct() {
        $this->dal = new Sugar_CRM_DAL();
    }
    
    /**
     * Searches the CRM for
     * ## OPTIONS
     *
     * <contract_number>
     * : The contract number of the client
     *
     * ## EXAMPLES
     *
     *     wp sugarcrm search-client #11
     * 
     * 
     * @subcommand search-client
     * @param type $args
     */
    public function search_client( $args ) {
        
        list ($contract_number) = $args;
        
        if (empty($contract_number)) {
            WP_CLI::error("Contract number invalid: $contract_number");
        }
        
        $results = $this->dal->searchAccounts($contract_number);
        var_dump($results);
        
    }
    
    /**
     * Creates a sample test client to be used to verify the sugarcrm api is working.
     * ## OPTIONS
     *
     * <contract_number>
     * : The contract number of the client
     *
     * ## EXAMPLES
     *
     *     wp sugarcrm create-test-client #11
     * 
     * 
     * @subcommand create-test-client
     * @param type $args
     */
    public function create_client( $args ) {
         list ($contract_number) = $args;
        
        if (empty($contract_number)) {
            WP_CLI::error("Contract number invalid: $contract_number");
        }
        
        $client = [
            "contract_number" => "$contract_number"
            ,"name" => "John Smith"
            ,"description" => "insurance description"
            ,"street_address" => "251 Acme Street."
            ,"city" => "Somewhere"
            ,"state" => "UT"
            ,"state_of_sale" => "ID"
            ,"zip" => "84663"
            ,"country" => "USA"
            ,"phone" => "(801) 555-5555"
            ,"email" => "john@example.com"
        ];
        $results = $this->dal->createAccount($client);
        var_dump($results);
    }
}
