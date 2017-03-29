<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

use AffiliateLTP\admin\Referrals_New_Request;
use AffiliateLTP\CommissionType;

/**
 * Description of class-commission-tree-validator
 *
 * @author snielson
 */
class Commission_Tree_Validator {
    
    public function __construct() {
        ;
    }
    
    /**
     * 
     * @param \AffiliateLTP\admin\commissions\Referrals_New_Request $request
     * @param array $trees
     * @return array
     * @throws \LogicException
     */
    public function validate(Referrals_New_Request $request, array $trees) {
        $errors = [];

        foreach ($trees as $tree) {
            if (!$tree instanceof Commission_Node) {
                throw new \LogicException("passed in parameters are not of type Commission_Processor_Item");
            }

            if ($request->type == CommissionType::TYPE_LIFE 
                    && !$request->skip_life_licensed_check) {
                // need to check if anyone has life insurance problems.
                $this->validate_tree_for_valid_life_insurance($tree, $errors);
            }
        }

        return $errors;
    }
    
    private function validate_tree_for_valid_life_insurance(Commission_Node $tree, &$errors) {
        if ($tree->agent->life_license_status->has_license() && !$tree->agent->life_license_status->has_active_licensed()) {
            // TODO: stephen need to add in the agent username
            $message = "Agent with id " . $tree->agent_id . " life insurance license has expired";
            $errors[] = ['type' => 'life_expired', 'message' => $message];
        }
        if (!empty($tree->parent_node)) {
            $this->validate_tree_for_valid_life_insurance($tree->parent_node, $errors);
        }
        if (!empty($tree->coleadership_node)) {
            $this->validate_tree_for_valid_life_insurance($tree->coleadership_node, $errors);
        }
    }
}
