<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;

use \GFForms;
use \GFAddOn;
use \GF_Fields;
use AffiliateLTP\admin\GravityForms\Agent_Slug_Field;
use AffiliateLTP\admin\GravityForms\Agent_Partner_Lookup_Field;
use AffiliateLTP\admin\GravityForms\Agent_Register;
use AffiliateLTP\admin\GravityForms\Stripe_Errors_Ommissions;
use AffiliateLTP\Plugin;
use GFAPI;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\admin\Settings_DAL;

GFForms::include_addon_framework();

/**
 * Uses the Gravity Forms Addon framework to create the extensions needed for
 * the agent fields we've added to the form.
 *
 * @author snielson
 */
class AffiliateLTP_Gravity_Forms_Add_On extends GFAddOn {

    /**
     * The maximum number of form events to attempt to retrieve and display.
     */
    CONST MAXIMUM_EVENT_FORM_ENTRIES = 5000;
    
    protected $_version = GF_LIFETESTPREP_ADDON_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'affiliate-ltp';
    protected $_path = 'affiliate-ltp/class-gravity-forms-referral-url.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms Affiliate-LTP Add-On';
    protected $_short_title = 'Affiliate-LTP Add-On';
    private static $_instance = null;
    
    /**
     * The agent database service
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     * The settings database service
     * @var Settings_DAL
     */
    private $settings_dal;

    /**
     * Returns an instance of the gravity forms.
     * @return AffiliateLTP_Gravity_Forms_Add_On
     */
    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    public function __construct() {
        // we grab the container which is the best we can do due to
        // gravity forms static initializations mechanisms.
        $container = Plugin::instance()->get_container();
        $this->agent_dal = $container->get('agent_dal');
        $this->settings_dal = $container->get('settings_dal');
        parent::__construct();
    }

    /**
     * Register the agent fields to be used by Gravity Forms.
     */
    public function pre_init() {
        parent::pre_init();

        if ($this->is_gravityforms_supported()) {
            if (class_exists('GF_Field')) {
                GF_Fields::register(new Agent_Slug_Field());
                GF_Fields::register(new Agent_Partner_Lookup_Field());
            }
            
            new Agent_Register(); // instantiate it once.
            
            // new feature flag... so we can turn this off if things go wrong.
            if (Plugin::STRIPE_EO_HANDLING_ENABLED) {
                new Stripe_Errors_Ommissions($this->settings_dal);
            }
            new Event();
        }
    }
    
    public function get_forms_by_setting($settingName, $settingValue) {
        $forms = GFAPI::get_forms();
        $filter = function($a) use($settingName,$settingValue) {
            return $a && ($a[$settingName] == $settingValue); 
        };
        $filtered_forms = array_filter($forms, $filter);
        return $filtered_forms;
    }
    
    private function get_entries_for_partner( $form, $partner_id ) {
        $fields = GFAPI::get_fields_by_type($form, array(Agent_Partner_Lookup_Field::TYPE));
        $field_ids = array_map(function($fd) { return $fd['id'];}, $fields);

        $field_filters = array_map(function($fd) use($partner_id) { return ['key' => $fd, 'value' => $partner_id ]; }, $field_ids);
        $search_criteria = [
            'status' => 'active'
            ,'field_filters' => $field_filters
        ];
        return GFAPI::get_entries($form['id'], $search_criteria);
    }
    
    /**
     * Retrieves all of the event data for the entire company organized
     * by event then by partner and then by attendees.
     * @param type $form
     */
    public function get_all_event_data( $form ) {
        $sorting_criteria = null;
        
        $agent_dal = $this->agent_dal;
        
        $paging = ['page_size' => self::MAXIMUM_EVENT_FORM_ENTRIES];
        $entries = GFAPI::get_entries($form['id'], ['status' => 'active'], $sorting_criteria, $paging);
        $partner_data = [];
        $total_participants = 0;
        $total_paid = 0;
        if (!empty($entries)) {
            foreach ($entries as $entry) {
                $entry_data = $this->get_entry_event_data($form, $entry);
                $partner_id = !empty($entry_data['partner_id']) ? $entry_data['partner_id'] : 'unassigned';
                if (empty($partner_data[$partner_id])) {
                    $name = ($partner_id === "unassigned") ? "No Partner" : $agent_dal->get_agent_name($partner_id);
                    $partner_data[$partner_id] = ["registrants" => [], "name" => $name];
                }
                $partner_data[$partner_id]["registrants"][] = $entry_data;
                $total_participants += ($entry_data['spouse']) ? 2 : 1;
                if ($entry_data['price_paid_unformatted'] > 0) {
                    $total_paid += $entry_data['price_paid_unformatted'];
                }
            }
        }
        return ["title" => $form['title'], "partners" => $partner_data
                , 'total_participants' => $total_participants
                , 'total_paid' => money_format("%i", $total_paid)];
//        return $mappedEntries;
    }
    
    public function get_event_data_for_agent( $form, $agent_id ) {
        $entries = $this->get_entries_for_partner( $form, $agent_id );
        
        $mappedEntries = [];
        $total_participants = 0;
        $total_paid = 0;
        if (empty($entries)) {
            return ["title" => $form['title'], "registrants" => []
                , 'total_paid' => '0.00', 'total_participants' => 0];
        }
        foreach ($entries as $entry) {
            $mappedEntry = $this->get_entry_event_data($form, $entry);
            $mappedEntries[] = $mappedEntry;
            if ($mappedEntry['spouse']) {
                $total_participants += 2;
            }
            else {
                $total_participants += 1;
            }
            if ($mappedEntry['price_paid_unformatted'] > 0) {
                $total_paid += $mappedEntry['price_paid_unformatted'];
            }
        }
        return ["title" => $form['title']
                , "registrants" => $mappedEntries
                , 'total_participants' => $total_participants
                , 'total_paid' => money_format("%i", $total_paid)];
    }
    
    private function get_entry_event_data($form, $entry) {
        $name_id = Gravity_Forms_Utilities::get_form_field_id_by_label($form, 'Name');
        $spouse_id = Gravity_Forms_Utilities::get_form_field_id_by_label($form, 'Spouse if attending');
        $total_id = Gravity_Forms_Utilities::get_form_field_id_by_label($form, 'Total');


        $name = $this->get_field_value($form, $entry, $name_id);
        $spouse = $this->get_field_value($form, $entry, $spouse_id);
        $paid_total = $this->get_field_value($form, $entry, $total_id);
        $partner_id = Gravity_Forms_Utilities::get_form_field_value($form, $entry, 'agent-partner-lookup');


        $entry_data = ['name' => $name, 'spouse' => $spouse
                        , 'price_paid' => money_format("%i", $paid_total)
                        , 'price_paid_unformatted' => $paid_total
                        , 'partner_id' => $partner_id];
        return $entry_data;
    }
    
    public function get_event_forms() {
        return $this->get_forms_by_setting(Event::FORM_SETTING_NAME, "1");
    }
}
