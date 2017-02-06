<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

/**
 *
 * @author snielson
 */
interface Agent_Tree_Node_Filterer {
    
    /**
     * Checks if the current node should be included or not.  If the
     * filter returns true the node is included, if the filter returns
     * false the node is excluded.
     * @param \AffiliateLTP\Agent_Tree_Node $node
     */
    function filter(Agent_Tree_Node $node);
}
