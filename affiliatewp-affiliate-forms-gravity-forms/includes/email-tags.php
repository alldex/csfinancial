<?php
/**
 * Email tags
 *
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Set up a tag if "Create AffiliateWP email tag" has been enabled
 *
 * @since 1.0
 */
function affwp_afgf_add_email_tag( $email_tags, $this ) {

	$fields = affwp_afgf_get_email_tag_field_ids();

	$new_email_tags = array();

	if ( $fields ) {
		foreach ( $fields as $key => $field ) {

			$new_email_tags[$key]['tag']         = $field['id'] ? 'gf_field_' . $field['id'] : '';
			$new_email_tags[$key]['description'] = $field['label'];
			$new_email_tags[$key]['function']    = 'affwp_afgf_email_tags';

		}
	}

	return array_merge( $email_tags, $new_email_tags );

}
add_filter( 'affwp_email_tags', 'affwp_afgf_add_email_tag', 10, 2 );

/**
 * Retrieve the value for each tag
 *
 * @since 1.0
 */
function affwp_afgf_email_tags( $affiliate_id = 0, $referral, $tag ) {

	// retrieve entry_id from affiliate meta table
	$entry_id = affwp_afgf_affiliate_entry_id( $affiliate_id );

	if ( $entry_id ) {

		$entry = GFAPI::get_entry( $entry_id );
		$tag   = explode( '_', $tag );
		$lead  = GFFormsModel::get_lead( $entry_id );

		// product fields need to be formatted differently
		// splits gf_field_1 and looks at the number
		if ( $tag[2] == affwp_afgf_get_field_id( 'product' ) ) {
			$value = affwp_afgf_get_field_value( $entry_id, 'product' );
		} else {
			$value = $lead[$tag[2]];
		}

		return $value;

	}

}