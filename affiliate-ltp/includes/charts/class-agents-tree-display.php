<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\charts;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\Agent_Tree_Node_Filterer;
use AffiliateLTP\admin\Agent_Custom_Slug;
use AffiliateLTP\Agent_Tree_Node;


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
    
    /**
     * If set filters the checklist display portion of the tree with whatever
     * matches the filterer.
     * @var Agent_Tree_Node_Filterer
     */
    private $checklist_filterer;
    
    /**
     * Holds the id of the currently logged in agent which is used to display
     * user specific items for the logged in agent.
     * @var int
     */
    private $current_user_agent_id;
    
    public function __construct(Agent_DAL $agent_dal, Agent_Tree_Node_Filterer $filterer = null
            ,  Agent_Tree_Node_Filterer $checklist_filterer) {
        $this->agent_dal = $agent_dal;
        $this->filterer = $filterer;
        $this->checklist_filterer = $checklist_filterer;
        
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
        $downline_tree_with_counts = $this->get_tree_with_downline_counts($downline_tree);
        
        // you should always at least have yourself.
        $nodes = $this->get_tree_data_for_agents( $downline_tree );
        $show_partners_checked = false;
        
        if ($show_controls) {
            // current setting is:
            $show_partners_checked = absint(filter_input(INPUT_POST, 'affiliate_ltp_show_partners')) === 1;
        }
        
        $templatePath = affiliate_wp()->templates->get_template_part( 'dashboard-tab-organization-tree', null, false);
        include_once $templatePath;
        
    }
    
    private function get_tree_with_downline_counts($downline_tree) {
        $total_child_count = 0;
        if ($downline_tree->children) {
            foreach ($downline_tree->children as $child) {
                if ($this->should_visit_node($child)) {
                    $child = $this->get_tree_with_downline_counts($child);
                    // add the child's downline and one more to count the child.
                    $total_child_count += $child->downline_count + 1;
                }
            }
        }
        $downline_tree->downline_count = $total_child_count;
        return $downline_tree;
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
    
    private function get_rank_name( $rank_id ) {
        $ranks = get_rank_by_id( $rank_id );
        if (!empty($ranks)) {
            return array_shift($ranks)["name"];
        }
        return "";
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
        $parent_slug = Agent_Custom_Slug::get_slug_for_agent_id($parent_agent_id);
        $sub_slug = Agent_Custom_Slug::get_slug_for_agent_id($sub_id);
        
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
        
        // add in the parent ranks.
        $parent_name .= " " . $this->get_rank_name($this->agent_dal->get_agent_rank( $parent_agent_id ) );
        $sub_name .= " " . $this->get_rank_name( $this->agent_dal->get_agent_rank( $sub_id ) );
        
        $affiliate_status = affwp_get_affiliate_status($sub_id);

        $node_arr = [
            'id' => $sub_id
            ,'user_id' => $user_id
            ,'name' => $sub_name
            ,'parent_name' => $parent_name
            ,'status' => $affiliate_status
            ,'life_licensed' => $this->agent_dal->is_life_licensed($sub_id)
            ,"statistics" => $this->get_agent_statistics($sub_user, $sub_id, $node)
            ,"is_current_agent" => $this->is_current_agent( $sub_id )
            ,"checklist_readonly" => false
            ,"checklist_complete" => true
        ];
        
        $include_checklist = $this->checklist_filterer->filter($node);
        if ($include_checklist || $this->is_current_agent( $sub_id )) {
            $node_arr["checklist"] = $this->get_agent_checklist( $sub_id );
            // if the checklist is supposed to be excluded but it's the current
            // agent we want them to have readonly writes to the checklist.
            if (!$include_checklist) {
                $node_arr['checklist_readonly'] = true;
            }
            // need to have a status item that the checklist is complete if 
            // all the items are completed
            foreach ($node_arr['checklist'] as $item) {
                if (!isset($item['date_completed']) || $item['date_completed'] === null) {
                    $node_arr['checklist_complete'] = false;
                    break;
                }
            }
        }
        $node_arr['avatar'] = $this->get_avatar_for_node( $node_arr );
        
        $nodes[] = $node_arr;
        
        $this->visit_children($nodes, $node, $coleadership_name);
    }
    
    private function get_avatar_for_node( array $node ) {
        if ( $node['checklist_complete'] ) {
            return "<img class='avatar avatar-96 photo' src='" . AFFILIATE_LTP_PLUGIN_URL . 'assets/images/person.png' . "' />";
        }
        else {
            return get_avatar($node['user_id']);
        }
    }
    
    private function is_current_agent( $agent_id ) {
        if (!isset($this->current_user_agent_id)) {
            $this->current_user_agent_id = $this->agent_dal->get_current_user_agent_id();
        }
        
        return $this->current_user_agent_id == $agent_id;
    }
    
    private function get_agent_checklist( $agent_id ) {
        
        return $this->agent_dal->get_agent_progress_items( $agent_id );
    }
    
    /**
 * Get an affiliate's data (Stats)
 *
 * @since  1.1
 */
private function get_agent_statistics( $agent_user, $agent_id, $node ) {

	// Affiliate info
	$affiliate = affwp_get_affiliate( $agent_id );
        $phone = affwp_get_affiliate_meta($agent_id, 'cell_phone', true);
	$join_date = esc_attr( date_i18n( 'm-d-Y', strtotime( $affiliate->date_registered ) ) );
	$status    = affwp_get_affiliate_status( $agent_id );
	$contact   = $agent_user->user_email;

	$paid_earnings   = affwp_get_affiliate_earnings( $agent_id, true );
	$unpaid_earnings = affwp_get_affiliate_unpaid_earnings( $agent_id, true );
	$total_earnings  = affwp_get_affiliate_earnings( $agent_id ) + affwp_get_affiliate_unpaid_earnings( $agent_id );
	$total_earnings_by_currency  = affwp_currency_filter( affwp_format_amount( $total_earnings ) );
        
	// Network data
	$direct_id        = affwp_mlm_get_direct_affiliate( $agent_id );
	$parent_id        = affwp_mlm_get_parent_affiliate( $agent_id );
	$referrer         = affiliate_wp()->affiliates->get_affiliate_name( $direct_id );
	$parent 		  = affiliate_wp()->affiliates->get_affiliate_name( $parent_id );
        $downline = $node->downline_count;
        
//      we don't care about direct line affiliates at this point.
//	$sub_affiliates   = count( affwp_mlm_get_sub_affiliates( $agent_id ) );
//	$downline 		  = max(count( affwp_mlm_get_downline_array( $agent_id ) ) - 1, 0);
	
	$aff_data = apply_filters( 'affwp_mlm_aff_data', 
            array(
                    'info' => array(
                            'title'    => __( 'Info', 'affiliatewp-multi-level-marketing' ),
                            'icon'     => 'fa-info',
                            'content'  => array(						
                                    'joined'  => $join_date,
                                    'phone' => $phone,
                                    'status'  => $status,
                                    'contact' => $contact,
                            )
                    )
                    ,'earnings' => array(
                            'title'    => __( 'Earnings', 'affiliatewp-multi-level-marketing' ),
                            'icon'     => 'fa-usd',
                            'content'  => array(						
                                    'paid'   => $paid_earnings,
                                    'unpaid' => $unpaid_earnings,
                                    'total'  => $total_earnings_by_currency,
                            )
                    )
                    ,'sub_affiliates' => array(
                            'title'    => __( 'Network', 'affiliatewp-multi-level-marketing' ),
                            'icon'     => 'fa-sitemap',
                            'content'  => array(						
                                    'referrer' => $referrer,
                                    'parent'   => $parent,
//                                    'direct'   => $sub_affiliates,
                                    'downline' => $downline,
                            )
                    )

            )
    );
	
	return $aff_data;

}
}
//