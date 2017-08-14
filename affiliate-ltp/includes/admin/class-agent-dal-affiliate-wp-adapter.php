<?php

namespace AffiliateLTP\admin;

use AffiliateLTP\Plugin;
use AffiliateLTP\admin\Affiliates;
use AffiliateLTP\Agent_Tree_Node;
use AffiliateLTP\Agent_Coleadership_Tree_Node;
use AffiliateLTP\admin\Life_License_Status;
use AffiliateLTP\Progress_Item_DB;
use Psr\Log\LoggerInterface;
use AffiliateLTP\admin\Agent_Points_Summary_Request;

/**
 * Description of class-agent-dal-affiliate-wp-adapter
 *
 * @author snielson
 */
class Agent_DAL_Affiliate_WP_Adapter implements Agent_DAL {
    /*
     * Progress_Items_DB
     */

    private $progress_items_db;

    /**
     * The id of the partner rank
     * @var int
     */
    private $partner_rank_id;

    /**
     *
     * @var int
     */
    private $company_agent_id;

    /**
     * Used for logging errors and other messages
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, Progress_Item_DB $progress_items_db
    , Settings_DAL $settings_dal) {
        $this->logger = $logger;
        $this->progress_items_db = $progress_items_db;
        // TODO: stephen figure out how we can inject these values directly instead of the settings dal
        $this->partner_rank_id = $settings_dal->get_partner_rank_id();
        $this->company_agent_id = $settings_dal->get_company_agent_id();
    }

    const AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID = 'coleadership_agent_id';
    const AFFILIATE_META_KEY_COLEADERSHIP_AGENT_RATE = 'coleadership_agent_rate';

    public function filter_agents_by_licensed_life_agents($upline) {
        $licensedAgents = array();

        foreach ($upline as $agentId) {
            if ($this->is_life_licensed($agentId)) {
                $licensedAgents[] = $agentId;
            }
        }
        return $licensedAgents;
    }

    public function filter_agents_by_status($upline, $status = 'active') {
        return affwp_mlm_filter_by_status($upline, $status);
    }

    public function get_agent_status($agent_id) {
        return affwp_get_affiliate_status($agent_id);
    }

    public function get_agent_commission_rate($agent_id) {
        return affwp_get_affiliate_rate($agent_id);
    }

    /**
     * Returns the tree of the agent upline (the bottom node includes the current
     * agent).
     * @param int $agent_id The id of the agent you want to retrieve it's upline from
     * @return \AffiliateLTP\admin\Agent_Node
     */
    public function get_agent_upline($agent_id) {

        $matrix_depth = affiliate_wp()->settings->get('affwp_mlm_matrix_depth');
        $upline = [];
        $max = !empty($matrix_depth) ? $matrix_depth : apply_filters('affwp_mlm_upline_level_max', 15, $agent_id, $upline);
        $max--; // Offset the max value to return the correct amount of levels

        return $this->get_agent_upline_tree($agent_id, 0, $max);
    }

    /**
     * Returns the life insurance license status for the agent.
     * @param string $agent_id
     * @return Life_License_Status
     */
    public function get_life_license_status($agent_id) {
        $license_date = affwp_get_affiliate_meta($agent_id, 'life_expiration_date', true);
        $license_number = affwp_get_affiliate_meta($agent_id, 'life_license_number', true);

        $life_insurance_dal = new Agent_Life_Insurance_State_DAL();
        $licensed_states = $life_insurance_dal->get_state_licensing_for_agent($agent_id);

        $life_license_state = new Life_License_Status($license_number, $license_date, $licensed_states);
        $life_license_state->set_required_licensing_states($life_insurance_dal->get_required_licensing_states());

        return $life_license_state;
    }

    public function is_life_licensed($agent_id) {
        return Affiliates::isAffiliateCurrentlyLifeLicensed($agent_id);
    }

    public function is_active($agent_id) {
        return $this->get_agent_status($agent_id) === 'active';
    }

    public function get_parent_agent_id($agent_id) {
        $parent_agent_id = null;

        if (affwp_mlm_is_sub_affiliate($agent_id)) {
            $parent_agent_id = affwp_mlm_get_parent_affiliate($agent_id);
        }

        return $parent_agent_id;
    }

    public function get_agent_rank($agent_id) {
        $rank_id = affwp_ranks_get_affiliate_rank($agent_id);
        if (empty($rank_id)) {
            return null;
        }
        return $rank_id;
    }

    public function get_agent_coleadership_agent_id($agent_id) {
        $single = true;
        $id = affiliate_wp()->affiliate_meta->get_meta($agent_id, self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID, $single);
        if (empty($id)) {
            return null;
        }
        return absint($id);
    }

    public function get_agent_coleadership_agent_rate($agent_id) {
        $single = true;
        $rate = absint(affiliate_wp()->affiliate_meta->get_meta($agent_id, self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_RATE, $single));
        // rates are in whole numbers
        if ($rate > 0) {
            $rate /= 100;
        }
        return $rate;
    }

    public function get_agent_id_by_email($agent_email) {
        $user = get_user_by('email', $agent_email);
        if (!empty($user)) {
            return affwp_get_affiliate_id(absint($user->ID));
        }

        return null;
    }

    public function get_coleadership_sponsored_agent_ids($coleadership_agent_id) {
        if (!is_int($coleadership_agent_id)) {
            throw new Exception("coleadershp_agent_id was not a valid int");
        }
        $where = 'WHERE meta_key = \'' . self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID
                . '\' AND meta_value = '
                . absint($coleadership_agent_id);

        $clauses = [
            'fields' => 'affiliate_id'
            , 'join' => ''
            , 'where' => $where
            , 'orderby' => 'affiliate_id'
            , 'order' => ''
            , 'count' => false];

        $args = ['fields' => 'ids', 'offset' => 0, 'number' => 1000];

        $results = affiliate_wp()->affiliate_meta->get_results($clauses, $args);

        return $results;
    }

    private function get_coleadership_agent_relationships($agent_ids) {
        if (empty($agent_ids)) {
            return null;
        }
        $agent_ids = array_map(absint, $agent_ids);


        $where = 'WHERE meta_key = \'' . self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID
                . '\' AND affiliate_id IN ('
                . join(",", $agent_ids) . ")";

        $clauses = [
            'fields' => '*'
            , 'join' => ''
            , 'where' => $where
            , 'orderby' => 'affiliate_id'
            , 'order' => ''
            , 'count' => false];

        $args = ['fields' => 'blah'
            , 'offset' => 0, 'number' => 1000];

        $results = [];
        $db_results = affiliate_wp()->affiliate_meta->get_results($clauses, $args);
        if (!empty($db_results)) {
            foreach ($db_results as $record) {
                if (!empty($record->meta_value)) {
                    $results[] = [self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID => $record->meta_value
                        , 'agent_id' => $record->affiliate_id];
                }
            }
        }
        return $results;
    }
    
    public function get_agent_base_shop_with_coleaderships($agent_id, $max_depth = 15) {
        $downline = $this->get_agent_downline_with_coleaderships($agent_id, $max_depth);
        if (!empty($downline)) {
            // filter out any children who are partners
            $children = $downline->children;
            $new_children = [];
            foreach ($children as $child) {
                $child_rank = $this->get_agent_rank($child->id);
                $this->logger->debug("get_agent_base_shop_with_coleaderships($agent_id)-> child({$child->id}) rank=$child_rank");
                $this->logger->debug("partner_rank_id {$this->partner_rank_id}");
                if ($child_rank != $this->partner_rank_id) {
                    $this->logger->debug("get_agent_base_shop_with_coleaderships($agent_id)-> child({$child->id}) added");
                    $new_children[] = $child;
                }
            }
            $downline->children = $new_children;
        }
        return $downline;
    }

    public function get_agent_downline_with_coleaderships($agent_id, $max_depth = 15) {
        if (empty($agent_id)) {
            return null;
        }
        $tree = $this->get_new_agent_tree_node('normal', $agent_id);
        $matrix_depth = affiliate_wp()->settings->get('affwp_mlm_matrix_depth');

        // need to go through 
        $nodesById = [];
        $all_ids = [];

        // Get 15 levels if no max or matrix depth is set
        if (empty($max_depth)) {
            $max_depth = !empty($matrix_depth) ? $matrix_depth : 15;
        }

        if (affwp_mlm_is_parent_affiliate($agent_id)) {

            $downline[0] = array($agent_id);
            $all_ids[] = $agent_id;

            $nodesById[$agent_id] = $tree;

            // Loop through levels and add sub affiliates
            for ($level = 1; $level <= $max_depth; $level++) {

                $parent_level = $level - 1;
                $parent_ids = $downline[$parent_level];
                $agent_relationships = affwp_mlm_get_sub_affiliates($parent_ids);
                $sub_ids = [];
                foreach ($agent_relationships as $agent_relationship) {
                    $sub_id = $agent_relationship->affiliate_id;
                    $sub_ids[] = $sub_id;
                    $all_ids[] = $sub_id;
                    $sub_obj = $this->get_new_agent_tree_node('normal', $sub_id);
                    $nodesById[$agent_relationship->affiliate_parent_id]->children[] = $sub_obj;
                    $nodesById[$sub_id] = $sub_obj;
                }
//                        
                if (empty($sub_ids)) {
                    break;
                } else {
                    $downline[$level] = $sub_ids;
                }
            }
        }

        // now go through and add in all the co-leadership values.
        if (!empty($all_ids)) {
            $coleadership_relationships = $this->get_coleadership_agent_relationships($all_ids);
            // if the co-leadership is anywhere in the downline we want to show that....
            foreach ($coleadership_relationships as $relationship) {
                $agent_id = $relationship['agent_id'];
                $coleadership_id = $relationship[self::AFFILIATE_META_KEY_COLEADERSHIP_AGENT_ID];
                if (isset($nodesById[$coleadership_id])) {
                    $child_agent = new Agent_Coleadership_Tree_Node($nodesById[$agent_id]);
                    $child_agent->type = 'coleadership';
                    $child_agent->coleadership_agent_id = $coleadership_id;
                    $nodesById[$coleadership_id]->children[] = $child_agent;
                }
            }
        }

        return $tree;
    }

    public function get_agent_progress_items($agent_id) {
        $progress_items = affiliate_wp()->settings->get('affwp_ltp_progress_items');

        $checklist = array();

        foreach ($progress_items as $key => $item) {
            $checklist[$item['id']] = ["name" => $item['name'], "date_completed" => null];
        }

        // now we need to get affiliate meta data
        $agent_items = $this->progress_items_db->get_progress_items($agent_id);
        if (!empty($agent_items)) {
            // merge the agent items with the global checklist items.
            foreach ($agent_items as $agent_item) {
                $admin_id = $agent_item['progress_item_admin_id'];
                if (isset($checklist[$admin_id])) {
                    $name = $checklist[$admin_id]['name'];
                    $checklist[$admin_id] = $agent_item;
                    $checklist[$admin_id]['name'] = $name;
                } else {
                    $checklist[$admin_id] = $agent_item;
                }
            }
        }


        return $checklist;
    }

    public function update_agent_progress_item($agent_id, $progress_item_admin_id, $completed) {

        // client will send up agent, and item admin id
        // system will try to grab the item by the admin id if it exists
        // if it doesn't exist it will grab the admin item from the global system and create it
        // if it does exist it will update the completed date on the item.


        $progress_item = $this->progress_items_db->get_by_admin_id($agent_id, $progress_item_admin_id);
        if (empty($progress_item)) {
            $progress_items = affiliate_wp()->settings->get('affwp_ltp_progress_items');
            foreach ($progress_items as $item) {
                if ($item['id'] == $progress_item_admin_id) {
                    $progress_item = ["progress_item_admin_id" => $item['id']
                        , 'affiliate_id' => $agent_id
                        , 'name' => $item['name']];
                    break;
                }
            }
        }

        if (empty($progress_item)) {
            // TODO: stephen handle the error, return??
            return null;
        }

        if ($completed) {
            $progress_item['date_completed'] = date("Y-m-d H:i:s");
        } else {
            $progress_item['date_completed'] = null;
        }

        if (empty($progress_item['progress_item_id'])) {
            $item_id = $this->progress_items_db->add($progress_item);
            return $item_id !== false;
        } else {
            return $this->progress_items_db->update($progress_item['progress_item_id'], $progress_item);
        }
    }

    /**
     * Returns the currently logged in user's agent id.
     * @return int
     */
    public function get_current_user_agent_id() {
        $val = absint(affwp_get_affiliate_id());
        if ($val === 0) {
            return null;
        }
        return $val;
    }

    public function get_agent_name($agent_id) {
        if (empty($agent_id)) {
            return null;
        }

        return affwp_get_affiliate_name($agent_id);
    }

    public function get_agent_displayname($agent_id) {
        if (empty($agent_id)) {
            return null;
        }

        $user_id = $this->get_agent_user_id($agent_id);

        $user_info = get_userdata($user_id);
        if (empty($user_info)) {
            return null;
        }

        return $user_info->display_name;
    }

    public function get_agent_email($agent_id) {
        if (empty($agent_id)) {
            return null;
        }

        return affwp_get_affiliate_email($agent_id);
    }

    public function get_agent_username($agent_id) {
        if (empty($agent_id)) {
            return null;
        }

        return affwp_get_affiliate_username($agent_id);
    }

    private function get_new_agent_tree_node($type, $id) {
        $obj = new Agent_Tree_Node();
        $obj->type = $type;
        $obj->id = $id;
        $obj->children = [];
        return $obj;
    }

    public function get_agent_user_id($agent_id) {
        if (empty($agent_id)) {
            return null;
        }

        return affwp_get_affiliate_user_id($agent_id);
    }

    public function create_agent($user_id, $payment_email, $agent_status = 'active') {
        $affiliate_args = array(
            'status' => $agent_status,
            'user_id' => $user_id,
            'payment_email' => $payment_email
        );
        return affwp_add_affiliate($affiliate_args);
    }

    public function set_agent_phone($agent_id, $phone) {
        // remove the key if it exists
        affwp_delete_affiliate_meta($agent_id, "cell_phone");

        // then add it.
        affwp_update_affiliate_meta($agent_id, 'cell_phone', $phone);
    }

    public function get_agent_registration_entry_id($agent_id) {
        if (empty($agent_id)) {
            return null;
        }

        return affwp_get_affiliate_meta($agent_id, 'gravity_forms_entry_id');
    }

    /**
     * Returns all of the tree heirarchy for the agents.
     * @param int $agent_id
     * @param int $count Current level count of the tree
     * @param int $max Maximum level count the tree can go
     * @return \AffiliateLTP\admin\Agent_Node
     */
    private function get_agent_upline_tree($agent_id, $count, $max) {
        if ($count >= $max) {
            return null;
        }

        $agent = new Agent_Node();
        $agent->id = $agent_id;

        $parent_id = affwp_mlm_get_parent_affiliate($agent_id);
        if (!empty($parent_id)) {
            $agent->parent = $this->get_agent_upline_tree($parent_id, $count + 1, $max);
        }

        $coleadership_id = $this->get_agent_coleadership_agent_id($agent_id);
        if (!empty($coleadership_id)) {
            $agent->coleadership = $this->get_agent_upline_tree($coleadership_id, $count + 1, $max);
        }
        return $agent;
    }

    public function get_partner_agent_leaderboard_points_data($limit, $date_filter, $company_agent_id) {
        $request = new Agent_Points_Summary_Request();
        $request->limit = $limit;
        $request->date_filter = $date_filter;
        $request->partners_only = true;
        $request->base_shop_only = true;
        $request->personal_sales_only = false;
        return $this->get_agent_point_summary_data($request);
//        return $this->get_agent_leaderboard_data($limit, $date_filter, $company_agent_id, true);
    }

    public function get_agent_leaderboard_points_data($limit, $date_filter, $company_agent_id) {
        return $this->get_agent_leaderboard_data($limit, $date_filter, $company_agent_id);
    }

    private function get_agent_leaderboard_data($limit, $date_filter, $company_agent_id, $partners_only = false) {
        $request = new Agent_Points_Summary_Request();
        $request->limit = $limit;
        $request->date_filter = $date_filter;
        $request->partners_only = $partners_only;
        $request->base_shop_only = $partners_only;
        return $this->get_agent_point_summary_data($request);
    }

    public function get_agent_point_summary_data(Agent_Points_Summary_Request $request) {
        global $wpdb;

        // TODO: stephen should we pull the the table prefixes and everything from the various affiliate and meta tables?
        // TODO: stephen should we make this into a view to try and simplify this??
        $params = [];
        $sql = "SELECT 
        r.affiliate_id AS 'agent_id', 
        wu.display_name,
        ROUND(sum(ifnull(rm.meta_value, 0)),0) as 'points' 
FROM 
        wp_affiliate_wp_referralmeta rm -- points reference
INNER JOIN 
        wp_affiliate_wp_referrals r USING(referral_id)  -- commissions table
INNER JOIN 
        wp_affiliate_wp_affiliates a USING (affiliate_id) -- agents table
INNER JOIN 
        wp_users wu ON a.user_id = wu.ID ";
        if ($request->partners_only) {
            $params[] = $this->partner_rank_id;
            $sql .= "
INNER JOIN (
                SELECT 
                        pam.affiliate_id 
                FROM 
                        wp_affiliate_wp_affiliatemeta pam 
                WHERE 
                        pam.meta_key='current_rank' 
                        AND pam.meta_value = %d
) partners 
        ON a.affiliate_id = partners.affiliate_id";
        }
        $sql .= "
WHERE 
        rm.meta_key = 'points'  -- grab only the points
        AND r.affiliate_id != %d  -- skip over the company user
        AND r.status = 'paid'  -- we only want paid commissions
        AND r.date BETWEEN %s AND %s ";
        $params[] = $this->company_agent_id;
        $params[] = $request->date_filter['start'];
        $params[] = $request->date_filter['end'];
        if ($request->base_shop_only) {
            $sql .= "
-- super shop is tracked by 
        AND r.referral_id NOT IN ( -- skip over points earned outside the base shop
                SELECT
                        m2.referral_id
                FROM
                        wp_affiliate_wp_referralmeta m2
                WHERE
                        m2.meta_key = 'generation_count'
                        AND m2.meta_value > 0
        ) ";
        }
        if ($request->personal_sales_only) {
            $sql .= " AND r.custom = 'direct'";
        }

        if (!empty($request->get_agent_ids())) {
            $sql .= " AND a.affiliate_id IN (" . join(",", $request->get_agent_ids()) . ") ";
        }

        $sql .= "
GROUP BY  
        a.affiliate_id 
ORDER BY  
        points DESC
        ,wu.display_name
LIMIT %d; -- Limit is configurable
";
        $params[] = $request->limit;

        return $this->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    function get_agent_leaderboard_direct_recruits($limit, $date_filter, $company_agent_id) {
        global $wpdb;
        $sql = "SELECT 
        count(a.affiliate_id) as 'recruits'
        ,u.display_name 
        ,u.user_login -- we include this to group by as display_name could be duplicated
FROM 
        wp_affiliate_wp_mlm_connections m 
JOIN 
        wp_affiliate_wp_affiliates a ON (m.direct_affiliate_id = a.affiliate_id) 
JOIN 
        wp_affiliate_wp_affiliates suba ON (m.affiliate_id = suba.affiliate_id)
JOIN 
        wp_users u ON (a.user_id = u.ID) 
WHERE
        suba.date_registered BETWEEN %s AND %s
        AND m.direct_affiliate_id != %d
GROUP BY  
        u.user_login
ORDER BY recruits DESC, u.display_name  LIMIT %d";

        return $this->get_results($wpdb->prepare($sql, [$date_filter['start'], $date_filter['end'], $company_agent_id, $limit]), ARRAY_A);
    }

    function get_partner_agent_leaderboard_base_shop_recruits($limit, $date_filter, $company_agent_id) {
        
    }

    function get_agent_registration_date($agent_id) {
        if (empty($agent_id)) {
            return null;
        }
        $agent = affwp_get_affiliate($agent_id);
        return $agent->date_registered;
    }

    function get_agent_ids_by_rank($rank_id) {
        global $wpdb;
        $sql = "SELECT am.affiliate_id FROM wp_affiliate_wp_affiliatemeta am WHERE am.meta_key = %s AND am.meta_value = %d ";
        return $this->get_col($wpdb->prepare($sql, ['current_rank', $rank_id]));
    }

    function search_agents_by_name_and_rank($name, $rank) {
        if (empty($name) || !is_numeric($rank)) {
            return [];
        }

        $lcSearch = strtolower($name);
        $agent_ids = $this->get_agent_ids_by_rank($rank);

        $results = [];
        foreach ($agent_ids as $id) {
            $agent = affwp_get_affiliate($id);
            $userData = get_userdata($agent->user_id);
            if (strpos(strtolower($userData->display_name), $lcSearch) !== false || strpos(strtolower($userData->username), $lcSearch) !== false) {
                $results[] = ["id" => $id, "display_name" => $userData->display_name];
            }
        }
        return $results;
    }

    function search_agents_by_code($agent_code_snippet) {
        global $wpdb;
        $sql = "SELECT am.meta_value AS 'code', u.display_name "
                . ", a.affiliate_id AS 'agent_id', a.user_id"
                . " FROM wp_affiliate_wp_affiliatemeta am "
                . " JOIN wp_affiliate_wp_affiliates a ON a.affiliate_id = am.affiliate_id "
                . " JOIN wp_users u ON a.user_id = u.ID "
                . " WHERE am.meta_key = 'custom_slug' AND am.meta_value LIKE '%s'";
        $prepared = $wpdb->prepare($sql, '%' . $wpdb->esc_like($agent_code_snippet) . '%');
        
        return $this->get_results($prepared, ARRAY_A);
    }
    
    public function get_agent_by_code( $agent_code ) {
        global $wpdb;
        $sql = "SELECT am.affiliate_id "
                . " FROM wp_affiliate_wp_affiliatemeta am "
                . " WHERE am.meta_key = 'custom_slug' AND am.meta_value = %s";
        $prepared = $wpdb->prepare($sql, $agent_code );
        return $this->get_var($prepared);
    }
    
    public function get_agent_code( $agent_id ) {
        global $wpdb;
        $sql = "SELECT am.meta_value AS 'code' "
                . " FROM wp_affiliate_wp_affiliatemeta am "
                . " WHERE am.meta_key = 'custom_slug' AND am.affiliate_id = %d";
        $prepared = $wpdb->prepare($sql, $agent_id );
        return $this->get_var($prepared);
    }
    
    private function get_var($statement) {
        global $wpdb;
//        $this->logger->debug("get_var() Query: " . $statement);
        $results = $wpdb->get_var($statement);
        if ($wpdb->last_error) {
            $this->logger->error("get_var() Query Failed with statement: " . $statement. "\nError: " . $wpdb->last_error);
        }
        return $results;
    }
    
    private function get_col($statement) {
        global $wpdb;
        $this->logger->debug("get_col() Query: " . $statement);
        $results = $wpdb->get_col($statement);
        if ($wpdb->last_error) {
            $this->logger->error("get_col() Query Failed with statement: " . $statement. "\nError: " . $wpdb->last_error);
        }
        return $results;
    }

    private function get_results($statement, $type) {
        global $wpdb;
        $this->logger->debug("get_results() Query: " . $statement . "\nResult Type: " . $type);
        $results = $wpdb->get_results($statement, $type);
        if ($wpdb->last_error) {
            $this->logger->error("get_results() Query Failed with statement: " . $statement. "\nError: " . $wpdb->last_error);
        }
        return $results;
    }

}
