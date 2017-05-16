<?php
/**
 * Admin
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add fields to review affiliate screen
 *
 * @since 1.0
 */
function affwp_afgf_add_to_review() {

	$affiliate     = affwp_get_affiliate( absint( $_GET['affiliate_id'] ) );
	$affiliate_id  = $affiliate->affiliate_id;
	$entry_id      = affwp_afgf_affiliate_entry_id( $affiliate_id );

	$registration_form_id = affwp_afgf_get_registration_form_id();

	if ( $entry_id ) {

		// get form
		$form        = GFAPI::get_form( $registration_form_id );
		$form_fields = $form['fields'];

		$fields = array();

		$entry = GFAPI::get_entry( $entry_id );

		if ( $entry && $form_fields ) {

			foreach ( $form_fields as $key => $field ) {

				if ( in_array( $field['id'], affwp_afgf_excluded_field_ids() ) ) {
					continue;
				}

			?>

				<tr class="form-row">
					<th scope="row">
						<?php echo esc_html( GFCommon::get_label( $field ) ); ?>
					</th>
					<td>
						<?php

						$values = GFFormsModel::get_lead_field_value( $entry, $field );

						if ( is_array( $values ) ) {
							echo implode(', ', array_filter( $values ) );
						} else {
							echo $values;
						}

						?>
					</td>
				</tr>

				<?php

			}
		}

		?>
		<tr>
			<th scope="row"><?php _e( 'View Submission', 'affiliatewp-afgf' ); ?></th>
			<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=gf_entries&view=entry&id=' . $registration_form_id . '&lid=' . $entry_id ) ); ?>">#<?php echo $entry_id; ?></a></td>
		</tr>
		<?php
	}

}
add_action( 'affwp_review_affiliate_end', 'affwp_afgf_add_to_review' );

/**
 * Add submission link to the edit affiliate screen
 *
 * @since 1.0
 */
function affwp_afgf_edit_affiliate( $affiliate ) {

	$affiliate_id = $affiliate->affiliate_id;
	$entry_id      = affwp_afgf_affiliate_entry_id( $affiliate_id );
	$form_id       = affwp_afgf_get_registration_form_id();

	if ( ! $entry_id ) {
		return;
	}
	?>

	<tr>
		<th scope="row"><?php _e( 'View Submission', 'affiliatewp-afgf' ); ?></th>
		<td>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=gf_entries&view=entry&id=' . $form_id . '&lid=' . $entry_id ) ); ?>">#<?php echo $entry_id; ?></a>
		<p class="description"><?php _e( 'This links to the information the affiliate submitted when they registered.', 'affiliatewp-afgf' ); ?></p>
		</td>
	</tr>

	<?php
}
add_action( 'affwp_edit_affiliate_end', 'affwp_afgf_edit_affiliate' );


/**
 * Enable Gravity Forms password field
 *
 * @since       1.0
 * @return      void
 */
add_action( 'gform_enable_password_field', '__return_true' );

/**
 * Register the form-specific settings
 *
 * @since       1.0
 * @return      void
 */
function affwp_afgf_settings( $settings, $form ) {

	if ( ! affwp_afgf_integration_enabled() ) {
		return $settings;
	}

	$checked = rgar( $form, 'affwp_gravity_forms_registration' );
	$form_id = affwp_afgf_get_registration_form_id();

	if ( ! $form_id || $form['id'] === $form_id ) {

		$field = '<input type="checkbox" id="affwp-gravity-forms-registration" name="affwp_gravity_forms_registration" value="1" ' . checked( 1, $checked, false )  . ' />';
		$field .= '<label for="affwp-gravity-forms-registration">' . __( 'Use as affiliate registration form', "gravityforms") . '</label>';

	} else {

		$form_link = esc_url( admin_url( 'admin.php?page=gf_edit_forms&id=' . $form_id ) );
		$field = '<label for="affwp-gravity-forms-registration">' . sprintf( __( 'Form %s is the affiliate registration form.', 'affiliatewp-afnf' ), '<a href="' . $form_link . '">#' . $form_id . '</a>' ) . '</label>';

	}

	$settings['Form Options']['affwp_gravity_forms_registration'] = '
	    <tr>
	        <th>AffiliateWP registration</th>
	        <td>' . $field . '</td>

	    </tr>';

	return $settings;

}
add_filter( 'gform_form_settings', 'affwp_afgf_settings', 10, 2 );

/**
 * Save form settings
 *
 * @since 1.0
 *
 * @param array $form Form data.
 * @return array (Maybe) modified form data to save.
 */
function affwp_afgf_save_settings( $form ) {

    $form['affwp_gravity_forms_registration'] = rgpost( 'affwp_gravity_forms_registration' );

	// Store the form ID in AffWP settings.
	$form_id = $form['affwp_gravity_forms_registration'] ? absint( $form['id'] ) : 0;
	affiliate_wp()->settings->set( array( 'affwp_gravity_forms_registration_form' => $form_id ), true );

    return $form;
}
add_filter( 'gform_pre_form_settings_save', 'affwp_afgf_save_settings' );
