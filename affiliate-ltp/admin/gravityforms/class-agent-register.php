<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;

use AffiliateWP_Affiliate_Forms_For_Gravity_Forms;

/**
 * 
 */
class Agent_Register {

    /**
     * Keeps track of the entries that a user is self-registering for.
     * This helps in differentiating between registrations for sub-agents or
     * for themselves.
     * @var array
     */
    private $self_registered_entry_ids;

    /**
     * Since the user hookup can fail during the registration process we need
     * to keep track of that.
     * @var array
     */
    private $entry_creation_error_ids;

    public function __construct() {
        $this->self_registered_entry_ids = [];
        $this->entry_creation_error_ids = [];
        add_action('plugins_loaded', array($this, 'remove_affiliate_gravity_form_hooks'), 200);

        add_action('gform_entry_created', array($this, 'add_agent'), 10, 2);

//        add_filter('gform_form_settings', array($this, 'add_gravity_form_agent_settings'), 10, 2);
//        add_filter('gform_pre_form_settings_save', 'gravity_form_agent_settings_save');

        add_filter('gform_confirmation', array($this, 'update_confirmation_by_registration_status'), 10, 4);

        add_filter('gform_field_validation', array($this, 'form_field_validation'), 10, 4);
    }

    public function remove_affiliate_gravity_form_hooks() {
        if (class_exists('AffiliateWP_Affiliate_Forms_For_Gravity_Forms')) {
            remove_action('gform_entry_created', array(AffiliateWP_Affiliate_Forms_For_Gravity_Forms::instance(), 'add_affiliate'));

            remove_action('gform_pre_render_' . affwp_afgf_get_registration_form_id(), 'affwp_afgf_form_remove_fields');

            remove_filter('gform_field_validation', 'affwp_afgf_gform_field_validation', 10, 4);
        }
    }

    /**
     * Add agent account
     *
     * @since 1.0
     */
    public function add_agent($entry, $form) {

        if ($this->is_agent_registration_form($form)) {

            // register user
            $this->register_user($entry, $form);
        }
    }

    public function update_confirmation_by_registration_status($confirmation, $form, $entry, $ajax) {
        // if it's the agent form
        // and the user is adding a different agent
        // then change the confirmation to be an agent added message
        // otherwise return whatever the current confirmation is.

        if ($this->is_agent_registration_form($form)) {
            if ($this->is_error_entry($entry)) {
                $confirmation = __("A system error occurred and your data could not be processed.  Please hit the back button and try again or contact Customer Support", 'affiliate-ltp');
            } else if (!$this->is_self_registered_entry($entry)) {
                $confirmation = __("Agent was successfully created <a href='?tab=signup' onclick='window.location.reload();'>Create another agent</a>", 'affiliate-ltp');
            }
        }
        return $confirmation;
    }

    public function is_error_entry($entry) {
        if (empty($entry['id'])) {
            return true;
        } else if (!empty($this->entry_creation_error_ids[$entry['id']])) {
            return true;
        }
        return false;
    }

    public function is_self_registered_entry($entry) {
        if (empty($entry['id'])) {
            return false;
        }

        return isset($this->self_registered_entry_ids[$entry['id']]);
    }

    /**
     * Register the agent / user.  This is a modification from the
     * AffiliateWP_Affiliate_Forms_For_Gravity_Forms->register_user function.
     * This differs by always creating a user (since registered users are
     * creating multiple new users.
     * @see AffiliateWP_Affiliate_Forms_For_Gravity_Forms->register_user
     */
    public function register_user($entry, $form) {

        error_log("calling register_user");

        $email = isset($entry[$this->get_form_field_id($form, 'email')]) ? $entry[$this->get_form_field_id($form, 'email')] : '';

        // email is always required for logged out users
        if (!is_user_logged_in() && !$email) {
            error_log("email not found and user not logged in.  Some kind of validation error occurred");
            return;
        }

        $password = $this->get_form_field_value($form, $entry, 'password');
        $username = $this->get_form_field_value($form, $entry, 'username');
        $website = $this->get_form_field_value($form, $entry, 'website');
        $payment_email = $this->get_form_field_value($form, $entry, 'payment_email');
        $promotion_method = $this->get_form_field_value($form, $entry, 'promotion_method');
        $website_url = $this->get_form_field_value($form, $entry, 'website');

        if (!$username) {
            $username = $email;
        }

        $name_ids = $this->form_get_name_field_ids($form);
        $first_name = '';
        $last_name = '';

        if ($name_ids) {

            // dual first name/last name field
            $name_ids = array_filter($this->form_get_name_field_ids($form));

            if (count($name_ids) > 2) {

                // extended
                $first_name = isset($entry[(string) $name_ids[1]]) ? $entry[(string) $name_ids[1]] : '';
                $last_name = isset($entry[(string) $name_ids[3]]) ? $entry[(string) $name_ids[3]] : '';
            } else if (count($name_ids) == 2) {

                // normal
                $first_name = isset($entry[(string) $name_ids[0]]) ? $entry[(string) $name_ids[0]] : '';
                $last_name = isset($entry[(string) $name_ids[1]]) ? $entry[(string) $name_ids[1]] : '';
            } else {

                // simple
                $first_name = isset($entry[$this->get_form_field_id($form, 'name')]) ? $entry[$this->get_form_field_id($form, 'name')] : '';
            }
        }

        // AffiliateWP will show the user as "user deleted" unless a display name is given
        if ($first_name) {

            if ($last_name) {
                $display_name = $first_name . ' ' . $last_name;
            } else {
                $display_name = $first_name;
            }
        } else {
            $display_name = $username;
        }

        $status = affiliate_wp()->settings->get('require_approval') ? 'pending' : 'active';


        // use password fields if present, otherwise randomly generate one
        $password = $password ? $password : wp_generate_password(12, false);

        $args = apply_filters('affiliatewp_afgf_insert_user', array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $display_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'entry_id' => $entry['id']
                ), $username, $email, $password, $display_name, $first_name, $last_name, $entry['id']);

        $user_id = wp_insert_user($args);

        // can't do anything at this point so we will return. 
        if (is_wp_error($user_id)) {
            $this->mark_entry_error($entry, "Failed to insert wordpress user. Error is: "
                    . $user_id->get_error_message()
                    . ". User arguments were " . var_export($args, true));
            return;
        }

        if ($promotion_method) {
            update_user_meta($user_id, 'affwp_promotion_method', $promotion_method);
        }

        if ($website_url) {
            wp_update_user(array('ID' => $user_id, 'user_url' => $website_url));
        }

        // add affiliate
        $affiliate_args = array(
            'status' => $status,
            'user_id' => $user_id,
            'payment_email' => $payment_email
        );
        $success = affwp_add_affiliate($affiliate_args);

        if (!$success) {
            $this->mark_entry_error($entry, "Failed to create agent wordpress user using the following arguments: "
                    . var_xport($affiliate_args));
            return;
        }

        // if the user is not logged in they are registering for themselves.
        if (!is_user_logged_in()) {
            $this->add_self_register_entry($entry);
            if (!$this->log_user_in($user_id, $username)) {
                $this->mark_entry_error($entry, "Failed to login user with user id $user_id and username $username");
            }
        }

        // Retrieve affiliate ID. Resolves issues with caching on some hosts, such as GoDaddy
        $affiliate_id = affwp_get_affiliate_id($user_id);

        // store entry ID in affiliate meta so we can retrieve it later
        affwp_update_affiliate_meta($affiliate_id, 'gravity_forms_entry_id', $entry['id']);

        do_action('affwp_register_user', $affiliate_id, $status, $args);
    }

    private function mark_entry_error($entry, $message) {
        $this->entry_creation_error_ids[$entry['id']] = $message;
        error_log($message);
    }

    private function add_self_register_entry($entry) {
        $this->self_registered_entry_ids[$entry['id']] = true;
    }

    /**
     * Register the form-specific settings
     *
     * @since       1.0
     * @return      void
     */
    function add_gravity_form_agent_settings($settings, $form) {

        if (!affwp_afgf_integration_enabled()) {
            return $settings;
        }

        if ($this->is_agent_registration_form($form)) {
            $checked = true;
        } else {
            $checked = false;
        }

        $field = '<input type="checkbox" id="affwp_affiliateltp_agent_registration" name="affwp_affiliateltp_agent_registration" value="1" ' . checked(1, $checked, false) . ' />';
        $field .= '<label for="affwp_affiliateltp_agent_registration">' . __('Use as a agent registration form', "affiliate-ltp") . '</label>';

        $settings['Form Options']['affwp_gravity_forms_registration'] = '
	    <tr>
	        <th>' . __("Agent Registration", "affiliate-ltp") . '</th>
	        <td>' . $field . '</td>

	    </tr>';

        return $settings;
    }

    /**
     * Save form settings
     *
     * @since 1.0
     *
     * @param array $form Form data.
     * @return array (Maybe) modified form data to save.
     */
    function gravity_form_agent_settings_save($form) {

        $form['affwp_affiliateltp_agent_registration'] = rgpost('affwp_affiliateltp_agent_registration');

        // I believe I only need to store the registration form on the actual form.
//
//	// Store the form ID in AffWP settings.
//	$form_id = $form['affwp_gravity_forms_registration'] ? absint( $form['id'] ) : 0;
//	affiliate_wp()->settings->set( array( 'affwp_gravity_forms_registration_form' => $form_id ), true );

        return $form;
    }

    function is_agent_registration_form($form) {
        $form_id = affwp_afgf_get_registration_form_id();
        return ($form != null && $form_id === $form['id']);
//        if (isset( $form['affwp_affiliateltp_agent_registration'] ) 
//                && $form['affwp_affiliateltp_agent_registration'] === true ) {
//            return true;
//        }
//        return false;
    }

    /**
     * Get field value by entry ID and field type
     *
     * @since 1.0
     */
    function get_form_field_value($form, $entry, $field_type = '') {

        if (!( $form || $entry || $field_type )) {
            error_log("get_form_field_value called without providing form, entry_id, or field_type");
            return;
        }

        switch ($field_type) {

            case 'product':
                $ids = affwp_afgf_get_product_field_ids();
                $product = array();

                if ($ids) {
                    foreach ($ids as $id) {
                        $product[] = $entry[$id];
                    }
                }

                $value = implode(', ', array_filter($product));

                break;

            default:
                $value = isset($entry[$this->get_form_field_id($form, $field_type)]) ? $entry[$this->get_form_field_id($form, $field_type)] : '';
                break;
        }


        if ($value) {
            return $value;
        }

        return false;
    }

    function get_form_field_id($form, $field_type = '') {

        if (!($form || $field_type )) {
            return;
        }

        // get form fields
        $fields = $form['fields'];

        if ($fields) {

            foreach ($fields as $field) {


                if (isset($field['type']) && $field_type == $field['type']) {

                    $field_id = $field['id'];

                    break;
                }
            }
        }

        if (!empty($field_id)) {
            return $field_id;
        }

        return false;
    }

    /**
     * Get name field ID
     *
     * @since 1.0
     */
    function form_get_name_field_ids($form) {
        // get form fields
        $fields = $form['fields'];

        $name = array();

        if ($fields) {
            foreach ($fields as $field) {

                if (isset($field['type']) && $field['type'] == 'name') {

                    $name[] = isset($field['inputs'][0]['id']) ? $field['inputs'][0]['id'] : '';
                    $name[] = isset($field['inputs'][1]['id']) ? $field['inputs'][1]['id'] : '';
                    $name[] = isset($field['inputs'][2]['id']) ? $field['inputs'][2]['id'] : '';
                    $name[] = isset($field['inputs'][3]['id']) ? $field['inputs'][3]['id'] : '';

                    break;
                }
            }
        }

        if (!empty($name)) {
            return $name;
        }

        return false;
    }

    /**
     * Log the user in
     *
     * @since 1.0
     */
    private function log_user_in($user_id = 0, $user_login = '', $remember = false) {

        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        wp_set_current_user($user_id, $user_login);
        wp_set_auth_cookie($user_id, $remember);

        do_action('wp_login', $user_login, $user);
        return true;
    }

    /**
     * Field validation
     *
     * @since 1.0
     */
    function form_field_validation($result, $value, $form, $field) {

        $form_id = affwp_afgf_get_registration_form_id();

        // only validate affiliate registration form
        if ($form['id'] !== $form_id) {
            return $result;
        }

        // email field is always required
        if ('email' == $field['type']) {

            // user is not already logged in
            if (rgblank($value)) {
                $result['is_valid'] = false;
                $result['message'] = empty($result['errorMessage']) ? __('You must enter an email address.', 'gravityforms') : $result['errorMessage'];
            }

            if (!rgblank($value)) {
                // email already in use

                if ($field['emailConfirmEnabled']) {
                    // email confirmation so check first value of array
                    if (email_exists($value[0])) {
                        $result['is_valid'] = false;
                        $result['message'] = empty($result['errorMessage']) ? __('This email address is already in use.', 'gravityforms') : $result['errorMessage'];
                    }
                } else {
                    if (email_exists($value)) {
                        $result['is_valid'] = false;
                        $result['message'] = empty($result['errorMessage']) ? __('This email address is already in use.', 'gravityforms') : $result['errorMessage'];
                    }
                }
            }
        }

        // valid payment email as an email fields
        if ('payment_email' == $field['type']) {

            if (!rgblank($value) && !GFCommon::is_valid_email($value)) {
                $result['is_valid'] = false;
                $result['message'] = empty($result['errorMessage']) ? __('Please enter a valid email address.', 'gravityforms') : $result['errorMessage'];
            }
        }

        // username
        if ('username' == $field['type']) {
            if (username_exists($value)) {
                $result['is_valid'] = false;
                $result['message'] = empty($result['errorMessage']) ? __('This username is already in use.', 'gravityforms') : $result['errorMessage'];
            }
        }

        return $result;
    }

}
