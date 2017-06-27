<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
namespace AffiliateLTP\commands;

use \WP_CLI;
use \WP_CLI_Command;
use GFAPI;
use AffiliateLTP\admin\GravityForms\Agent_Partner_Lookup_Field;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\GravityForms\Gravity_Forms_Utilities;
use AffiliateLTP\admin\GravityForms\AffiliateLTP_Gravity_Forms_Add_On;
use AffiliateLTP\admin\Settings_DAL;

class Affiliate_LTP_GravityForms_Command extends \WP_CLI_Command {
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    public function __construct(Settings_DAL $settings_dal, Agent_DAL $agent_dal) {
        $this->agent_dal = $agent_dal;
        $this->partner_rank_id = $settings_dal->get_partner_rank_id();
    }
    
    /**
     * Updates all the entries of the form with the actual partner id
     * using the older partner last name format.
     * ## OPTIONS
     *
     * <form_id>
     * : The form id of all the entries to update
     *
     * ## EXAMPLES
     *
     *     wp affwp-ltp-gf update-partner-id 2
     * 
     * 
     * @subcommand update-partner-id
     * @param type $args
     */
    public function update_partner_id($args) {
        list ($form_id) = $args;
        
        if (empty($form_id) || !is_numeric($form_id)) {
            WP_CLI::error("Form id invalid: $form_id");
            return;
        }
        // TODO: stephen turn this into a constant.
        $form = GFAPI::get_form($form_id);
        if (empty($form)) {
            WP_CLI::error("Form id invalid: $form_id");
            return;
        }
        
        $label = 'Your upline Partners last name';
        $field_id = Gravity_Forms_Utilities::get_form_field_id_by_label($form, $label);
        if (empty($field_id)) {
            WP_CLI::error("Form id $form_id had no field with label '$label'");
            return;
        }
        $addon = AffiliateLTP_Gravity_Forms_Add_On::get_instance();
        
        $partner_lookup_field_id = Gravity_Forms_Utilities::get_form_field_id($form, Agent_Partner_Lookup_Field::TYPE);
        if (empty($partner_lookup_field_id)) {
             WP_CLI::error("Form id $form_id had no partner lookup field to update.  Please add one before running this command.");
            return;
        }
        
        // grab up to a 1000 records which should be enough for old data
        $paging = ['page_size' => 1000];
        $search_criteria = [];
        $sorting = null;
        $entries = GFAPI::get_entries($form_id, $search_criteria, $sorting, $paging);
        foreach ($entries as $entry) {
            $partner_last_name = $addon->get_field_value($form, $entry, $field_id);
            $partner_lookup_field_value = $addon->get_field_value($form, $entry, $partner_lookup_field_id);
            
            if (empty($partner_last_name)) {
                echo "\033[31mError:\033[0m Entry " . $entry['id'] . " had no value for field '$label'. Skipping update.\n";
                continue;
            }
            else if (!empty($partner_lookup_field_value)) {
                echo "\033[31mError:\033[0m Entry " . $entry['id'] . " already has a value for partner lookup field. Skipping update.\n";
                continue;
            }
            
            $agents = $this->agent_dal->search_agents_by_name_and_rank($partner_last_name, $this->partner_rank_id);
            if (empty($agents)) {
                echo "Entry: " . $entry['id'] . " had no partner id found for partner with last name: " . $partner_last_name . "\n";
            }
            else if (count($agents) > 1 ) {
                echo "Entry: " . $entry['id'] . " had multiple partner ids for partner with last name: " . $partner_last_name . "\n";
            }
            else {
                $partner_id = $agents[0]["id"];
                GFAPI::update_entry_field($entry['id'], $partner_lookup_field_id, $partner_id);
                WP_CLI::success("Entry: " . $entry['id'] . " updated with partner id of " . $partner_id . " for partner with last name: " . $partner_last_name);
            }            
            
        }
    }
}