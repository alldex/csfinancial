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
    // TODO: stephen should this be a static property somewhere else as we are copying it now into every object??
    private $required_state_licenses = [];
    
    public function __construct($license_number = null, $date_licensed = null, $state_licenses = []) {
        $this->license_number = $license_number;
        $this->date_licensed = $date_licensed;
        $this->state_licenses = $state_licenses;
        
        if ($this->has_license_number() && $this->is_license_date_active()) {
            $this->active_license = true;
        }
    }
    
    public function set_required_licensing_states($required_states) {
        if (!is_array($required_states)){
            throw new \InvalidArgumentException("required_states must be a valid array");
        }
        $this->required_state_licenses = $required_states;
    }
    
    public function has_license($check_state) {
        if ( $this->has_license_number() ) {
            if ($this->requires_state_license($check_state)) {
                return $this->is_licensed_in_state($check_state);
            }
            return true;
        }
        return false;
    }
    
    public function has_active_licensed($check_state) {
        if ($this->active_license) {
            if ($this->requires_state_license($check_state)) {
                return $this->is_licensed_in_state($check_state);
            }
            return true;
        }
        return false;
    }
    
    public function get_license_date() {
        return $this->date_licensed;
    }
    
    private function has_license_number() {
        return !empty($this->license_number);
    }
    
    private function requires_state_license($check_state) {
        return !empty($this->required_state_licenses[$check_state]);
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
