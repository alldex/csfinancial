<?php
/**
 * Functions
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Get the Id of the affiliate registration form
 *
 * @since 1.0
 *
 * @return int|false Registration form ID if set, otherwise false.
 */
function affwp_afgf_get_registration_form_id() {

	$registration_form_id = affiliate_wp()->settings->get( 'affwp_gravity_forms_registration_form', 0 );

	if ( 0 !== $registration_form_id ) {
		return $registration_form_id;
	}

	return false;
}

/**
 * Get an affiliate's entry ID from the affiliate meta table
 *
 * @since 1.0
 */
function affwp_afgf_affiliate_entry_id( $affiliate_id = 0 ) {

	if ( ! $affiliate_id ) {
		return;
	}

	$entry_id = affwp_get_affiliate_meta( $affiliate_id, 'gravity_forms_entry_id', true );

	if ( $entry_id ) {
		return $entry_id;
	}

	return false;
}

/**
 * Get an array of email tag IDs
 *
 * @since 1.0
 */
function affwp_afgf_get_email_tag_field_ids() {

	$form_id = affwp_afgf_get_registration_form_id();

	if ( $form_id ) {
		// get form
		$form = GFAPI::get_form( $form_id );

		// get form fields
		$form_fields = $form['fields'];

		$fields = array();

		if ( $form_fields ) {
			foreach ( $form_fields as $id => $field ) {

				if ( isset( $field['affwp_email_tag'] ) ) {
					$fields[$id]['id']    = $field['id'];
					$fields[$id]['label'] = $field['label'];
				}

			}
		}

		if ( $fields ) {
			return $fields;
		}

		return array();

	}

}

/**
 * Get field value by entry ID and field type
 *
 * @since 1.0
 */
function affwp_afgf_get_field_value( $entry_id = '', $field_type = '' ) {

	if ( ! ( $entry_id || $field_type ) ) {
		return;
	}

	$form  = GFAPI::get_form( affwp_afgf_get_registration_form_id() );
	$entry = GFAPI::get_entry( $entry_id );

	switch ( $field_type ) {

		case 'product':
			$ids = affwp_afgf_get_product_field_ids();
			$product = array();

			if ( $ids ) {
				foreach ( $ids as $id ) {
					$product[] = $entry[ $id ];
				}
			}

			$value = implode( ', ', array_filter( $product ) );

			break;

		default:
			$value = isset( $entry[ affwp_afgf_get_field_id( $field_type ) ] ) ? $entry[ affwp_afgf_get_field_id( $field_type ) ] : '';
			break;

	}


	if ( $value ) {
		return $value;
	}

	return false;

}


/**
 * Get field ID by field type
 *
 * @since 1.0
 */
function affwp_afgf_get_field_id( $field_type = '' ) {

	if ( ! $field_type ) {
		return;
	}

	// get registration form ID
	$form_id = affwp_afgf_get_registration_form_id();

	// get form
	$form = GFAPI::get_form( $form_id );

	// get form fields
	$fields = $form['fields'];

	$product_array = array();

	if ( $fields ) {

		foreach ( $fields as $field ) {


			if ( isset( $field['type'] ) && $field_type == $field['type'] ) {

				$field_id = $field['id'];

				break;
			}

		}
	}

	if ( ! empty( $field_id ) ) {
		return $field_id;
	}

	return false;
}


/**
 * Get the product IDs.
 * These are typically 2.1, 2.3, 2.4 etc
 *
 * @since 1.0
 */
function affwp_afgf_get_product_field_ids() {

	// get registration form ID
	$form_id = affwp_afgf_get_registration_form_id();

	// get form
	$form = GFAPI::get_form( $form_id );

	// get form fields
	$fields = $form['fields'];

	$product_array = array();

	if ( $fields ) {

		foreach ( $fields as $field ) {

			if ( isset( $field['type'] ) && 'product' == $field['type'] ) {

				foreach ( $field['inputs'] as $field ) {
					$product_array[] = (string) $field['id'];
				}

				$field_id = $product_array;

				break;
			}


		}
	}

	if ( ! empty( $field_id ) ) {
		return $field_id;
	}

	return false;
}


/**
 * Get name field ID
 *
 * @since 1.0
 */
function affwp_afgf_get_name_field_ids() {
	// get registration form ID
	$form_id = affwp_afgf_get_registration_form_id();

	// get form
	$form = GFAPI::get_form( $form_id );

	// get form fields
	$fields = $form['fields'];

	$name = array();

	if ( $fields ) {
		foreach ( $fields as $field ) {

			if ( isset( $field['type'] ) && $field['type'] == 'name' ) {

				$name[] = isset( $field['inputs'][0]['id'] ) ? $field['inputs'][0]['id'] : '';
				$name[] = isset( $field['inputs'][1]['id'] ) ? $field['inputs'][1]['id'] : '';
				$name[] = isset( $field['inputs'][2]['id'] ) ? $field['inputs'][2]['id'] : '';
				$name[] = isset( $field['inputs'][3]['id'] ) ? $field['inputs'][3]['id'] : '';

				break;
			}

		}
	}

	if ( ! empty( $name ) ) {
		return $name;
	}

	return false;
}

/**
 * Field IDs excluded from email tags, review screen, checkbox option
 *
 * @since 1.0
 */
function affwp_afgf_excluded_field_ids() {

	$excluded_fields = array(
		affwp_afgf_get_field_id( 'name' ),
		affwp_afgf_get_field_id( 'password' ),
		affwp_afgf_get_field_id( 'promotion_method' ),
		affwp_afgf_get_field_id( 'website' ),
		affwp_afgf_get_field_id( 'email' ),
		affwp_afgf_get_field_id( 'username' )
	);

	return $excluded_fields;
}

/**
* Get the email field ID
*
* @since 1.0
*/
function affwp_afgf_get_email_field_id( $form = 0 ) {

	if ( ! $form ) {
		return;
	}

	$email_fields = GFCommon::get_email_fields( $form );

	$field_id = '';

	if ( $email_fields ) {

		foreach ( $email_fields as $email_field ) {

			$field_id = $email_field['id'];

			break;

		}

	}

	if ( $field_id == '' ) {
		return false;
	} else {
		return $field_id;
	}

}

/**
 * Check to see if the Gravity Forms integration is enabled
 *
 * @since 1.0.9
 */
function affwp_afgf_integration_enabled() {

	$integrations = affiliate_wp()->integrations->get_enabled_integrations();

	if ( in_array( 'Gravity Forms', $integrations ) ) {
		return true;
	}

	return false;

}
