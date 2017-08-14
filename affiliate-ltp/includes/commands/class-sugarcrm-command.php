<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\commands;

use AffiliateLTP\Sugar_CRM_DAL;
use AffiliateLTP\admin\Commission_DAL;

/**
 * Description of class-sugarcrm-command
 *
 * @author snielson
 */
class SugarCRM_Command extends \WP_CLI_Command {
    
    /**
     *
     * @var Sugar_CRM_DAL
     */
    private $sugar_crm_dal;
    
    /**
     *
     * @var Commission_DAL 
     */
    private $commission_dal;
    
    public function __construct(Sugar_CRM_DAL $dal, Commission_DAL $commission_dal) {
        $this->sugar_crm_dal = new $dal;
        $this->commission_dal = $commission_dal;
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
     * Searches the CRM for
     * ## OPTIONS
     *
     * ## EXAMPLES
     *
     *     wp sugarcrm sync-client-names
     * 
     * 
     * @subcommand sync-client-names
     * @param type $args
     */
    public function sync_client_names( $args ) {
        $offset = 0;
        $limit = 100;
        $results = [];
        $sort = [];
        $filter = [];
        do {
            $results = $this->commission_dal->get_commission_requests($sort, $filter, $limit, $offset);
            if (!$results) {
                break;
            }
            foreach ($results as $result) {
                $this->update_commissions_for_contract($result->contract_number);
                
            }
            $offset += $limit + 1;
        }
        while (!empty($results));
    }
    
    private function update_commissions_for_contract( $contract_number ) {
        $commissions = $this->commission_dal->get_commissions_by_contract($contract_number);
        foreach ($commissions as $commission) {
            try {
                $client_name = $this->get_client_name_from_commission($commission);
                $this->commission_dal->delete_commission_meta($commission->commission_id, 'client_name');
                $this->commission_dal->add_commission_meta($commission->commission_id, 'client_name', $client_name);
                echo "Commission({$commission->commission_id}) updated with name: $client_name\n";
            }
            catch (\Exception $ex) {
                error_log("Failed to update commission {$commission->commission_id} with client name $client_name ");
            }
        }
    }
    
    private function get_client_name_from_commission($commission) {
        $client_id = $this->commission_dal->get_commission_client_id($commission->commission_id);
        if (!empty($client_id)) {
            $client = $this->sugar_crm_dal->getAccountById($client_id);
            return $client["name"];
        }
        throw new \RuntimeException("Client name to sync for commission {$commission->commission_id} could not be found");
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
