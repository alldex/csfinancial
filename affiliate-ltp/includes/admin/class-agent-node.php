<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Represents a simple agent node to be used in a tree.
 *
 * @author snielson
 */
class Agent_Node {
    /**
     * The id of the agent
     * @var int
     */
    public $id;
    
    /**
     *
     * @var Agent_Node
     */
    public $parent;
    
    /**
     *
     * @var Agent_Node
     */
    public $coleadership;
}
