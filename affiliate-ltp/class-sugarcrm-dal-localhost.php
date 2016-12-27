<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-sugarcrm-dal-localhost
 *
 * @author snielson
 */
class SugarCRMDALLocalhost extends SugarCRMDAL {

    private static $instance = null;
    
    /**
     * Returns the singleton instance.
     * @return SugarCRMDAL
     */
    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new SugarCRMDALLocalhost();
        }
        return self::$instance;
    }
    
    public function createAccount($accountData) {
        // just return a random unique id for this proxy.
        return uniqid();
    }
    
    public function searchAccounts($searchValue = "", $limit = 5) {
        return array(0 => $this->getAccountById(0)); // return a dummy account.
    }
    
     public function getAccountById($accountId) {
        return array(
            "id" => $accountId
            ,"contract_number" => "#555-5555-5"
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
