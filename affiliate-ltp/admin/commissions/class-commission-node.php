<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

/**
 * Description of class-commission-node
 *
 * @author snielson
 */
class Commission_Node {
    /**
     * @var Agent_Data
     */
    public $agent;
   
    
    /**
     *
     * @var Commission_Node 
     */
    public $parent_node;

    /**
     *
     * @var Commission_Node 
     */
    public $coleadership_node;
    
    /**
     *
     * @var double
     */
    public $coleadership_rate;
    
    /**
     *
     * @var boolean 
     */
    public $is_direct_sale;
    
    public $generational_count = 0;
    
    public $split_rate = 0;
    
    /**
     * The real rate that was used to calculate this commission.
     * Can be the agent rate or an adjusted rate.
     * @var double
     */
    public $rate;
    
    public function is_generational_partner() {
        return $this->agent->is_partner && $this->generational_count > 0;
    }
}
