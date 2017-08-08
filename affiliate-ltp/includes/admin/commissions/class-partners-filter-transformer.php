<?php

namespace AffiliateLTP\admin\commissions;

use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\admin\commissions\Commission_Node;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

/**
 * Partners Filterer Transformer
 *
 * @author snielson
 */
class Partners_Filter_Transformer {
    
    /**
     *
     * @var Referrals_New_Request
     */
    private $request;
    
    public function __construct(Referrals_New_Request $request) {
        $this->request = $request;
    }
    
    /**
     * Clones and updates all of the agent rates for the tree heirachy
     * @param Commission_Node $tree
     * @return type
     */
    public function transform(Commission_Node $tree) {
        
        $partners = [];
        // since we are pruning off the parent nodes which can have the split
        // rate we have to keep whatever the split rate was with the original node
        // this is 100 if there is no split, or < 100 if there is one.
        $this->get_partner_nodes($tree, $partners, $tree->split_rate);
        return new Transformation_Result($this, $partners);
    }
    
    public function get_partner_nodes(Commission_Node $tree, &$partner_nodes, $split_rate) {
        // once we've reached a partner we are done.. no more recursion.
        if ($tree->agent->is_partner) {
            $tree->split_rate = $split_rate; // we have to keep whatever the split rate was...
            $partner_nodes[] = $tree;
            return;
        }
        
        if ($tree->coleadership_node != null) {
            $this->get_partner_nodes($tree->coleadership_node, $partner_nodes, $split_rate);
        }
        if ($tree->parent_node != null) {
            $this->get_partner_nodes($tree->parent_node, $partner_nodes, $split_rate);
        }
    }
}
