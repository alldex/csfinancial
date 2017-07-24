<?php
namespace AffiliateLTP;

use RecursiveIteratorIterator;

class Agent_Tree_Node_Iterator extends \ArrayIterator implements \RecursiveIterator {
    
    public function getChildren() {
    $node = $this->current();
    return new Agent_Tree_Node_Iterator($node->children);
  }

    public function hasChildren() {
        $node = $this->current();
        return !empty($node->children);
    }

}
/**
 *
 * @author snielson
 */
class Agent_Tree_Node implements \IteratorAggregate{
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
    
    public function getIterator() {
        return new RecursiveIteratorIterator(new Agent_Tree_Node_Iterator([$this]),RecursiveIteratorIterator::SELF_FIRST);
    }

}
