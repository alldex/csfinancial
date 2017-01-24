<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\csv;

use League\Csv\Reader;
use AffiliateLTP\Plugin;

/**
 * Creates a commission for each of the CSV records found.
 *
 * @author snielson
 */
class Commissions_Importer {
    
    public function import_from_file($path) {
        $settings_dal = null;
        $commission_dal = null;
        $agent_dal = null;
        $sugar_crm_dal = Plugin::instance()->getSugarCRM();
        
        $reader = Reader::createFromPath($path);
        
        $parser = new Commission_Parser($reader, $agent_dal, $sugar_crm_dal);
        
        while ($request = $parser->next_commission_request()) {
            $processor = new \AffiliateLTP\admin\Commission_Processor($commission_dal, $agent_dal, $settings_dal);
            // go through and create the items;
            $processor->process_commission_request($request);
        }
    }
}
