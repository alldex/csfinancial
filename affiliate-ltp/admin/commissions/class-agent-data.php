<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

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
}
