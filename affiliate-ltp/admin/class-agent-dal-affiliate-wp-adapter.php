<?php
namespace AffiliateLTP\admin;

require_once dirname( dirname(__FILE__) ) . '/class-agent-tree-node.php';
require_once dirname( dirname(__FILE__) ) . '/class-agent-coleadership-tree-node.php';

/**
 * Description of class-agent-dal-affiliate-wp-adapter
 *
 * @author snielson
 */
class Agent_DAL_Affiliate_WP_Adapter implements Agent_DAL {
    
    const AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID = 'coleadership_agent_id';
    const AFFILIATE_META_KEY_COLEADERSHIP_AGENT_RATE = 'coleadership_agent_rate';
    
    public function filter_agents_by_licensed_life_agents( $upline ) {
        $licensedAgents = array();
        
        foreach ( $upline as $agentId ) {
            if ($this->is_life_licensed($agentId)) {
                $licensedAgents[] = $agentId;
            }
        }
        return $licensedAgents;
    }
        
    public function filter_agents_by_status( $upline, $status = 'active' ) {
        return affwp_mlm_filter_by_status( $upline, $status );
    }
    
    public function get_agent_status( $agent_id ) {
        return affwp_get_affiliate_status( $agent_id );
    }

    public function get_agent_commission_rate( $agent_id ) {
        return affwp_get_affiliate_rate( $agent_id );
    }
    
    public function get_agent_upline( $agent_id ) {
        return affwp_mlm_get_upline( $agent_id );
    }
    
    public function is_life_licensed($agent_id) {
        return Affiliates::isAffiliateCurrentlyLifeLicensed($agent_id);
    }
    
    public function is_active($agent_id) {
        return $this->get_agent_status($agent_id) === 'active';
    }
    
    public function get_parent_agent_id($agent_id) {
        $parent_agent_id = null;
        
        if ( affwp_mlm_is_sub_affiliate( $agent_id ) ) {
            $parent_agent_id = affwp_mlm_get_parent_affiliate( $agent_id );
        }
        
        return $parent_agent_id;
    }

    public function get_agent_rank($agent_id) {
        $rank_id = affwp_ranks_get_affiliate_rank( $agent_id );
        if (empty($rank_id)) {
            return null;
        }
        return $rank_id;
    }

    public function get_agent_coleadership_agent_id( $agent_id ) {
        $single = true;
        $id = affiliate_wp()->affiliate_meta->get_meta($agent_id, 
                self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID, $single);
        if (empty($id)) {
            return null;
        }
        return absint($id);
    }

    public function get_agent_coleadership_agent_rate($agent_id) {
        $single = true;
        $rate = absint(affiliate_wp()->affiliate_meta->get_meta($agent_id, 
                self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_RATE, $single));
        // rates are in whole numbers
        if ($rate > 0) {
            $rate /= 100;
        }
        return $rate;
    }
    
    public function get_agent_id_by_email( $agent_email ) {
            $user = get_user_by('email', $agent_email );
            if (!empty($user)) {
                return absint($user->ID);
            }
            
            return null;
    }
    
    public function get_coleadership_sponsored_agent_ids($coleadership_agent_id) {
        if (!is_int($coleadership_agent_id)) {
            throw new Exception("coleadershp_agent_id was not a valid int");
        }
        $where = 'WHERE meta_key = \'' . self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID 
                . '\' AND meta_value = ' 
                . absint($coleadership_agent_id);
        
        $clauses = [ 
            'fields' => 'affiliate_id'
            ,'join' => ''
            ,'where' => $where
            ,'orderby' => 'affiliate_id'
            ,'order' => ''
            ,'count' => false ];
        
        $args = ['fields' => 'ids', 'offset' => 0, 'number' => 1000];
        
        $results = affiliate_wp()->affiliate_meta->get_results($clauses, $args);

        return $results;
    }
    
    private function get_coleadership_agent_relationships( $agent_ids ) {
        if (empty($agent_ids)) {
            return null;
        }
        $agent_ids = array_map(absint, $agent_ids);
        
        
        $where = 'WHERE meta_key = \'' . self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID 
                . '\' AND affiliate_id IN (' 
                . join(",", $agent_ids) . ")";
        
        $clauses = [ 
            'fields' => '*'
            ,'join' => ''
            ,'where' => $where
            ,'orderby' => 'affiliate_id'
            ,'order' => ''
            ,'count' => false ];
        
        $args = ['fields' => 'blah'
                , 'offset' => 0, 'number' => 1000];
        
        $results = [];
        $db_results = affiliate_wp()->affiliate_meta->get_results($clauses, $args);
        if (!empty($db_results)) {
            foreach ($db_results as $record) {
                if (!empty($record->meta_value)) {
                    $results[] = [self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID => $record->meta_value
                            ,'agent_id' => $record->affiliate_id];
                }
            }
            
        }
        return $results;
    }
    
    public function get_agent_downline_with_coleaderships($agent_id, $max_depth = 15) {
        if ( empty( $agent_id ) ) {
            return null;
        }
        $tree = $this->get_new_agent_tree_node('normal', $agent_id);
	$matrix_depth = affiliate_wp()->settings->get( 'affwp_mlm_matrix_depth' );
        
        // need to go through 
        $nodesById = [];
        $all_ids = [];
	
	// Get 15 levels if no max or matrix depth is set
	if ( empty( $max_depth ) ) {
            $max_depth = !empty( $matrix_depth ) ? $matrix_depth : 15;
        }
	
	if ( affwp_mlm_is_parent_affiliate( $agent_id ) ) {
		
		$downline[0] = array( $agent_id );
                $all_ids[] = $agent_id;
               
                $nodesById[$agent_id] = $tree;
		
		// Loop through levels and add sub affiliates
		for ( $level = 1; $level <= $max_depth; $level++ ) {
			
			$parent_level = $level - 1;			
			$parent_ids = $downline[$parent_level];
			$agent_relationships = affwp_mlm_get_sub_affiliates( $parent_ids );
                        $sub_ids = [];
                        foreach ($agent_relationships as $agent_relationship) {
                            $sub_id = $agent_relationship->affiliate_id;
                            $sub_ids[] = $sub_id;
                            $all_ids[] = $sub_id;
                            $sub_obj = $this->get_new_agent_tree_node('normal', $sub_id);
                            $nodesById[$agent_relationship->affiliate_parent_id]->children[] = $sub_obj;
                            $nodesById[$sub_id] = $sub_obj;
                        }
//                        
			if ( empty( $sub_ids ) ) {
				break;
			} else{
				$downline[$level] = $sub_ids;
			}	
		}
	}
        
        // now go through and add in all the co-leadership values.
        if (!empty ($all_ids) ) {
            $coleadership_relationships = $this->get_coleadership_agent_relationships( $all_ids );
            // if the co-leadership is anywhere in the downline we want to show that....
            foreach ($coleadership_relationships as $relationship) {
                $agent_id = $relationship['agent_id'];
                $coleadership_id = $relationship[self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID];
                if (isset($nodesById[$coleadership_id])) {
                    $child_agent = new \AffiliateLTP\Agent_Coleadership_Tree_Node($nodesById[$agent_id]);
                    $child_agent->type = 'coleadership';
                    $child_agent->coleadership_agent_id = $coleadership_id;
                    $nodesById[$coleadership_id]->children[] = $child_agent;
                }
            }
        }

	return $tree;
    }
    private function get_new_agent_tree_node($type, $id) {
        $obj = new \AffiliateLTP\Agent_Tree_Node();
        $obj->type = $type;
        $obj->id = $id;
        $obj->children = [];
        return $obj;
    }
}