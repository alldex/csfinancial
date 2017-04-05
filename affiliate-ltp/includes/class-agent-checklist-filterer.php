<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;

/**
 * Filters out nodes that are not the current agent, or if the current agent is
 * a partner it includes all nodes.
 * @author snielson
 */
class Agent_Checklist_Filterer implements Agent_Tree_Node_Filterer {
  
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    public function __construct(Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        
        $this->agent_dal = $agent_dal;
        $this->current_agent_id = $agent_dal->get_current_user_agent_id();
        
        $this->override_display = false;

        // if they are a partner or a trainer let them see the checklist
        if (!empty($this->current_agent_id)) {
            $agent_rank = $agent_dal->get_agent_rank($this->current_agent_id);
            if ($agent_rank == $settings_dal->get_partner_rank_id()
                || $agent_rank == $settings_dal->get_trainer_rank_id()) {
                $this->override_display = true;
            }
            
        }
    }
    
    /**
     * Checks if the checklist for the current node should be displayed or not.  If the
     * filter returns true the node is included, if the filter returns
     * false the node is excluded.
     * @param \AffiliateLTP\Agent_Tree_Node $node
     */
    public function filter(Agent_Tree_Node $node) {
        if ($this->override_display) {
            return true;
        }
        return false;
    }
}
