<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Description of class-state-dal
 *
 * @author snielson
 */
class State_DAL {
    
    private static $required_states_abbr = ["AL","CA","FL","GA","KY", "LA", 
        "MA", "MS", "MT", "NM", "PA", "SD", "UT", "VA", "WV", "WI"];
    
    private static $license_required_states = null;
    
    private static $states = null;
    
    public function get_states() {
        if (empty(self::$states)) {
            $this->init_state_lists();
        }
        
        return self::$states;
    }
    
    private function init_state_lists() {
        // this file content came from https://gist.github.com/mshafrir/2646763
        $json_states_hash = file_get_contents(AFFILIATE_LTP_PLUGIN_DIR . "assets" 
                . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "states_hash.json");
        if (!empty($json_states_hash)) {
            try {
                $states_hash = json_decode($json_states_hash, true);
                $states_list = [];
                $required_states_list = [];
                foreach ($states_hash as $abbr => $name) {
                    $state = ["name" => $name, "abbr" => $abbr];
                    $states_list[] = $state;
                    if (array_search($abbr, self::$required_states_abbr) !== false) {
                        $required_states_list[] = $state;
                    }
                }
                self::$states = $states_list;
                self::$license_required_states = $required_states_list;
            } catch (\Exception $ex) {
                error_log($ex->getMessage() . " Trace: " . $ex->getTraceAsString());
            }
        }
    }
    
    public function get_license_required_states() {
        if (empty(self::$license_required_states)) {
            $this->init_state_lists();
        }
        return self::$license_required_states;
    }
}
