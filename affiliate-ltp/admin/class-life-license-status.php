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
    
    public function __construct($license_number = null, $date_licensed = null) {
        $this->license_number = $license_number;
        $this->date_licensed = $date_licensed;
        
        if ($this->has_license() && $this->is_license_date_active()) {
            $this->active_license = true;
        }
    }
    
    public function has_license() {
        return !empty($this->license_number);
    }
    
    public function has_active_licensed() {
        return $this->active_license;
    }
    
    public function get_license_date() {
        return $this->date_licensed;
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
