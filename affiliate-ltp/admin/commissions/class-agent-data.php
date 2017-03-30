<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;
use AffiliateLTP\admin\Life_License_Status;

/**
 * Description of agent-data
 *
 * @author snielson
 */
class Agent_Data {
    public $id;
    public $rank;
    public $rate = 0;
    
    /**
     *
     * @var Life_License_Status
     */
    public $life_license_status;
    
    /**
     * Whether the agent is a partner or not (for generational counting purposes)
     * @var boolean
     */
    public $is_partner = false;
    
    /**
     * Returns whether the agent is active (hasn't been disabled)
     * @var boolean
     */
    public $is_active = false;
}
