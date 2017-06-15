<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;
use GF_Field;

/**
 * Handles the Gravity Forms Agent Custom Slug field.
 *
 * @author snielson
 */
class Agent_Partner_Lookup_Field extends GF_Field {
    
    /**
     * Unique name of the type of field this is for gravity forms.
     * @var type 
     */
    public $type = 'agent-partner-lookup';
    
    /**
     * Returns the title of the field that shows up on the editor fields menu.
     * @return string
     */
    public function get_form_editor_field_title() {
        return esc_attr__('Parkner Lookup', 'affiliate-ltp');
    }

    /**
     * Connects the button to add the field with the group it's a part of and its
     * text label.
     * @return array
     */
    public function get_form_editor_button() {
        return array(
            'group' => 'agents',
            'text' => $this->get_form_editor_field_title(),
        );
    }

    /**
     * Handles the button adding and adds in the field group information if it's
     * not there already.
     * @param array $field_groups
     * @return array
     */
    public function add_button($field_groups) {
        $field_groups = $this->maybe_add_field_group($field_groups);

        return parent::add_button($field_groups);
    }

    /**
     * Adds the custom field group if it doesn't already exist.
     *
     * @param array $field_groups The field groups containing the individual field buttons.
     *
     * @return array
     */
    public function maybe_add_field_group($field_groups) {
        foreach ($field_groups as $field_group) {
            if ($field_group['name'] == 'agents') {
                return $field_groups;
            }
        }

        $field_groups[] = array(
            'name' => 'agents',
            'label' => __('Agent Fields', 'affiliate-ltp'),
            'fields' => array()
        );

        return $field_groups;
    }
    
    /**
     * Much of this was taken from the Gravity_Forms GF_Field_Text class as this
     * field is the most similar.  It returns the HTML content for the field.
     * @param array $form
     * @param string $value
     * @param type $entry
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null) {
        $form_id         = absint( $form['id'] );
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor  = $this->is_form_editor();

        $logic_event = ! $is_form_editor && ! $is_entry_detail ? $this->get_conditional_logic_event( 'keyup' ) : '';
        $id          = (int) $this->id;
        $field_id    = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

        $value        = esc_attr( $value );
        
//        if (!($is_form_editor || $is_entry_detail) && empty($value)) {
//            
//        }
        $size         = $this->size;
        $class_suffix = $is_entry_detail ? '_admin' : '';
        $class        = $size . $class_suffix;

        $max_length = is_numeric( $this->maxLength ) ? "maxlength='{$this->maxLength}'" : '';

        $tabindex              = $this->get_tabindex();
        $disabled_text         = $is_form_editor ? 'disabled="disabled"' : '';
        $placeholder_attribute = $this->get_field_placeholder_attribute();
        $required_attribute    = $this->isRequired ? 'aria-required="true"' : '';
        $invalid_attribute     = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
        $input = '<span class="affwp-ajax-search-wrap">'
                .   "<input class='agent-name affwp-agent-search {$class}' type='text' "
                .   " name='input_{$id}' id='{$field_id}' value='{$value}' "
                .   "{$max_length} {$tabindex} {$logic_event} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text} "
                . " />";

        return sprintf( "<div class='ginput_container ginput_container_text'>%s</div>", $input );
    }

    /**
     * Returns what settings /customizations are allowed on the editor.
     * @return array
     */
    public function get_form_editor_field_settings() {
        return array(
//            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'label_setting',
            'label_placement_setting',
//            'admin_label_setting',
            'size_setting',
            'rules_setting',
//            'visibility_setting',
//            'duplicate_setting',
            'default_value_setting',
            'placeholder_setting',
            'description_setting',
//            'phone_format_setting',
            'css_class_setting',
        );
    }
    
    /**
     * Verifies that the agent slug is valid and exists in the system.
     * @param string $value
     * @param array $form
     */
    public function validate( $value, $form ) {
        parent::validate($value, $form);
        if ($this->failed_validation) {
            return;
        }
        
        $agent_id = \AffiliateLTP\admin\Agent_Custom_Slug::get_agent_id_for_slug( $value );
        if ( empty( $agent_id ) ) {
            error_log("validate(): could not find agent with slug " . $value);
            $error_message = sprintf( __( "No agent could be found for provided agent code %s", 'affiliate-ltp' ), $value );
            $this->failed_validation = true;
            if ( empty( $this->errorMessage ) ) {
                $this->validation_message = $error_message;
            }
        }
    }
    
    /**
     * Just before the value is saved off we save off the slug and setup any
     * hooks needed to process the slug by the affiliatewp and other plugins
     * to deal with the agent id.
     * @param string $value
     * @param array $form
     * @param string $input_name
     * @param type $lead_id
     * @param type $lead
     * @return string The value to save to the database.
     */
    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead) {
        $value = parent::get_value_save_entry($value, $form, $input_name, $lead_id, $lead);
        return $value;
    }
    
    /**
     * Returns the agent id to be used for tracking the referral on the form.
     * @param int $affiliate_id
     * @param string $login
     * @return int
     */
    public function return_agent_id_from_slug($affiliate_id, $login = '') {
        $agent_id = \AffiliateLTP\admin\Agent_Custom_Slug::get_agent_id_for_slug( $this->slug_value );
        if (empty($agent_id)) {
            error_log("Failed to find agent_id after successful validation for slug " . $this->slug_value
                    . " .Returning passed in affiliate_id of " . $affiliate_id);
            return $affiliate_id;
        }
        return $agent_id;
    }
}