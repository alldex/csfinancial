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
    
    /**
     * Whether the current php request should use the errors and ommissions account,
     * since we don't have access to the form while api keys are being set we have to
     * watch for the form previous to the Stripe api keys and set this variable.
     * @var boolean
     */
    private $should_use_eo_account;
    
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
        do_action( 'gform_stripe_post_include_api', array($this, 'override_stripe_api_keys'));
        
        add_filter( 'gform_form_post_get_meta', array($this, 'set_eo_account_flag'));
        
        add_filter( 'parse_request', array($this, 'check_eo_form_callback'));
        
    }
    
    /**
     * We need the stripe communications to be with the Errors and Ommissions
     * account if the GFStripe plugin says we are in the right plugin callback
     * and that the url parameter of account-type is set to be eo
     */
    public function check_eo_form_callback() {
        
        if (GFStripe::get_instance()->is_callback_valid()) {
            if (strtolower(rgget('account-type')) === 'eo') {
                $this->should_use_eo_account = true;
            }
        }
    }
    
    public function set_eo_account_flag( $form ) {
        // TODO: stephen what if someone is loading a bunch of forms... this could
        // have some bad side effects...?
        if (!empty($form['affwp_ltp_stripe_errors_and_ommissions'])) {
            $this->should_use_eo_account = true;
        }
    }
    
    public function override_stripe_api_keys() {
        if (!$this->should_use_eo_account) {
            return;
        }
        
        $apiKey = $this->settings_dal->get_errors_and_ommissions_current_secret_api_key();
        if (empty($apiKey)) {
            // we do not want to continue here as the flag should never be set if our api key is null...
            throw new \RuntimeException("The errors and ommissions api key was invalid.  Cannot continue");
        }
        
        // override the current api key with our new one so we can process to the correct account.
        Stripe::setApiKey($apiKey);
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
