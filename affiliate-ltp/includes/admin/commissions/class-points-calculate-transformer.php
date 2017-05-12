<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\Commission_Type;


/**
 * Goes through the commission heirarchy and calculates the points.
 *
 * @author snielson
 */
class Points_Calculate_Transformer {
    
    /**
     * The type of commission for the request.
     * @var int
     */
    private $request_type;
    
    /**
     * The initial request points
     * @var type 
     */
    private $request_points;
    
    public function __construct(Referrals_New_Request $request) {
        $this->request_points = $request->points;
        $this->request_type = $request->type; 
    }
    
    public function transform(Commission_Node $tree) {
        // if the initial tree has a split rate we want to update it.
        if ($tree->split_rate < 100) {
            $points = round($tree->split_rate * $this->request_points, PHP_ROUND_HALF_DOWN);
        }
        else {
            $points = $this->request_points;
        }
        return $this->update_node_points($tree, $points);
    }
    
    private function update_node_points(Commission_Node $tree, $points) {
        $copy = clone $tree;
        if ($this->request_type != Commission_Type::TYPE_LIFE
                && $copy->is_direct_sale) {
            $points = round($copy->rate * $points);
        }
        $copy->points = $points;
        
        if (!empty($copy->coleadership_node)) {
            $this->update_coleadership_points($copy, $points);
        }
        else if (!empty($copy->parent_node)) {
            $copy->parent_node = $this->update_node_points($copy->parent_node, $points);
        }
        return $copy;
    }
    
    private function update_coleadership_points(Commission_Node $tree, $points) {
        $coleadership_rate = $tree->coleadership_rate;
        
        $active_points = round($points * $coleadership_rate);
        $passive_points = $points - $active_points;
        $tree->coleadership_node = $this->update_node_points($tree->coleadership_node, $active_points);
        $tree->parent_node = $this->update_node_points($tree->parent_node, $passive_points);
    }
    
}
