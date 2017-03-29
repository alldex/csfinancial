<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;
use AffiliateLTP\admin\commissions\Commission_Node;

/**
 * Runs through the tree and changes the agent rate to be what the actual rate
 * is based upon the rate heirarchy.  For example if a child node is 25% and
 * the current node is 25%, the real rate is 0.  If the current node's rate is
 * 45% the real rate is 20%.
 *
 * @author snielson
 */
class Real_Rate_Calculate_Transformer {
    
    /**
     * Clones and updates all of the agent rates for the tree heirachy
     * @param Commission_Node $tree
     * @return type
     */
    public function transform(Commission_Node $tree) {
        return $this->update_parent(0, $tree);
    }
    
    /**
     * Clones and updates all of the agent rates for the tree heirachy using
     * the current_rate as the baseline for the parent.  If the parent's rate
     * is less than the current rate the parent will be set to 0, if it's higher
     * the parent will be set to the positive difference.
     * @param double $current_rate
     * @param Commission_Node $parent
     * @return Commission_Node
     */
    private function update_parent($current_rate, Commission_Node $parent) {
        // TODO: stephen should this be handled in the __clone method?
        $copy = clone $parent;
        $copy->agent = clone $parent->agent;
        
        if ($copy->agent->rate > $current_rate) {
            $copy->agent->rate = $copy->agent->rate - $current_rate;
            $current_rate = $copy->agent->rate;
        }
        else {
            $copy->agent->rate = 0;
        }
        
        if (!empty($copy->parent_node)) {
            $copy->parent_node = $this->update_parent($current_rate, $copy->parent_node);
        }
        
        if (!empty($parent->coleadership_node)) {
            $copy->coleadership_node = $this->update_parent($current_rate, $copy->coleadership_node);
        }
        
        return $copy;
    }
}
