<?php

namespace AffiliateLTP\admin;

use AffiliateLTP\Plugin;
use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\CommissionType;
use AffiliateLTP\admin\Life_License_Status;

require_once dirname(dirname(__FILE__)) . '/admin/class-life-license-status.php';

// TODO: stephen need to save the agent_parent_id piece... 
// how to do this I'm not sure.
require_once 'class-commission-company-processor.php';

class Commission_Processor_Item {

    public $commission_id;
    public $child_commission_id = null;
    public $amount;
    public $agent_id;
    public $points;
    public $type;
    public $is_direct_sale;
    public $date;
    public $generational_count = 0;
    // TODO: stephen would be it better to rename this to be highest_previous_rate?
    public $previous_rate = 0;
    public $rate = 0;
    public $contract_number;
    public $client_id;
    public $meta_items = [];
    public $rank;

    /**
     *
     * @var Life_License_Status
     */
    public $life_license_status;

    /**
     *
     * @var Commission_Processor_Item 
     */
    public $parent_node;

    /**
     *
     * @var Commission_Processor_Item 
     */
    public $coleadership_node;

}

class Commission_Processor_Meta_Item {

    private $key;
    private $value;

    public function __construct($key, $value) {
        $this->key = $key;
        $this->value = $value;
    }

    public function key() {
        return $this->key;
    }

    public function value() {
        return $this->value;
    }

}

/**
 * 
 *
 * @author snielson
 */
class Commission_Processor {

    /**
     * Safety catch to break loops that exceed this level in case
     * there is a recursive loop.
     */
    const HEIARCHY_MAX_LEVEL_BREAK = 100;
    
    const DEBUG_ENABLED = true;

    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;

    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;

    /**
     *
     * @var Settings_DAL
     */
    private $settings_dal;

    /**
     * Holds the commission arrays that were created during processing.
     * @var array
     */
    private $processed_items;

    const STATUS_DEFAULT = 'unpaid';

    public function __construct(Commission_DAL $commission_dal, Agent_DAL $agent_dal, Settings_DAL $settings_dal) {
        $this->commission_dal = $commission_dal;
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
        $this->processed_items = [];
    }

    public function parse_agent_trees(Referrals_New_Request $request) {
        $trees = [];

        // if the company is taking everything we don't process anything for other
        // agents
        if ($request->companyHaircutAll) {
            return $trees;
        }

        // need to have a branch on whether to grab data from existing commission records
        // or create the tree ourselves
        if ($this->is_repeat_business($request)) {
            // handle the population this way
            // $tree_parser = new commissions\Repeat_Commission_Tree_Parser($this->agent_dal);
        } else {
            $tree_parser = new commissions\New_Commission_Tree_Parser($this->agent_dal);
            $tree_parser->add_transformer(new Real_Rate_Calculate_Transformer());
        }
        return $tree_parser->parse($request);
    }

    private function is_repeat_business(Referrals_New_Request $request) {
        // for now there is no repeat business.
        return false;
    }

    public function validate_agent_trees_with_request(Referrals_New_Request $request, array $trees) {
        
        $validator = new commissions\Commission_Tree_Validator();
        return $validator->validate($request, $trees);
    }

    public function process_commission_request_updated(Referrals_New_Request $request) {
        $company_processor = new Commission_Company_Processor($this->commission_dal, $this->settings_dal);

        // reset this so we can stay clean.
        $this->processedItems = [];

        // create the client if necessary
        $request->client['id'] = $this->createClient($request->client);

        // prepare the initial company cut and update the request that
        // other subsequent commissions are based off of.
        $updatedRequest = $company_processor->prepare_company_commission($request);

        $agent_trees = $this->parse_agent_trees($request);
        $errors = $this->validate_agent_trees_with_request($request, $agent_trees);
        if (!empty($errors)) {
            // TODO: stephen throw an exception here???
            $this->debugLog(var_export($errors, true));
            return;
        }
        
        foreach ($agent_trees as $tree) {
            $this->process_item($tree, null);
        }
    }

    public function process_commission_request(Referrals_New_Request $request) {

        $company_processor = new Commission_Company_Processor($this->commission_dal, $this->settings_dal);

        // reset this so we can stay clean.
        $this->processedItems = [];

        // create the client if necessary
        $request->client['id'] = $this->createClient($request->client);

        // prepare the initial company cut and update the request that
        // other subsequent commissions are based off of.
        $updatedRequest = $company_processor->prepare_company_commission($request);

        $processingStack = $this->get_initial_commissions_to_process_from_request($updatedRequest);
        $stackBreakCount = 0;
        $this->debugLog("attempting processing.  Count is: " . $processingStack->count());
        while (!$processingStack->isEmpty() && 500 >= $stackBreakCount++) {
            $item = $processingStack->pop();
            $this->debugLog("process_commission_request() popped agent '{$item->agent_id}' off stack, processing");
            $this->process_item($item, $processingStack);
        }
        // adds to the company cut any remaining funds that were not used
        // in the commissions to the other agents.
        $company_processor->create_company_commission($this->processed_items, $request);

        $items = $this->processed_items;
        $this->processed_items = [];
        return $items;
    }

    private function process_item(Commission_Processor_Item $item, \SplStack $processingStack) {
        if ($this->should_process_item($item)) {
            $adjusted_amount = $item->amount * $item->rate;
            $this->debugLog("process_item() Agent '{$item->agent_id}' {$item->amount} * {$item->rate} = $adjusted_amount");
            $item->commission_id = $this->create_commission_for_item($item, $adjusted_amount);
        }
        else {
            $this->debugLog("process_item() skip agent '{$item->agent_id}' ");
        }
        // TODO: stephen add logging to state that the commission was added.

        $this->process_parent_item($item, $processingStack);
    }

    private function create_commission_for_item(Commission_Processor_Item $item, $adjusted_amount) {
        $this->debugLog("create_commission_for_item() creating commission for agent '{$item->agent_id}'");
        $custom = 'direct';
        $description = __("Personal sale", "affiliate-ltp");
        if (!$item->is_direct_sale) {
            $custom = 'indirect';
            $description = __("Override", "affiliate-ltp");
        }

        $commission = array(
            "agent_id" => $item->agent_id
            , "description" => $description
            , "amount" => $adjusted_amount
            , "reference" => $item->contract_number
            , "custom" => $custom
            , "context" => $item->type
            , "status" => self::STATUS_DEFAULT
            , "date" => $item->date
            , "client" => ["id" => $item->client_id
                , "contract_number" => $item->contract_number
            ]
            , "meta" => [
                "points" => $item->points
                , "agent_rate" => $item->rate
                , "original_amount" => $item->amount
                , "new_business" => 'Y' // TODO: stephen need to have a way of differentiating new from old. (the earliest date would probably be sufficient).
            ]
        );
        foreach ($item->meta_items as $key => $value) {
            $commission['meta'][$key] = $value;
        }
        if (isset($item->child_commission_id)) {
            $commission['meta']["child_commission_id"] = $item->child_commission_id;
        }


        // TODO: stephen is this the best place for this?? Do we really want it
        // on the commission?
        $coleadership_id = $this->agent_dal->get_agent_coleadership_agent_id($item->agent_id);
        if ($coleadership_id) {
            $coleadership_rate = $this->agent_dal->get_agent_coleadership_agent_rate($item->agent_id);
            $commission['meta']["coleadership_id"] = $coleadership_id;
            $commission['meta']["coleadership_rate"] = $coleadership_rate;
        }
//        $this->debugLog("create_commission_for_item() gen count: {$item->generational_count}");
        if ($item->generational_count > 0) {
            $commission['description'] .= "- {$item->generational_count} Generation ";
            $commission['meta']['generation_count'] = $item->generational_count;
        }

        // add the rank of the individual
        $rank_id = $this->agent_dal->get_agent_rank($item->agent_id);
        if (!empty($rank_id)) {
            $commission['meta']['rank_id'] = $rank_id;
        }

        // create referral
        $commission_id = $this->commission_dal->add_commission($commission);
        //$commission_id = affiliate_wp()->referrals->add($commission);

        if ($commission_id) {
            $this->debugLog("create_commission_for_item() created commission for '{$item->agent_id}'");
            $commission['id'] = $commission;
            $this->processed_items[] = $commission;
            do_action('affwp_ltp_commission_created', $commission);
            return $commission_id;
        } else {
            // TODO: stephen add more details here.
            throw new \Exception("Failed to create commission id for commission data: " . var_export($commission, true));
        }
    }

    private function should_process_item($item) {
        if (!$this->agent_dal->is_active($item->agent_id)) {
            $this->debugLog("agent {$item->agent_id} not active");
            return false;
        }
        if ($item->type === CommissionType::TYPE_LIFE && !$this->agent_dal->is_life_licensed($item->agent_id)) {
            $this->debugLog("agent {$item->agent_id} not licensed for life insurance");
            return false;
        }

        return true;
    }

    private function process_parent_item(Commission_Processor_Item $item, \SPLStack $processingStack) {

        if ($this->is_partner($item)) {
            $this->debugLog("process_parent_item() agent is partner, processing generational override");
            $this->add_generational_override_agent($item, $processingStack);
        } else if ($this->agent_has_coleadership($item)) {
            $this->debugLog("process_parent_item() agent has co-leadership, processing coleadership");
            $this->add_coleadership_agents($item, $processingStack);
        } else {
            $this->debugLog("process_parent_item() calling add_parent_agent");
            $this->add_parent_agent($item, $processingStack);
        }
    }

    private function agent_has_coleadership(Commission_Processor_Item $item) {
        $coleadership_id = $this->agent_dal->get_agent_coleadership_agent_id($item->agent_id);
        if (!empty($coleadership_id)) {
            return true;
        }
        return false;
    }

    private function add_parent_agent(Commission_Processor_Item $child_item, \SplStack $processingStack) {

        $parent_agent_id = $this->agent_dal->get_parent_agent_id($child_item->agent_id);
        if ($parent_agent_id != null && $parent_agent_id != 0) {
            $this->debugLog("add_parent_agent() pre add '$parent_agent_id' to stack " . var_export($parent_agent_id, true));
            $parent_item = $this->create_parent_processor_item($child_item, $parent_agent_id);
            $this->debugLog("add_parent_agent() adding '{$parent_item->agent_id}' to stack");
            $processingStack->push($parent_item);
        }
    }

    private function create_parent_processor_item(Commission_Processor_Item $child_item, $parent_agent_id) {

        // we have to use the highest rate for our calculations.
        $previous_rate = max($child_item->rate, $child_item->previous_rate);
        $parent_rate = $this->agent_dal->get_agent_commission_rate($parent_agent_id);

        //$child_rate = $this->agent_dal->get_agent_commission_rate($child_item->agent_id);
        $adjusted_rate = $this->get_adjusted_rate($parent_rate, $previous_rate);
        $this->debugLog("previous rate is {$previous_rate} and parent rate is $parent_rate, adjusted rate is $adjusted_rate");

        $item = clone $child_item;
        $item->agent_id = $parent_agent_id;
        $item->is_direct_sale = false;
        $item->previous_rate = max($previous_rate, $parent_rate);
        $item->child_commission_id = $child_item->commission_id;
        $item->commission_id = null;
//        $this->debugLog("setting previous rate to be $previous_rate");
        $item->rate = $adjusted_rate;
        $item->meta_items = [];
        $this->debugLog("create_parent_processor_item() adding {$parent_agent_id} to stack");
        return $item;
    }

    private function get_adjusted_rate($nominal_rate, $previous_rate) {
        if ($nominal_rate > $previous_rate) {
            return $nominal_rate - $previous_rate;
        }
        return 0;
    }

    private function is_partner(Commission_Processor_Item $item) {

        $partner_rank_id = $this->settings_dal->get_partner_rank_id();
        $this->debugLog("is_partner: agent rank '$rank' partner rank id '$partner_rank_id'");
        if (!empty($partner_rank_id) && $item->rank === $partner_rank_id) {
            return true;
        }
        return false;
    }

    /**
     * Assumes the parent agent is a partner
     * @param \AffiliateLTP\admin\Commission_Processor_Item $child_item
     * @param \SplStack $processingStack
     */
    private function add_generational_override_agent(Commission_Processor_Item $child_item, \SplStack $processingStack) {
        // if partner create a new item and increment the generational override

        $coleadership_agent_id = $this->agent_dal->get_agent_coleadership_agent_id($child_item->agent_id);
        $parent_agent_id = $this->agent_dal->get_parent_agent_id($child_item->agent_id);
        if (empty($parent_agent_id) && empty($coleadership_agent_id)) {
            return;
        }

        if (!empty($coleadership_agent_id)) {
            $this->debugLog("generational override has co-leadership.  Creating co-leadership generational override");
            $this->add_parent_partner_coleadership_items($child_item, $coleadership_agent_id, $parent_agent_id, $processingStack);
        } else if ($this->is_partner($parent_agent_id)) {
            $this->debugLog("parent is partner, adding generational item");
            $processingStack->push($this->get_generational_item($child_item, $parent_agent_id));
        } else {
            $this->debugLog("adding parent agent as parent is not a partner nor coleadership");
            $this->add_parent_agent($child_item, $processingStack);
        }
    }

    private function add_parent_partner_coleadership_items(Commission_Processor_Item $child_item, $coleadership_agent_id, $parent_agent_id, \SplStack $processingStack) {
        $coleadership_is_partner = !empty($coleadership_agent_id) && $this->is_partner($coleadership_agent_id);
        $rate = $this->agent_dal->get_agent_coleadership_agent_rate($child_item->agent_id);
        $active_item = clone $child_item;
        $active_item->points = round($child_item->points * $rate, 2);
        $active_item->amount = round($child_item->amount * $rate, 2);

        $passive_item = clone $child_item;
        $passive_item->points = $child_item->points - $active_item->points;
        $passive_item->amount = $child_item->amount - $active_item->amount;

        if ($coleadership_is_partner) {
            $gen_item = $this->get_generational_item($active_item, $coleadership_agent_id);
            $processingStack->push($gen_item);
        } else {
            $parent_item = $this->create_parent_processor_item($active_item, $coleadership_agent_id);
            $processingStack->push($parent_item);
        }

        $passive_gen_item = $this->get_generational_item($passive_item, $parent_agent_id);
        $processingStack->push($passive_gen_item);
    }

    private function get_generational_item(Commission_Processor_Item $child_item, $parent_agent_id) {
        $item = clone $child_item;
        $item->agent_id = $parent_agent_id;
        $item->is_direct_sale = false;
        $item->previous_rate = $item->rate;
        $item->rate = $this->get_generational_rate($item);
        $item->generational_count += 1;
        // switch the commission ids so we can track them.
        $item->child_commission_id = $child_item->commission_id;
        $item->commission_id = null;
        $item->meta_items = [];
        $this->debugLog("get_generational_item() adding {$parent_agent_id} to stack");
        return $item;
    }

    private function get_generational_rate(Commission_Processor_Item $item) {
        // TODO: stephen... how do we handle the situation where we have a generational override
        // that is at 17%, but the person above them is not a partner....
        // how do we handle the calculations for this? Or if two levels up the person is not a partner...
        // If their commission is 50% - 17% we'll have > 100% for our commission calculations....
        // we currently only handle three generational overrides.
        return $this->settings_dal->get_generational_override_rate($item->generational_count + 1);
    }

    /**
     * Adds the active leader (who is not a direct parent) and the passive leader
     * who is the parent of the current agent.  The points and amounts are adjusted
     * 
     * @param \AffiliateLTP\admin\Commission_Processor_Item $item
     * @param \SplStack $processingStack
     */
    private function add_coleadership_agents(Commission_Processor_Item $item, \SplStack $processingStack) {

        $coleadership_id = $this->agent_dal->get_agent_coleadership_agent_id($item->agent_id);
        $coleadership_rate = $this->agent_dal->get_agent_coleadership_agent_rate($item->agent_id);

        $active_leader_item = clone $item;
        $active_leader_item->amount = round($item->amount * $coleadership_rate, 2);
        $active_leader_item->points = round($item->points * $coleadership_rate, 2);

        $processingStack->push($this->create_parent_processor_item($active_leader_item, $coleadership_id));

        // since we already have an add parent let's just call that to make sure
        // it pushes at the top of the stack.
        $passive_adjusted_child_item = clone $item;
        $passive_adjusted_child_item->amount = $item->amount - $active_leader_item->amount;
        $passive_adjusted_child_item->points = $item->points - $active_leader_item->points;
        $this->add_parent_agent($passive_adjusted_child_item, $processingStack);
    }

    private function get_initial_commissions_to_process_from_request(Referrals_New_Request $request) {
        $stack = new \SplStack();

        // if the company is taking everything we don't process anything for other
        // agents
        if ($request->companyHaircutAll) {
            $this->debugLog('skipping due to company haircut');
            return $stack;
        }

        foreach ($request->agents as $agent) {
            $splitPercent = $agent->split / 100;

            $rate = $this->agent_dal->get_agent_commission_rate($agent->id);

            $item = new Commission_Processor_Item();
            $item->amount = $request->amount * $splitPercent;
            // TODO: stephen not sure I like this split rate piece here
            // either it needs to be it's own attribute or other things like contract_number should go there.
            $item->meta_items['split_rate'] = $splitPercent;
            $item->agent_id = $agent->id;
            $item->date = $request->date;
            $item->is_direct_sale = true;
            $item->points = $request->points;
            $item->type = $request->type;
            $item->contract_number = $request->client['contract_number'];
            $item->client_id = $request->client['id'];
            $item->rate = $rate;
            $stack->push($item);
        }

        return $stack;
    }

    /**
     * Creates the client and returns the id of the client that was created.
     * @param array $clientData
     * @return string
     */
    private function createClient($clientData) {
        // we already have a client id we don't need to create a client here
        if (!empty($clientData['id'])) {
            // we return the id to keep the code flow the same.
            return $clientData['id'];
        }
        // create the client on the sugar CRM system.
        $instance = Plugin::instance()->getSugarCRM();
        return $instance->createAccount($clientData);
    }

    private function debugLog($message) {
        if (self::DEBUG_ENABLED) {
            error_log($message);
        }
    }

}
