<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

/**
 * Determines if an agent's registration date is within the specified start
 * and end filter.
 *
 * @author snielson
 */
class Agent_Tree_Registration_Filterer implements Agent_Tree_Node_Filterer {
    
    /**
     * Used to access / retrieve agents from the database.
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     * The minimum date timestamp that the registration can be.
     * @var number
     */
    private $start_time;
    
    /**
     * The maximum date timestamp that the registration can be.
     * @var number
     */
    private $end_time;
    
    public function __construct(admin\Agent_DAL $dal, $date_filter) {
        $this->agent_dal = $dal;
        $this->start_time = strtotime($date_filter['start']);
        $this->end_time = strtotime($date_filter['end']);
    }
    public function filter(Agent_Tree_Node $node) {
        $date = $this->agent_dal->get_agent_registration_date($node->id);
        if (!empty($date)) {
            $date_time = strtotime($date);
            return $date_time >= $this->start_time
                    && $date_time <= $this->end_time;
        }
        return false;
    }

}
