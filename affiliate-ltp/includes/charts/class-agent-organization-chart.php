<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\charts;

use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL_Affiliate_WP_Adapter;
use AffiliateLTP\Agent_Tree_Partner_Filterer;
/**
 * Responsible for rendering organization charts of the agent heirarchy.
 *
 * @author snielson
 */
class Agent_Organization_Chart implements \AffiliateLTP\I_Register_Hooks_And_Actions{
    
    /**
     * Agent database service
     * @var Agent_DAL
     */
    private $agent_dal;
    
    public function __construct(Agent_DAL $agent_dal) {
        $this->agent_dal = $agent_dal;
    }
    
    public function register_hooks_and_actions() {
        remove_action('affwp_mlm_show_sub_affiliates', 'affwp_mlm_connect_affiliates', 10);
        add_action('affwp_mlm_show_sub_affiliates', array($this, 'display_sub_agent_charts'), 10, 2);
        
                // this is actually called from the template file of dasbhoard-tab-organization.php since that's
        // the way the affiliate-wp plugin works with tabs/tab-containers.
        add_action('affwp_affiliate_dashboard_organization_show', array( $this, 'render_organization_tree' ), 10, 1);
    }
    
    /**
     * Handle the affiliate-wp-mlm plugin's agent chart display
     * @param stdClass $affiliate The affiliate that we are wanting to show the sub-agents for.
     * @param $chart_type  The type of chart to display.  We will ignore this value since we are overriding it.
     */
    public function display_sub_agent_charts($affiliate, $chart_type) {
        // we ignore chart type as everything is a tree.
        $this->render_organization_tree($affiliate->affiliate_id);
    }
    
    public function render_organization_tree($agent_id) {
        $agent_dal = $this->agent_dal;
        $settings_dal = new Settings_DAL_Affiliate_WP_Adapter();
        $filterer = null;
        $checklist_filterer = new Agent_Checklist_Filterer($agent_dal, $settings_dal);
        $show_controls = false;
        $exclude_partner = true;
        
        // if the user is a partner.
        $partner_rank_id = $settings_dal->get_partner_rank_id();
        if ($partner_rank_id === $agent_dal->get_agent_rank($agent_id)) {
            $show_controls = true;
            if (absint(filter_input(INPUT_POST, 'affiliate_ltp_show_partners')) === 1) {
                $exclude_partner = false;
            }
        }
        if ($exclude_partner) {
            $filterer = new Agent_Tree_Partner_Filterer($agent_dal, $settings_dal);
        }
        
        $agents_tree_display = new Agents_Tree_Display($agent_dal, $filterer, $checklist_filterer);
        $agents_tree_display->show_tree($agent_id, $show_controls);
    }
}
