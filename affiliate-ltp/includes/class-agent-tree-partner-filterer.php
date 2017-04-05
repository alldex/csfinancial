<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\Agent_Tree_Node_Filterer;

/**
 * Filters out any agents who have the rank of partner.
 *
 * @author snielson
 */
class Agent_Tree_Partner_Filterer implements Agent_Tree_Node_Filterer {
    
    /**
     * The partner rank.
     * @var int
     */
    private $partner_rank;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    public function __construct(Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->partner_rank = $settings_dal->get_partner_rank_id();
        $this->agent_dal = $agent_dal;
    }
    
    public function filter(Agent_Tree_Node $node) {
        if (!empty($node)) {
            $agent_rank = $this->agent_dal->get_agent_rank($node->id);
            if ($agent_rank === $this->partner_rank) {
                return false;
            }
        }
        return true;
    }
}
