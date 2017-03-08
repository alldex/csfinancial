<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;
use AffiliateLTP\admin\Agent_DAL;


/**
 * Displays a tree chart for a given agent.
 *
 * @author snielson
 */
class Agents_Tree_Display {
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     * If set filters the tree with whatever setting matched.
     * @var Agent_Tree_Node_Filterer 
     */
    private $filterer;
    
    public function __construct(Agent_DAL $agent_dal, Agent_Tree_Node_Filterer $filterer = null) {
        $this->agent_dal = $agent_dal;
        $this->filterer = $filterer;
        
        add_filter('show_sub_affiliates_tree_filter_affiliates', array($this, 'add_coleadership_agents_to_tree'));
    }
    
    // TODO: stephen replace affiliate functions.
    public function show_tree ( $agent_id, $show_controls = false) {
        if ( empty( $agent_id ) ) {
            $agent_id = affwp_get_affiliate_id();
        }
        
        if ( empty( $agent_id ) ) {
            return;
        }
        
        $downline_tree = $this->agent_dal->get_agent_downline_with_coleaderships( $agent_id );
        
        $level_count = 0;
        
        $is_parent = affwp_mlm_is_parent_affiliate( $agent_id );
        
        $allow_affiliate_registration = false;
        if (!$is_parent) {
            $allow_affiliate_registration = affiliate_wp()->settings->get( 'allow_affiliate_registration' );
        }
        
        $nodes = $this->get_tree_data_for_agents( $downline_tree );
        $has_sub_agents = !empty($nodes);
        $show_partners_checked = false;
        
        if ($show_controls) {
            // current setting is:
            $show_partners_checked = absint(filter_input(INPUT_POST, 'affiliate_ltp_show_partners')) === 1;
        }
        
        $templatePath = affiliate_wp()->templates->get_template_part( 'dashboard-tab-organization-tree', null, false);
        include_once $templatePath;
        
    }
    
    private function should_visit_node(Agent_Tree_Node $node) {
        if ($this->filterer !== null) {
            return $this->filterer->filter($node);
        }
        return true;
    }
    
    private function get_tree_data_for_agents( $downline_tree ) {
        
        $nodes = [];
        
        $coleadership_name = null; 
        // we always show the root node no matter what, which is why we use this
        // function instead of visit node.
        $this->get_tree_data_for_agent_node($nodes, $downline_tree, $coleadership_name );
        
        return $nodes;
    }
    
    private function visit_node(&$nodes, $node, $coleadership_name) {
        if (!$this->should_visit_node($node)) {
            return;
        }
        $this->get_tree_data_for_agent_node($nodes, $node, $coleadership_name);
    }
    
    private function visit_children(&$nodes, $node, $coleadership_name) {
        if (!empty($node->children)) {
            foreach ($node->children as $child_node) {
                $this->visit_node($nodes, $child_node, $coleadership_name);
            }
        }
    }
    
    private function get_tree_data_for_agent_node(&$nodes, $node, $coleadership_name) {
        $sub_id = $node->id;
        
        $user_id = affwp_get_affiliate_user_id($sub_id);
        $sub_user = get_user_by('id', $user_id);

        if ($node->type === 'coleadership') {
            $parent_agent_id = $node->coleadership_agent_id;
        }
        else {
            
            $parent_agent_id = affwp_mlm_get_parent_affiliate($sub_id);
        }
        $parent_user_id = affwp_get_affiliate_user_id($parent_agent_id);
        $parent_user = get_user_by('id', $parent_user_id);

        // Both names must match
        $parent_slug = admin\Agent_Custom_Slug::get_slug_for_agent_id($parent_agent_id);
        $sub_slug = admin\Agent_Custom_Slug::get_slug_for_agent_id($sub_id);
        
        $sub_name = $sub_user->display_name;
        if (!empty($sub_slug)) {
            $sub_name .= " (" . $sub_slug . ")";
        }
        
        $parent_name = $parent_user->display_name;
        if (!empty($parent_slug)) {
            $parent_name .= " (" . $parent_slug . ")";
        }
        
        
        if ($node->type === 'coleadership') {
            // update the coleadership name if we are in a new coleadership
            // branch
            $coleadership_name = $parent_name;
            
            // we leave the parent name alone
            
            // a node higher up may be on a CL which we want to display
            // also since the names have to be unique in our charts 
            // we have to add this name on.
            $sub_name .= ", CL($parent_name)";
        }
        else if (!empty($coleadership_name)) {
            $sub_name .= ", CL($coleadership_name)";
            $parent_name .= ", CL($coleadership_name)";
        }
        
        $affiliate_status = affwp_get_affiliate_status($sub_id);

        $nodes[] = [
            'id' => $sub_id
            ,'user_id' => $user_id
            ,'name' => $sub_name
            ,'parent_name' => $parent_name
            ,'status' => $affiliate_status
        ];
        
        $this->visit_children($nodes, $node, $coleadership_name);
    }
}
