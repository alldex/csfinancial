<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;
use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;

require_once 'class-commission-node.php';
require_once 'class-agent-data.php';

/**
 * For new commissions it creates the processing tree that mimics the agent
 * heirarchy.
 *
 * @author snielson
 */
class New_Commission_Tree_Parser {
    
    /**
     * Safety catch to break loops that exceed this level in case
     * there is a recursive loop.
     */
    const HEIARCHY_MAX_LEVEL_BREAK = 100;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;
    
    /**
     * The cached id value of the partner rank
     * @var type 
     */
    private $partner_rank_id;
    
    /**
     * Used to perform manipulations to the trees 
     * @var type 
     */
    private $transformers;

    public function __construct(Settings_DAL $settings_dal, Agent_DAL $agent_dal) {
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
    }
    
    public function add_transformer($transformer) {
        $this->transformers[] = $transformer;
    }

    /**
     * 
     * @param \AffiliateLTP\admin\commissions\Referrals_New_Request $request
     * @return array
     */
    public function parse(Referrals_New_Request $request) {
        $trees = [];

        foreach ($request->agents as $agent) {
            $item = $this->get_initial_processor_item_for_agent($request, $agent);
            $this->populate_tree_with_parents($item, 0);
            $trees[] = $this->process_transformations($item);
        }
        return $trees;
    }
    
    protected function process_transformations($item) {
        $updated_item = $item;
        if (!empty($this->transformers)) {
            foreach ($this->transformers as $transformer) {
                $updated_item = $transformer->transform($updated_item);
            }
        }
        return $updated_item;
    }
    
    protected function get_initial_processor_item_for_agent(Referrals_New_Request $request, $agent) {
        $splitPercent = $agent->split / 100;

        $item = $this->create_initial_processor_item($agent->id);
//                $item->amount = $request->amount * $splitPercent;
//            // TODO: stephen not sure I like this split rate piece here
//            // either it needs to be it's own attribute or other things like contract_number should go there.
        $item->meta_items['split_rate'] = $splitPercent;
        $item->date = $request->date;
        $item->is_direct_sale = true;
        $item->points = $request->points;
        $item->type = $request->type;
        $item->contract_number = $request->client['contract_number'];
        $item->client_id = $request->client['id'];
        return $item;
    }
    
    
    protected function get_parent_for_node(Commission_Node $node) {
        $parent_node = null;
        $parent_agent_id = $this->agent_dal->get_parent_agent_id($node->agent->id);
        if (!empty($parent_agent_id)) {
            $parent_node = $this->create_initial_processor_item($parent_agent_id, $node);
        }
        return $parent_node;
    }
    
    protected function get_coleadership_for_node(Commission_node $node) {
        $coleadership_id = $this->agent_dal->get_agent_coleadership_agent_id($node->agent->id);
        if (!empty($coleadership_id)) {
            $coleadership_node = $this->create_initial_processor_item($coleadership_id, $node);
            $coleadership_node->coleadership_rate = 
                    $this->agent_dal->get_agent_coleadership_agent_rate($node->agent->id);
        }
        return $coleadership_node;
    }

    protected function create_initial_processor_item($agent_id, Commission_Node $child_node = null) {
        $item = new Commission_Node();
        $agent = new Agent_Data();
        $agent->id = $agent_id;
        $agent->rank = $this->agent_dal->get_agent_rank($agent_id);
        $agent->life_license_status = $this->agent_dal->get_life_license_status($agent_id);
        
        $agent->is_partner = $this->is_partner($agent->rank);
        $item->agent = $agent;
        
        if ($agent->is_partner && !empty($child_node)) {
            $item->generational_count = $child_node->generational_count + 1;
            $agent->rate = $this->get_generational_override_rate($item->generational_count);
        }
        else {
            $agent->rate = $this->agent_dal->get_agent_commission_rate($agent_id);
        }
        $item->rate = $agent->rate;
        return $item;
    }
    
    protected function get_generational_override_rate($generation_count) {
        // TODO: stephen... how do we handle the situation where we have a generational override
        // that is at 17%, but the person above them is not a partner....
        // how do we handle the calculations for this? Or if two levels up the person is not a partner...
        // If their commission is 50% - 17% we'll have > 100% for our commission calculations....
        // we currently only handle three generational overrides.
        return $this->settings_dal->get_generational_override_rate($generation_count);
    }
    
    protected function is_partner($agent_rank) {
        $partner_rank_id = $this->get_partner_rank_id();
        //$this->debugLog("is_partner: agent rank '$rank' partner rank id '$partner_rank_id'");
        if (!empty($partner_rank_id) && $agent_rank === $partner_rank_id) {
            return true;
        }
        return false;
    }
    
    private function get_partner_rank_id() {
        if (!isset($this->partner_rank_id)) {
            $this->partner_rank_id = $this->settings_dal->get_partner_rank_id();
        }
        return $this->partner_rank_id;
    }
    
    private function populate_tree_with_parents(Commission_Node $node, $count) {

        /**
         * 100 parent heirarchy is extremely deep for recursion.
         */
        if ($count > self::HEIARCHY_MAX_LEVEL_BREAK) {
            throw new \RuntimeException("Max level heirarchy reached.  Terminating recursive loop.  Check for circular references.");
        }
        $parent_node = $this->get_parent_for_node($node);
        if (!empty($parent_node)) {
            $node->parent_node = $parent_node;
            $this->populate_tree_with_parents($node->parent_node, $count + 1);
        }
        
        $coleadership_node = $this->get_coleadership_for_node($node);
        if (!empty($coleadership_node)) {
            $node->coleadership_node = $coleadership_node;
            $this->populate_tree_with_parents($node->coleadership_node, $count + 1);
        }
    }
    
}
