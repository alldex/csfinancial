<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Description of class-agent-points-summary-request
 *
 * @author snielson
 */
class Agent_Points_Summary_Request
{
    public $limit;
    public $date_filter;
    public $partners_only = false;
    private $agent_ids = [];
    public $personal_sales_only = true;
    public $base_shop_only = false;
    
    public function get_agent_ids() {
        return $this->agent_ids;
    }
    
    public function set_agent_ids(array $agent_ids) {
        $sanitized_ids = array_filter(array_map(intval, $agent_ids)
                , function($val) {
            return !is_nan($val);
        });
        $this->agent_ids = $sanitized_ids;
    }
    
}

