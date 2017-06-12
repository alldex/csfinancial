<?php

namespace AffiliateLTP\leaderboards;

use AffiliateLTP\Plugin;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\Agent_Tree_Node;
use AffiliateLTP\Agent_Tree_Aggregate_Filterer;
use AffiliateLTP\Agent_Tree_Registration_Filterer;
use AffiliateLTP\Agent_Tree_Partner_Filterer;
use AffiliateLTP\Agent_Tree_Transformer;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
/**
 * Handles the shortcodes and global functionality for the leaderboards
 *
 * @author snielson
 */
class Leaderboards {
    
    /**
     * Settings dal
     * @var Settings_DAL
     */
    private $settings_dal;
    
    /**
     * Agent dal
     * @var Agent_DAL 
     */
    private $agent_dal;
    
    public function __construct(Settings_DAL $settings_dal, Agent_DAL $agent_dal) {
        $this->settings_dal = $settings_dal;
        $this->agent_dal = $agent_dal;
        add_shortcode("leaderboard_agent_points", array($this, 'leaderboard_agent_points'));
        add_shortcode("leaderboard_agent_recruits", array($this, 'leaderboard_agent_recruits'));
        add_shortcode("leaderboard_partner_points", array($this, 'leaderboard_partner_points'));
        add_shortcode("leaderboard_partner_recruits", array($this, 'leaderboard_partner_recruits'));
    }
    
    public function leaderboard_agent_points($atts) {
        $default_limit = 10;
        $atts = shortcode_atts([
            'limit' => $default_limit
        ], $atts);
        $limit = absint($atts['limit']) > 0 ? absint($atts['limit']) : $default_limit;
        $filter = $this->get_leaderboard_filter();
        $date_filter = $this->get_date_range_from_filter($filter);
        
        $scores = $this->get_leaderboard_data($limit, $date_filter, 'get_agent_leaderboard_points_data', 'points');
        
        return $this->get_leaderboard_html_with_scores($scores, 'Points');
    }
    
    public function leaderboard_partner_points() {
        $default_limit = 10;
        $atts = shortcode_atts([
            'limit' => $default_limit
        ], $atts);
        $limit = absint($atts['limit']) > 0 ? absint($atts['limit']) : $default_limit;
        $filter = $this->get_leaderboard_filter();
        $date_filter = $this->get_date_range_from_filter($filter);
        
        $scores = $this->get_leaderboard_data($limit, $date_filter, 'get_partner_agent_leaderboard_points_data', 'points');
        
        return $this->get_leaderboard_html_with_scores($scores, 'Points');
    }
    
    public function leaderboard_agent_recruits( $atts ) {
        $default_limit = 10;
        $atts = shortcode_atts([
            'limit' => $default_limit
        ], $atts);
        $limit = absint($atts['limit']) > 0 ? absint($atts['limit']) : $default_limit;
        $filter = $this->get_leaderboard_filter();
        $date_filter = $this->get_date_range_from_filter($filter);
        
        $scores = $this->get_leaderboard_data($limit, $date_filter, 'get_agent_leaderboard_direct_recruits', 'recruits');
        
        return $this->get_leaderboard_html_with_scores($scores, 'Recruits');
    }
    
    public function leaderboard_partner_recruits( $atts ) {
        $default_limit = 10;
        $atts = shortcode_atts([
            'limit' => $default_limit
        ], $atts);
        $limit = absint($atts['limit']) > 0 ? absint($atts['limit']) : $default_limit;
        $filter = $this->get_leaderboard_filter();
        $date_filter = $this->get_date_range_from_filter($filter);
        
        $scores = $this->get_partner_recruit_data($limit, $date_filter);
        
        return $this->get_leaderboard_html_with_scores($scores, 'Recruits');
    }
    
    private function get_partner_recruit_tree_transformer($date_filter) {
        $agg_filter = new Agent_Tree_Aggregate_Filterer();
        $agg_filter->addFilter(new Agent_Tree_Partner_Filterer($this->agent_dal, $this->settings_dal));
        $agg_filter->addFilter(new Agent_Tree_Registration_Filterer($this->agent_dal, $date_filter));
        
        $transformer = new Agent_Tree_Transformer($agg_filter);
        $transformer->addTransformation(function(Agent_Tree_Node $node) use($agg_filter,$transformer) {
            $copy = clone $node;
            $copy->children = [];
            foreach ($node->children as $child) {
                if ($agg_filter->filter($child)) {
                    $copy->children[] = $transformer->transform($child);
                }
            }
            return $copy;
        });
        $transformer->addTransformation(function(Agent_Tree_Node $node) use($transformer) {
            $copy = clone $node;
            $copy->children = [];
            $total_child_count = 0;
            foreach ($node->children as $child) {
                $copyChild = $transformer->transform($child);
                $copy->children[] = $copyChild;
                $total_child_count += $copyChild->count + 1;
            }
            $copy->count = $total_child_count;
            return $copy;
        });
        
        return $transformer;
    }
    
    function get_partner_recruit_data($limit, $date_filter) {
        $company_agent_id = absint($this->settings_dal->get_company_agent_id());
        
        $partner_id = $this->settings_dal->get_partner_rank_id();
        
        // grab all the partners
        
        // grab their downline and filter each node
        // include if node's agent registration is in the date range
        // exclude if node is a partner
        
        $partner_agent_ids = $this->agent_dal->get_agent_ids_by_rank( $partner_id );
//        var_dump($partner_agent_ids);
        $partner_agent_ids = array_map(absint, $partner_agent_ids);
        $partners = array_filter($partner_agent_ids, function($p) use($company_agent_id) {
            return $p !== $company_agent_id;
        });
        
        $transformer = $this->get_partner_recruit_tree_transformer($date_filter);
        
        $partner_recruits = [];
        
        foreach ($partners as $partner) {
            $partner_downline = $this->agent_dal->get_agent_downline_with_coleaderships($partner);
//            var_dump($partner_downline);
            $partner_recruits[] = $transformer->transform($partner_downline);
        }
        
        usort($partner_recruits, function($a, $b) {
            $diff = $b->count - $a->count;
            return $diff;
        });
        
        if (count($partner_recruits) > $limit) {
            $partner_recruits = array_slice($partner_recruits, 0, $limit);
        }
        
        $scores = [];
        for ($i = 0; $i < count($partner_recruits); $i++) {
            $name =  $this->agent_dal->get_agent_displayname($partner_recruits[$i]->id);
            if (empty($name)) {
                $name = $this->agent_dal->get_agent_email($partner_recruits[$i]->id);
            }
            $scores[] = ['agent' => $name, "value" => $partner_recruits[$i]->count
                    , "image" => AFFILIATE_LTP_PLUGIN_URL . "assets/images/person.png"];
        }
        return $scores;
    }
    
    private function parse_organization_for_partner_base_shops(Agent_Tree_Node $companyNode) {
        $cloneOrg = clone $companyNode;
        $partner_nodes = [];
        $filterer = new \AffiliateLTP\Agent_Tree_Partner_Filterer($this->agent_dal, $this->settings_dal);
        foreach ($companyNode->children as $partner) {
            if (!$filterer->filter($node)) {
                $partner_nodes[] = $this->parse_base_shop_for_partner($partner, $filterer);
            }
        }
        $cloneOrg->children = $partner_nodes;
        return $cloneOrg;
    }
    
    private function parse_base_shop_for_partner(Agent_Tree_Node $node, \AffiliateLTP\Agent_Tree_Node_Filterer $filterer) {
        $clone = clone $node;
        $clone->children = [];
        foreach ($node->children as $child) {
            if ($filterer->filter($node)) {
                $clone->children[] = $this->parse_base_shop_for_partner($child, $filterer);
            }
        }
        return $clone;
    }
    
    function get_date_range_from_filter($filter) {
        $start_date = date("Y-m-d H:i:s", strtotime("-1 YEAR"));
        $end_date = date("Y-m-d H:i:s");
        
        if ($filter !== "year") {
            // This stackoverflow post helped figure out the first/last day month conundrum
            // https://stackoverflow.com/questions/2680501/how-can-i-find-the-first-and-last-date-in-a-month-using-php
            $one_based_month = $filter + 1;
            $month = str_pad($one_based_month, 2, "0", STR_PAD_LEFT);
            $start_date = date("Y-$month-01 00:00:00");
            // now get the last day of the month using the first day as the starting month
            $end_date = date("Y-m-t 23:59:59", strtotime($start_date));
        }
        $range = ["start" => $start_date, "end" => $end_date];
        return $range;
    }
    
    function get_leaderboard_filter() {
        $currentFilter = filter_input(INPUT_POST, "leaderboard_filter");
        if (!isset($currentFilter)) {
            // date is 1-12, our filter is 0-11
            $currentFilter = absint(date("n")) - 1;
        }
        else if ($currentFilter !== "year") {
            $currentFilter = absint($currentFilter);
        }
        return $currentFilter;
    }
    
    function get_leaderboard_html_with_scores($scores, $type) {
        $currentFilter = $this->get_leaderboard_filter();
        $months = ["January", "February", "March", "April", "May", "June", 
            "July", "August", "September", "October", "November", "December"];
        $loader = Plugin::instance()->get_template_loader();
        $file = $loader->get_template_part('leaderboard', null, false);
        ob_start();
        include $file;
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    
    private function get_leaderboard_data($limit, $date_filter, $dal_function, $column_name) {
        $company_agent_id = absint($this->settings_dal->get_company_agent_id());
        $results = $this->agent_dal->$dal_function($limit, $date_filter, $company_agent_id);
                
        $scores = [];
        for ($i = 0; $i < count($results); $i++) {
            $name = $results[$i]['display_name'];
            $scores[] = ['agent' => $name, "value" => $results[$i][$column_name]
                    , "image" => AFFILIATE_LTP_PLUGIN_URL . "assets/images/person.png"];
        }
        return $scores;
    }
}
