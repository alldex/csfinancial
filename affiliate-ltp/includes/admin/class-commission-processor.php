<?php

namespace AffiliateLTP\admin;

use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\Commission_Type;
use AffiliateLTP\admin\commissions\Commission_Node;
use AffiliateLTP\admin\commissions\Points_Calculate_Transformer;
use AffiliateLTP\admin\commissions\Partners_Filter_Transformer;
use AffiliateLTP\admin\commissions\New_Commission_Tree_Parser;
use AffiliateLTP\admin\commissions\Repeat_Commission_Tree_Parser;
use AffiliateLTP\admin\commissions\Commission_Tree_Validator;
use AffiliateLTP\admin\commissions\Real_Rate_Calculate_Transformer;
use AffiliateLTP\admin\Commission_Request_Persister;
use Exception;
use AffiliateLTP\admin\Commission_Status;
use AffiliateLTP\Commission;
use AffiliateLTP\Sugar_CRM_DAL;
use Psr\Log\LoggerInterface;

class Commission_Validation_Exception extends \RuntimeException {
    private $validation_errors;
    public function __construct($validation_errors, $message, $code=null, $previous = null) {
        $this->validation_errors = $validation_errors;
        parent::__construct($message, $code, $previous);
    }
    
    public function get_validation_errors() {
        return $this->validation_errors;
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
    
    private $commission_request_id = null;
    
    /**
     * The id of the agent representing the company.
     * @var int
     */
    private $company_agent_id;
    
    /**
     * The client relationship management dal
     * @var Sugar_CRM_DAL 
     */
    private $sugar_crm;
    
    const STATUS_DEFAULT = 'unpaid';

    public function __construct(Commission_DAL $commission_dal
            , Agent_DAL $agent_dal, Settings_DAL $settings_dal
            , Sugar_CRM_DAL $sugar_crm
            , LoggerInterface $logger) {
        $this->commission_dal = $commission_dal;
        $this->agent_dal = $agent_dal;
        $this->settings_dal = $settings_dal;
        $this->processed_items = [];
        $this->sugar_crm = $sugar_crm;
        $this->logger = $logger;
    }

    public function parse_agent_trees(Referrals_New_Request $request) {
        $trees = [];

        // need to have a branch on whether to grab data from existing commission records
        // or create the tree ourselves
        if ($this->is_repeat_business($request)) {
            // handle the population this way
             $tree_parser = new Repeat_Commission_Tree_Parser($this->agent_dal, $this->commission_dal);
        } else {
            $tree_parser = new New_Commission_Tree_Parser($this->settings_dal, $this->agent_dal);
        }
        return $tree_parser->parse($request);
    }

    private function is_repeat_business(Referrals_New_Request $request) {
        if ($request->type == Commission_Type::TYPE_LIFE) {
            return !$request->new_business;
        }
        else {
            // we treat non-life business as just a new request
            return false;
        }
    }

    public function validate_agent_trees_with_request(Referrals_New_Request $request, array $trees) {
        
        $validator = new Commission_Tree_Validator();
        return $validator->validate($request, $trees);
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

        $agent_trees = $this->parse_agent_trees($updatedRequest);
        $errors = $this->validate_agent_trees_with_request($updatedRequest, $agent_trees);
        if (!empty($errors)) {
            // TODO: stephen throw an exception here???
            throw new Commission_Validation_Exception($errors, "Commission Validation Error");
        }
        
        // record the original request, not the updated request.
        // even if it's a company only request we still grab all the trees and
        // save it off for future processing.
        $this->commission_request_id = $this->save_commission_request($request, $agent_trees);
        
        // print out pre transformation.
        if (self::DEBUG_ENABLED) {
            $this->log_request($request, $agent_trees);
        }
        
        // transform the trees now
        $transformed_trees = $this->perform_tree_transformations($updatedRequest, $agent_trees);
        
        
        if (self::DEBUG_ENABLED) {
            $this->log_request($request, $transformed_trees);
        }
        
         // if the company is taking everything we don't process anything for other
        // agents
        if (!$request->companyHaircutAll) {
            foreach ($transformed_trees as $tree) {
                if ($tree->split_rate < 100) {
                    $split_request = $this->get_split_request($updatedRequest, $tree->split_rate);
//                    $this->log_request($split_request, [$tree]);
                    $this->process_item($split_request, $tree);
                }
                else {
                    $this->process_item($updatedRequest, $tree);
                }
            }
        }
        
        // adds to the company cut any remaining funds that were not used
        // in the commissions to the other agents.
        $company_processor->create_company_commission($this->processed_items, $updatedRequest, $this->commission_request_id);
        
        $items = $this->processed_items;
        $this->processed_items = [];
        return $items;
    }
    
    private function get_split_request(Referrals_New_Request $request, $split_rate) {
        // TODO: stephen do we want to conver this to a transformer??
        $split = clone $request;
        $split->amount = $split_rate * $request->amount;
        return $split;
    }
    
    private function log_request(Referrals_New_Request $request, $agent_trees) {
        $msg = "";
        $msg .= "Date: {$request->date}\n";
        $msg .= "Commission Request id: {$this->commission_request_id}\n";
        $msg .= "Contract Number is: {$request->client['contract_number']}\n";
        $msg .= "State of Sale (for life licensing): {$request->client['state_of_sale']}\n";
        $msg .= "Company Haircut All is: " . ($request->companyHaircutAll ? "YES" : "NO") . "\n";
        $msg .= "New Business: " . ($request->new_business ? "YES" : "NO") . "\n";
        $msg .= "Is Renewal: " . ($request->renewal ? "YES" : "NO") . "\n";
        $msg .= "Company Haircut Skip is: " . ($request->skipCompanyHaircut ? "YES" : "NO") . "\n";
        $msg .= "Skip Life License Check is: " . ($request->skip_life_licensed_check ? "YES" : "NO") . "\n";
        $msg .= "Company Haircut Percent is: " . $request->companyHaircutPercent . "\n";
        $msg .= "Amount: {$request->amount}\n";
        $msg .= "Points: {$request->points}\n";
        $msg .= "Type: " . ($request->type == Commission_Type::TYPE_LIFE ? "LIFE" :"NON-LIFE") ."\n";
        $msg .= "Agent Trees\n";
        $this->logger->debug($msg);
        foreach ($agent_trees as $tree) {
            $this->print_tree($request, $tree, '>');
        }
        $this->logger->debug("------------------------\n\n");
    }
    
    private function print_tree(Referrals_New_Request $request, Commission_Node $tree, $prefix) {
        $life_license_message = "NO";
        if ($tree->agent->life_license_status->has_active_licensed($request->client['state_of_sale'])) {
            $life_license_message = "YES";
        }
        $active_message = "NO";
        if ($tree->agent->is_active) {
            $active_message = "YES";
        }
        $msg = "";
        $msg .= "$prefix ------\n";
        $msg .= "$prefix ID: " . $tree->agent->id. "\n";
        $msg .= "$prefix Real Rate: " . $tree->rate . "\n";
        $msg .= "$prefix Points: " . $tree->points . "\n";
        $msg .= "$prefix Is Active: " . $active_message . "\n";
        $msg .= "$prefix Agent Rate: " . $tree->agent->rate. "\n";
        $msg .= "$prefix Rank: " . $tree->agent->rank . "\n";
        $msg .= "$prefix Active Life License: $life_license_message \n";
        $msg .= "$prefix Generational Count: " . $tree->generational_count . " \n";
        $this->logger->debug($msg);
        if ($tree->parent_node != null) {
            $this->logger->debug("$prefix Parent Node\n");
            $this->print_tree($request, $tree->parent_node, $prefix . "    ");
        }
        if ($tree->coleadership_node != null) {
            $this->logger->debug("$prefix Coleadership Rate: " . $tree->coleadership_rate . "\n");
            $this->logger->debug("$prefix Coleadership Node\n");
            $this->print_tree($request, $tree->coleadership_node, $prefix . "    ");
        }
        $this->logger->debug("$prefix ------\n");
    }
    
    private function perform_tree_transformations(Referrals_New_Request $request, $agent_trees) {
        $transformations = [];
         // only include the partners
        if ($request->renewal) {
            $transformations[] = new Partners_Filter_Transformer($request);
        }
        
        $transformations[] = new Real_Rate_Calculate_Transformer($this->settings_dal, $request);
        $transformations[] = new Points_Calculate_Transformer($request);
        
        $trees_to_process = $agent_trees;
        foreach ($transformations as $transformer) {
            $this->logger->debug("Performing transformation: " . get_class($transformer));
            $result_trees = [];
            foreach ($trees_to_process as $tree) {
                $result = $transformer->transform($tree);
                if ($result->has_result_nodes()) {
                    $this->logger->debug("result of transformation is: " . count($result->get_result_nodes()));
                    $result_trees = array_merge($result_trees, $result->get_result_nodes());
                }
                else {
                    $this->logger->debug("transformation pruned all nodes");
                }
            }
            $trees_to_process = $result_trees;
        }
        return $trees_to_process;
    }
    
    private function save_commission_request(Referrals_New_Request $request, $agent_trees) {
        $persistor = new Commission_Request_Persister($this->commission_dal);
        return $persistor->persist($request, $agent_trees);
    }

    private function process_item(Referrals_New_Request $request, Commission_Node $item) {
        $adjusted_amount = round($request->amount * $item->rate, 2, PHP_ROUND_HALF_DOWN);
        $this->create_commission_for_item($request, $item, $adjusted_amount);
        
        if ($item->coleadership_node != null) {
            $this->process_coleadership_item($request, $item);
            
        }
        else if ($item->parent_node != null) {
            $this->process_item($request, $item->parent_node);
        }
    }
    
    private function process_coleadership_item(Referrals_New_Request $request, Commission_Node $item) {
        $coleadership_rate = $item->coleadership_rate;
        
        $active_request = clone $request;
        $active_request->amount = round($request->amount * $coleadership_rate, 2);
//        $active_request->points = round($request->points * $coleadership_rate, 2);
        $this->process_item($active_request, $item->coleadership_node);

        // we should have both a coleadership and a parent, but a safety check here
        // in case the heirarchy wasnt setup properly.
        if (!empty($item->parent_node)) {
            $passive_request = clone $request;
            $passive_request->amount = $request->amount - $active_request->amount;
//            $passive_request->points = $request->points - $active_request->points;
            $this->process_item($passive_request, $item->parent_node);
        }
    }
    
    private function create_commission_for_item(Referrals_New_Request $request, Commission_Node $item, $adjusted_amount) {
        $this->logger->debug("create_commission_for_item() creating commission for agent '{$item->agent_id}'");
        $this->logger->debug("create_commission_for_item() points are '{$request->points}'");
        $custom = 'direct';
        $description = __("Personal sale", "affiliate-ltp");
        if (!$item->is_direct_sale) {
            $custom = 'indirect';
            $description = __("Override", "affiliate-ltp");
        }
        
        // TODO: stephen I think most the metadata stuff is redundant now with the commission_request
        // records... look at cleaning this up.
        $commission = new Commission();
        $commission->agent_id = $item->agent->id;
        $commission->description = $description;
        $commission->amount = $adjusted_amount;
        $commission->reference = $request->client['contract_number'];
        $commission->custom = $custom;
        $commission->context = $request->type;
        $commission->status = $this->get_status_for_save($item, $adjusted_amount);
        $commission->date = $request->date;
        $commission->client = $request->client;
        $commission->meta = [
            "points" => $item->points
            // TODO: stephen agent_rate and agent_real_rate may not be needed anymore...?
            , "agent_rate" => $item->agent->rate
            , "agent_real_rate" => $item->rate
            // TODO: stephen should this be bundled into the referrals_new_request piece??
            , "commission_request_id" => $this->commission_request_id
        ];
        if (!empty($item->split_rate)) {
            $commission->meta["split_rate"] = $item->split_rate;
        }
        if (isset($item->parent_node)) {
            $commission->meta['agent_parent_id'] = $item->parent_node->agent->id;
        }
        
        if (isset($item->coleadership_node)) {
            $commission->meta['coleadership_id'] = $item->coleadership_node->agent->id;
            $commission->meta['coleadership_rate'] = $item->coleadership_rate;
        }
        
        if ($item->generational_count > 0) {
            $commission->description .= "- {$item->generational_count} Generation ";
            $commission->meta['generation_count'] = $item->generational_count;
        }

        // add the rank of the individual
        if (!empty($item->agent->rank)) {
            $commission->meta['rank_id'] = $item->agent->rank;
        }

        // create referral
        $commission_id = $this->commission_dal->add_commission($commission);
        //$commission_id = affiliate_wp()->referrals->add($commission);

        if ($commission_id) {
//            $this->logger->debug("create_commission_for_item() created commission for '{$item->agent->id}'");
            $commission->commission_id = $commission;
            $this->processed_items[] = $commission;
            do_action('affwp_ltp_commission_created', $commission);
            return $commission_id;
        } else {
            throw new Exception("Failed to create commission id for commission data: " . var_export($commission, true));
        }
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
        return $this->sugar_crm->createAccount($clientData);
    }

    private function get_status_for_save(Commission_Node $item, $adjusted_amount) {
        
        $company_agent_id = $this->settings_dal->get_company_agent_id(); 
        // company status items are always paid.
        if ($item->agent->id == $company_agent_id) {
            return Commission_Status::PAID;
        }
        else if ($adjusted_amount <= 0) {
            return Commission_Status::PAID;
        }
        
        return self::STATUS_DEFAULT;
    }
    
    private function get_company_agent_id() {
        if (empty($this->company_agent_id)) {
            $this->company_agent_id = $this->settings_dal->get_company_agent_id();
        }
        return $this->company_agent_id;
    }

}