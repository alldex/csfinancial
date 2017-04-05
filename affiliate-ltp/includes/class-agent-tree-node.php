<?php
namespace AffiliateLTP;

/**
 *
 * @author snielson
 */
class Agent_Tree_Node {
    public $id;
    public $children;
    public $type;
    
    public function __construct(Agent_Tree_Node $node = null) {
        if (!empty($node)) {
            $this->id = $node->id;
            $this->type = $node->type;
        }
        
        $this->children = [];
        if (!empty($node->children)) {
            foreach ($node->children as $child) {
                $this->Children[] = $this->cloneChild($child);
            }
        }
    }
    
    public function cloneChild(Agent_Tree_Node $node) {
        return new Agent_Tree_Node($node);
    }
}
