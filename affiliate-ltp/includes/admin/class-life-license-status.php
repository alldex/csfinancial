<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Represents the status of an agent's life licensing.
 *
 * @author snielson
 */
class Life_License_Status {
    private $date_licensed = null;
    private $active_license = false;
    private $license_number = null;
    private $state_licenses = [];
    
    public function __construct($license_number = null, $date_licensed = null, $state_licenses = []) {
        $this->license_number = $license_number;
        $this->date_licensed = $date_licensed;
        $this->state_licenses = $state_licenses;
        
        if ($this->has_license_number() && $this->is_license_date_active()) {
            $this->active_license = true;
        }
    }
    
    public function has_license($check_state) {
        return $this->has_license_number() && $this->is_licensed_in_state($check_state);
    }
    
    public function has_active_licensed($check_state) {
        return $this->active_license && $this->is_licensed_in_state($check_state);
    }
    
    public function get_license_date() {
        return $this->date_licensed;
    }
    
    private function has_license_number() {
        return !empty($this->license_number);
    }
    
    private function is_licensed_in_state($check_state) {
        if (empty($check_state)) {
            throw new \InvalidArgumentException("state license check: state to check cannot be empty");
        }
        
        return !empty($this->state_licenses[$check_state]);
    }
    
    private function is_license_date_active() {
        $expiration_date = $this->get_license_date();
        if (!empty($expiration_date)) {
            $time = strtotime($expiration_date);
            // if they are still farther out than today's date then we are good.
            if ($time >= time()) {
                return true;
            }
        }
        return false;
    }
}
