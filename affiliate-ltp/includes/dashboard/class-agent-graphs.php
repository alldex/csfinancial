<?php
namespace AffiliateLTP\dashboard;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB;
use AffiliateLTP\Points_Graph;
use AffiliateLTP\Points_Retriever;

/**
 * Handles the dashboard graph page extensions to the already existing affiliate-wp
 * page.
 *
 * @author snielson
 */
class Agent_Graphs implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     * The id representing a partner rank
     * @var int
     */
    private $partner_rank_id;
    
    /**
     *
     * @var Affiliate_WP_Referral_Meta_DB 
     */
    private $meta_db;
    
    /**
     *
     * @var Points_Retriever
     */
    private $points_retriever;
    
    public function __construct(Agent_DAL $agent_dal
            , Affiliate_WP_Referral_Meta_DB $meta_db
            , Points_Retriever $points_retriever
            , $partner_rank_id) {
        $this->agent_dal = $agent_dal;
        $this->partner_rank_id = $partner_rank_id;
        $this->meta_db = $meta_db;
        $this->points_retriever = $points_retriever;
    }

    public function register_hooks_and_actions() {
        add_action( 'affwp_affiliate_dashboard_after_graphs', array( $this, 'add_points_to_graph_tab' ), 10, 1);
    }
    
    public function add_points_to_graph_tab( $affiliate_id ) {
        
        // TODO: stephen see if there's a way to get around this global function
        $points_retriever = $this->points_retriever;
        
        $date_range = affwp_get_report_dates();
        $start = $date_range['year'] . '-' . $date_range['m_start'] . '-' . $date_range['day'] . ' 00:00:00';
        $end   = $date_range['year_end'] . '-' . $date_range['m_end'] . '-' . $date_range['day_end'] . ' 23:59:59';
        
        $is_partner = $this->get_partner_status_for_current_agent();
        if ($is_partner) {
            // this value is inserted via javascript since the current plugin
            // does not give us a way to extend the search filters.
            $include_super_shop = filter_input(INPUT_GET, 
                'affwp_ltp_include_super_base_shop') == 'Y';
        }
        else {
            $include_super_shop = false;
        }
        $points_date_range = array(
            "start_date" => $start
            ,"end_date" => $end
            ,"range" => $date_range['range']
        );
        
        $agent_dal = $this->agent_dal;
        $agent_downline = $agent_dal->get_agent_downline_with_coleaderships($affiliate_id);
        
        $points_data = $points_retriever->get_points( $affiliate_id, $points_date_range, $include_super_shop );
        
        $graph = new Points_Graph($points_data, $this->meta_db, $points_date_range);
//        $graph = new \AffiliateLTP\Points_Graph;
	$graph->set( 'x_mode', 'time' );
	$graph->set( 'affiliate_id', $affiliate_id );
        // hide the date filter since the graph above this one controls all the
        // date filters.
        $graph->set( 'show_controls', false );
        
//        $data = $graph->get_data();
//                        echo "<pre>";
//                var_dump($data);
//                echo "</pre>";
        
        $template_path = affiliate_wp()->templates->get_template_part('dashboard-tab', 'graphs-point', false);
        
        include_once $template_path;
    }
    
    private function get_partner_status_for_current_agent() {
        
        $agent_dal = $this->agent_dal;
        $agent_id = $agent_dal->get_current_user_agent_id();
        $agent_rank = $agent_dal->get_agent_rank($agent_id);
        
        return $agent_rank == $this->partner_rank_id;
    }

}
