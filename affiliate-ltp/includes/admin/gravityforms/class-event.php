<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;

/**
 * Handles all of the gravity form settings to use a separate stripe account
 * for any forms that are set as an Errors & Ommissions form.
 *
 * @author snielson
 */
class Event {
    
    const DEBUG = true;
    const FORM_SETTING_NAME = 'affwp_ltp_event';
    
    
    public function __construct() {
        add_filter( 'gform_form_settings', array($this, 'add_form_setting'), 10, 2 );
        add_filter( 'gform_pre_form_settings_save', array($this, 'save_form_setting' ) );
    }
    
    private function log($message) {
        if (self::DEBUG) {
            error_log($message);
        }
    }
    
    public function save_form_setting( $form ) {
        $form[self::FORM_SETTING_NAME] = rgpost( self::FORM_SETTING_NAME );

        return $form;
    }
    
    public function add_form_setting( $settings, $form ) {
        
	$checked = rgar( $form, 'affwp_ltp_event' );
        $field = '<input type="checkbox" id="affwp-ltp-event" name="' 
                . self::FORM_SETTING_NAME . '" value="1" ' . checked( 1, $checked, false )  . ' />';
        $field .= '<label for="affwp-ltp-event">' . __( 'Is this an event?', "affiliate-ltp") . '</label>';

	$settings['Form Options'][self::FORM_SETTING_NAME] = '
	    <tr>
	        <th>AffiliateLTP Event</th>
	        <td>' . $field . '</td>

	    </tr>';

	return $settings;
    }
}
