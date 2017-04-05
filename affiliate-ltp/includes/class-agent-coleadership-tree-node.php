<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

use AffiliateLTP\Agent_Tree_Node;

/**
 * Description of class-agent-coleadership-tree-node
 *
 * @author snielson
 */
class Agent_Coleadership_Tree_Node extends Agent_Tree_Node {
    public $coleadership_agent_id;
    
    public function __construct(Agent_Tree_Node $node) {
        parent::__construct($node);
        
        if ($node instanceof Agent_Coleadership_Tree_Node) {
            $this->coleadership_agent_id = $node->coleadership_agent_id;
        }
    }
    
    public function cloneChild(Agent_Tree_Node $node) {
        if ($node instanceof Agent_Coleadership_Tree_Node) {
            return new Agent_Coleadership_Tree_Node($node);
        }
        else {
            parent::cloneChild($node);
        }
    }
}
