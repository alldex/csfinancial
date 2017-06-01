<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;
use Stripe;
use AffiliateLTP\admin\Settings_DAL;
use GFStripe;

/**
 * Handles all of the gravity form settings to use a separate stripe account
 * for any forms that are set as an Errors & Ommissions form.
 *
 * @author snielson
 */
class Stripe_Errors_Ommissions {
    
    const DEBUG = true;
    const GRAVITY_FORMS_STRIPE_SLUG = 'gravityformsstripe';
    /**
     * Whether the current php request should use the errors and ommissions account,
     * since we don't have access to the form while api keys are being set we have to
     * watch for the form previous to the Stripe api keys and set this variable.
     * @var boolean
     */
    private $should_use_eo_account;
    
    /**
     * Array of the Strip settings we are overriding when it's an error and ommissions form.
     * @var array
     */
    private $overriden_settings;
    
    /**
     * service to retrieve the E&O account credentials we need.
     * @var Settings_DAL
     */
    private $settings_dal;
    
    public function __construct(Settings_DAL $settings_dal) {
        $this->settings_dal = $settings_dal;
        $this->should_use_eo_account = false;
        
        add_filter( 'gform_form_settings', array($this, 'add_eo_form_setting'), 10, 2 );
        add_filter( 'gform_pre_form_settings_save', array($this, 'save_eo_form_setting' ) );
        
        if (self::DEBUG) {
            add_action( 'gform_stripe_post_include_api', array($this, 'log_api_keys'));
        }
        
        add_filter( 'gform_form_post_get_meta', array($this, 'set_eo_account_flag'));

        add_filter( 'parse_request', array($this, 'check_eo_form_callback'));
        
    }
    
    private function log($message) {
        if (self::DEBUG) {
            error_log($message);
        }
    }
    
    public function log_api_keys() {
        $secret_key = GFStripe::get_instance()->get_secret_api_key();
        $this->log("secret key after api include is: " . $secret_key);
    }
    
    /**
     * We need the stripe communications to be with the Errors and Ommissions
     * account if the GFStripe plugin says we are in the right plugin callback
     * and that the url parameter of account-type is set to be eo
     */
    public function check_eo_form_callback() {
        
        if (GFStripe::get_instance()->is_callback_valid()) {
            $this->log("stripe callback checking if eo form");
            if (strtolower(rgget('account-type')) === 'eo') {
                $this->log("is valid eo form, overriding stripe keys");
                $this->should_use_eo_account = true;
                $this->override_stripe_api_keys();
            }
        }
    }
    
    public function set_eo_account_flag( $form ) {
        $this->log("gform_form_post_get_meta checking errors and ommissions flag");
        // TODO: stephen what if someone is loading a bunch of forms... this could
        // have some bad side effects...?
        if (!empty($form['affwp_ltp_stripe_errors_and_ommissions'])) {
            $this->log("gform_form_post_get_meta flag set, overriding account");
            $this->should_use_eo_account = true;
            $this->override_stripe_api_keys();
        }
        
        return $form;
    }
    public function return_overriden_settings($skip, $option_name) {
        $this->log("returning overridden settings");
        return $this->overriden_settings;
    }
    public function override_stripe_api_keys() { //$settings, $option_name  ) {
        
        if (empty($this->overriden_settings)) {
            $settings = GFStripe::get_instance()->get_plugin_settings();
            $this->log("secret key is currently: " . GFStripe::get_instance()->get_secret_api_key());
            $this->log("publishable key is currently: " . GFStripe::get_instance()->get_publishable_api_key());
            $keys = $this->settings_dal->get_errors_and_ommissions_keys();
            $this->log("eo keys: " . var_export($keys, true));
            $settings['api_mode'] = $this->settings_dal->get_errors_and_ommissions_mode();
            $settings['test_secret_key'] = $keys['test_secret_key'];
            $settings['test_publishable_key'] = $keys['test_publishable_key'];
            $settings['live_secret_key'] = $keys['live_secret_key'];
            $settings['live_publishable_key'] = $keys['live_publishable_key'];
            $this->overriden_settings = $settings;
            $this->log("adding settings filter");
            // add a filter so we can return the overriden settings from here on out.
            add_filter('pre_option_gravityformsaddon_gravityformsstripe_settings', array($this, 'return_overriden_settings'), 10, 2 );
        }
    }
    public function save_eo_form_setting( $form ) {
        $form['affwp_ltp_stripe_errors_and_ommissions'] = rgpost( 'affwp_ltp_stripe_errors_and_ommissions' );

        return $form;
    }
    
    public function add_eo_form_setting( $settings, $form ) {
        
	$checked = rgar( $form, 'affwp_ltp_stripe_errors_and_ommissions' );
        $field = '<input type="checkbox" id="affwp-ltp-stripe-errors-and-ommissions" name="affwp_ltp_stripe_errors_and_ommissions" value="1" ' . checked( 1, $checked, false )  . ' />';
        $field .= '<label for="affwp-ltp-stripe-errors-and-ommissions">' . __( 'Process any payments with Errors and Ommissions Account?', "affiliate-ltp") . '</label>';

	$settings['Form Options']['affwp_ltp_stripe_errors_and_ommissions'] = '
	    <tr>
	        <th>AffiliateLTP E&O Settings</th>
	        <td>' . $field . '</td>

	    </tr>';

	return $settings;
    }
}
