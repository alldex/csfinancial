<?php
namespace AffiliateLTP\admin;

/**
 * Description of class-referrals-agent-request
 *
 * @author snielson
 */
class Referrals_Agent_Request {
    
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
    
    /**
     * The email of the agent if the request looked up the agent by email.
     * @var string
     */
    public $email;
    
    /**
     * The name of the agent if the request looked for the agent by name.
     * @var type 
     */
    public $name;
}
