<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

/**
 * Description of class-agent-tree-transformer
 *
 * @author snielson
 */
class Agent_Tree_Transformer {
    private $transformers = [];
    
    public function addTransformation($transformation_function) {
        $this->transformers[] = $transformation_function;
    }
    public function transform(Agent_Tree_Node $node) {
        $transformed_node = $node;
        foreach ($this->transformers as $func) {
            $transformed_node = $func($transformed_node);
        }
        return $transformed_node;
    }
}
