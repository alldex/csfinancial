<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\tools;

use AffiliateLTP\admin\tools\Commission_Parser;

use League\Csv\Reader;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\Sugar_CRM_DAL;
use AffiliateLTP\admin\Referrals_Agent_Request;
use AffiliateLTP\admin\Commission_Processor;
use Psr\Log\LoggerInterface;
use AffiliateLTP\admin\Commission_Validation_Exception;

/**
 * Creates a commission for each of the CSV records found.
 *
 * @author snielson
 */
class Commissions_Importer {
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     *
     * @var Sugar_CRM_DAL
     */
    private $sugar_crm_dal;
    
    /**
    /**
     *
     * @var Commission_Processor
     */
    private $commission_processor;
    
    /**
     * 
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(LoggerInterface $logger, Agent_DAL $agent_dal
            , Sugar_CRM_DAL $sugar_crm_dal, Commission_Processor $processor) {
        $this->sugar_crm_dal = $sugar_crm_dal;
        $this->agent_dal = $agent_dal;
        $this->commission_processor = $processor;
        $this->logger = $logger;
    }
    
    public function import_from_file($path, $skip_life_licensed_check) {
        $reader = Reader::createFromPath($path);
        
        $parser = new Commission_Parser($this->logger, $reader, $this->agent_dal, $this->sugar_crm_dal);
        $parser->skip_life_license_check($skip_life_licensed_check);
        
//        echo "<pre>";
        $requests_to_process = [];
//        $time1 = microtime(true);
        while ($request = $parser->next_commission_request()) {
            $validate_errors = $this->validate_request($request);
            if (empty($validate_errors)) {
                $requests_to_process[] = $request;
            }
            else {
                throw new \Exception("Import failed due to errors found on line " 
                        . $request->line_number .  ". Errors: " . implode("\n", $validate_errors));
            }
        }
//        echo "Validation Time: " . (microtime(true) - $time1) . "\n";
        if (!empty($requests_to_process)) {
            
            foreach ($requests_to_process as $request_to_process) {
//                $time1 = microtime(true);
                $processor = $this->commission_processor;
                try {
                    // go through and create the items;
                    $processor->process_commission_request($request_to_process);
    //                echo "record import time: " . (microtime(true) - $time1) . "\n";
                }
                catch (Commission_Validation_Exception $exception) {
                    $this->logger->error(var_export($exception->get_validation_errors(), true));
                    $errors = array_map(function($item) { return $item['message']; }, $exception->get_validation_errors());
                    throw new \Exception("Import failed due to errors found on line " 
                        . $request_to_process->line_number .  ". Errors: " . implode("\n ", $errors), $exception->getCode(), $exception);
                }
            }
            
        }
//        echo "</pre>";
//        exit;
    }
    
    public function validate_request(Commission_CSV_Request $request) {
        $errors = [];
        if ($request->amount <= 0) {
            $errors[] = "Request amount must be greater than $0.00";
        }
        
        if (empty($request->date)) {
            $errors[] = "Empty date field found.  Date for commission is required";
        }
        
        if ($request->points < 0) {
            $errors[] = "Points cannot be less than 0";
        }
        
        if (empty($request->agents)) {
            $errors[] = "No agents were found in the system for this import";
        }
        else {
            $agent_count = 1;
//            echo "<pre>";
//            echo $request->line_number;
//            var_dump($request->agents);
//            echo "</pre>";
            foreach ($request->agents as $agent) {
                $agent_errors = $this->validate_agent($agent);
                if (!empty($agent_errors)) {
                    $message = "Agent[" . $agent_count . "] " . join(" ", $agent_errors) . ".  ";
                    $errors[] = $message;
                }
                $agent_count++;
            }
        }
        return $errors;
    }
    
    public function validate_agent(Referrals_Agent_Request $agent) {
        $errors = [];
        if (empty($agent->id)) {
            $msg = "Agent email missing or agent could not be found in system to process request.";
            $msg .= $this->get_agent_description($agent);
            $errors[] = $msg;
        }
        
        if (empty($agent->split) || $agent->split <= 0) {
            $errors[] = "Agent split missing or less than or equal to 0.";
            $msg .= $this->get_agent_description($agent);
        }
        return $errors;
    }
    
    private function get_agent_description(Referrals_Agent_Request $agent) {
        $description = "";
        if (!empty($agent->email)) {
            $description .= " Agent email: " . $agent->email;
        }
        
        if (!empty($agent->name)) {
            $description .= " Agent name: " . $agent->name;
        }
        return $description;
    }
}
