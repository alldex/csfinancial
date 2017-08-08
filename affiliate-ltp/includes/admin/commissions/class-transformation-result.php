<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

/**
 * The result of a transformation process.
 *
 * @author snielson
 */
class Transformation_Result {
    private $transformer;
    
    /**
     * Array of Commission_Node
     * @var array 
     */
    private $result_nodes;
    
    public function __construct($transformer, array $commission_nodes) {
        $this->transformer = $transformer;
        $this->result_nodes = $commission_nodes;
    }
    
    public function get_result_nodes() {
        return $this->result_nodes;
    }
    
    public function has_result_nodes() {
        return !empty($this->get_result_nodes());
    }
    
    public function get_transformer() {
        return $this->transformer;
    }
}
