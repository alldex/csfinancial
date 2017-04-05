<?php
namespace AffiliateLTP;

/**
 * Description of class-sugarcrm-dal-localhost
 *
 * @author snielson
 */
class Sugar_CRM_DAL_Localhost extends Sugar_CRM_DAL {

    private static $instance = null;
    
    /**
     * Returns the singleton instance.
     * @return Sugar_CRM_DAL
     */
    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new Sugar_CRM_DAL_Localhost();
        }
        return self::$instance;
    }
    
    public function createAccount($accountData) {
        // just return a random unique id for this proxy.
        return uniqid();
    }
    
    public function searchAccounts($searchValue = "#1", $limit = 5) {
       $results = [];
       error_log("searching $searchValue");
       $startValue = preg_replace("/[^a-z0-9\-]+/i","",$searchValue);
       
        for ($count = 2; $count < $limit+2; $count++) {
            $contract_number = "#" 
                    . str_repeat($startValue, $count);
            $results[] = $this->getAccountById($contract_number);
        }
       
        return $results;
    }
    
     public function getAccountById($accountId) {
         
        return array(
            "id" => $accountId
            ,"contract_number" => $accountId
            ,"agent_id" => "1"
            ,"name" => "Stephen Nielson"
            ,"description" => "Some description"
            ,"street_address" => "105 S 3rd E"
            ,"city" => "Rexburg"
            ,"zip" => "84663"
            ,"country" => "USA"
            ,"phone" => "801 610-9014"
            ,"email" => "stephen@nielson.org"
        );
    }
}
