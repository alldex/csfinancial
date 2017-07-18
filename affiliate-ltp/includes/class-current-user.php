<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\admin\Agent_DAL;

/**
 * Description of class-current-user
 *
 * @author snielson
 */
class Current_User {
    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     * Whether the current user is a partner or not
     * @var boolean
     */
    private $is_partner = null;
    
    public function __construct(Settings_DAL $settings_dal, Agent_DAL $agent_dal) {
        $this->settings_dal = $settings_dal;
        $this->agent_dal = $agent_dal;
    }
    
    public function is_partner() {
        
        if ($this->is_partner === null) {
            $agent_id = $this->agent_dal->get_current_user_agent_id();
            $partner_rank_id = $this->settings_dal->get_partner_rank_id();
            $agent_rank = $this->agent_dal->get_agent_rank($agent_id);
            $this->is_partner = $agent_rank == $partner_rank_id;
        }
        
        return $this->is_partner;
    }
}
