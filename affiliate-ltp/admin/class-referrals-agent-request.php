<?php

/**
 * Description of class-referrals-agent-request
 *
 * @author snielson
 */
class AffiliateLTPReferralsAgentRequest {
    
    /**
     * the affiliate_id of this agent.
     * @var number
     */
    public $id;
    
    /**
     * The percentage of the total amount that should go to this agent.
     * @var number
     */
    public $split;
}
