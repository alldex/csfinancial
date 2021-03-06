<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;
use AffiliateLTP\admin\commissions\Commission_Node;
use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\Commission_Type;
use AffiliateLTP\admin\commissions\Agent_Data;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\admin\commissions\Transformation_Result;

/**
 * Runs through the tree and changes the agent rate to be what the actual rate
 * is based upon the rate heirarchy.  For example if a child node is 25% and
 * the current node is 25%, the real rate is 0.  If the current node's rate is
 * 45% the real rate is 20%.
 *
 * @author snielson
 */
class Real_Rate_Calculate_Transformer {
    /**
     *
     * @var Referrals_New_Request
     */
    private $request;
    
    /**
     * We don't calculate commissions for the company as that is handled
     * separately in the company processors.  If for some reason the
     * company agent user accidently gets added into the heirarchy
     * we need to skip commissions for it.
     * @var int
     */
    private $company_agent_id;
    
    public function __construct(Settings_DAL $settings_dal, Referrals_New_Request $request) {
        $this->request = $request;
        
        $this->company_agent_id = $settings_dal->get_company_agent_id();
    }
    /**
     * Clones and updates all of the agent rates for the tree heirachy
     * @param Commission_Node $tree
     * @return type
     */
    public function transform(Commission_Node $tree) {
        $result = $this->update_parent(0, $tree);
        return new Transformation_Result($this, [$result]);
    }
    
    /**
     * Clones and updates all of the agent rates for the tree heirachy using
     * the current_rate as the baseline for the parent.  If the parent's rate
     * is less than the current rate the parent will be set to 0, if it's higher
     * the parent will be set to the positive difference.
     * @param double $current_rate
     * @param Commission_Node $parent
     * @return Commission_Node
     */
    private function update_parent($current_rate, Commission_Node $parent) {
        // TODO: stephen should this be handled in the __clone method?
        $copy = clone $parent;
        $copy->agent = clone $parent->agent;
        
        // set the agent to receive nothing if they have no license.
        // check licensing, active status, and other conditions preventing
        // them from having the commission.
        if ($this->should_skip_commission($copy)) {
            $copy->rate = 0;
        }
        else if (!$copy->is_generational_partner()) { // we only adjust the rates for direct team members, not integenerational
            if ($copy->agent->rate > $current_rate) {
                $copy->rate = $copy->agent->rate - $current_rate;
                $current_rate = $copy->agent->rate;
            }
            else {
                $copy->rate = 0;
            }
        }
        
        if (!empty($copy->parent_node)) {
            $copy->parent_node = $this->update_parent($current_rate, $copy->parent_node);
        }
        
        if (!empty($parent->coleadership_node)) {
            $copy->coleadership_node = $this->update_parent($current_rate, $copy->coleadership_node);
        }
        
        return $copy;
    }
    
    private function should_skip_commission(Commission_Node $copy) {
        $skip = false;
        if ($copy->agent->id == $this->company_agent_id) {
            $skip = true;
        }
        if ($this->check_life_licensing()
                && $this->has_invalid_license($copy->agent)) {
            $skip = true;
        }
        
        if (!$copy->agent->is_active) {
            $skip = true;
        }
        return $skip;
    }
    
    private function has_invalid_license(Agent_Data $agent) {
        return !$agent->life_license_status->has_active_licensed($this->request->client['state_of_sale']);
    }
    
    private function check_life_licensing() {
        return $this->request->type == Commission_Type::TYPE_LIFE;
    }
}
