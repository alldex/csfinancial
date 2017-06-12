<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

use AffiliateLTP\Agent_Tree_Node_Filterer;


/**
 * Allows you to combine filters and treat this object as a single filter.
 * Follows the Composite pattern.
 *
 * @author snielson
 */
class Agent_Tree_Aggregate_Filterer implements Agent_Tree_Node_Filterer {
    private $filters;
    public function __construct() {
        $this->filters = [];
    }
    
    public function filter(Agent_Tree_Node $node) {
        foreach ($this->filters as $filter) {
            if (!$filter->filter($node)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function addFilter(Agent_Tree_Node_Filterer $filter) {
        $this->filters[] = $filter;
    }
}
