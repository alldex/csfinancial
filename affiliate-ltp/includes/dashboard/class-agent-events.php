<?php

namespace AffiliateLTP\dashboard;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
use AffiliateLTP\admin\GravityForms\AffiliateLTP_Gravity_Forms_Add_On;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\Template_Loader;

class Agent_Events {
    /**
     *
     * @var number
     */
    private $company_agent_id;
    
    /**
     *
     * @var Template_Loader
     */
    private $template_loader;
    
    public function __construct(Settings_DAL $settings_dal, Template_Loader $template_loader) {
        // this hook gets triggered in the templates/dashboard-tab-events.php which is the way
        // the affiliate plugin works.
        add_action("affwp_affiliate_dashboard_events_show", array($this, "show_events"));
        $this->company_agent_id = $settings_dal->get_company_agent_id();
        $this->template_loader = $template_loader;
    }
    
    public function show_events( $agent_id ) {
        $ltpAddon = AffiliateLTP_Gravity_Forms_Add_On::get_instance();
        $forms = $ltpAddon->get_event_forms();
        $events = [];
        foreach ($forms as $form) {
            $events[] = $this->get_event_data_for_agent($form, $agent_id);
        }
        
        $this->display_events_for_agent($events, $agent_id);
    }
    
    private function display_events_for_agent($events, $agent_id) {
        if ($this->is_company_agent($agent_id)) {
            return $this->display_company_events($events);
        }
        else {
            return $this->display_events_for_partner($events);
        }
    }
    
    private function display_company_events($events) {
        $file = $this->template_loader->get_template_part("dashboard-tab", "events-company", false);
        include $file;
    }
    private function display_events_for_partner($events) {
        $file = $this->template_loader->get_template_part("dashboard-tab", "events-partner", false);
        include $file;
    }
    
    private function get_event_data_for_agent($form, $agent_id) {
        $ltpAddon = AffiliateLTP_Gravity_Forms_Add_On::get_instance();
        if ($this->is_company_agent($agent_id)) {
            return $ltpAddon->get_all_event_data( $form );
        }
        else {
            return $ltpAddon->get_event_data_for_agent( $form, $agent_id );;
        }
    }
    
    private function is_company_agent($agent_id) {
        return $agent_id == $this->company_agent_id;
    }
}