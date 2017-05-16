<?php
/**
 * Fields
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add AffiliateWP field types to Gravity Forms
 *
 * @since 1.0
 */
function affwp_afgf_field_settings( $field_groups ) {

  // fields
  $fields = array(

  	// username
    array(
      'class'   => 'button',
      'value'   => __( 'Username', 'affiliatewp-afgf' ),
      'onclick' => "StartAddField( 'username' );"
    ),
    // payment email
    array(
      'class'   => 'button',
      'value'   => __( 'Payment Email', 'affiliatewp-afgf' ),
      'onclick' => "StartAddField( 'payment_email' );"
    ),
    // promotion method
    array(
      'class'   => 'button',
      'value'   => __( 'Promo Method', 'affiliatewp-afgf' ),
      'onclick' => "StartAddField( 'promotion_method' );"
    )

  );

  if ( affwp_afgf_integration_enabled() ) {
      // add custom AffiliateWP field group
      $field_groups[] = array(
        'name'   => 'affwp_fields',
        'label'  => __( 'AffiliateWP Fields', 'affiliatewp-afgf' ),
        'fields' => $fields
      );
  }

  return $field_groups;

}
add_filter( 'gform_add_field_buttons', 'affwp_afgf_field_settings' );

/**
 * Change the title above the form field in the admin
 *
 * @since 1.0
 */
function affwp_afgf_field_type_title( $title, $type ) {

	switch ( $type ) {

		case 'username':
			$title = __( 'Username' , 'affiliatewp-afgf' );
			break;

		case 'payment_email':
			$title = __( 'Payment Email' , 'affiliatewp-afgf' );
			break;

		case 'promotion_method':
			$title = __( 'Promotion Method' , 'affiliatewp-afgf' );
			break;

	}

	return $title;
}
add_filter( 'gform_field_type_title' , 'affwp_afgf_field_type_title', 10, 2 );


// Adds the input area to the external side
function affwp_afgf_gform_field_input( $input, $field, $value, $lead_id, $form_id ) {

	switch ( $field["type"] ) {

		case 'username':

			 $max_chars = "";

			 $tabindex   = GFCommon::get_tabindex();

			 $size = rgar( $field, "size" );
			 $disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
			 $class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
			 $class = $size . $class_suffix;

			 $css = isset( $field['cssClass'] ) ? $field['cssClass'] : '';

			 // max length
			 if ( is_numeric( rgget( "maxLength", $field ) ) ) {
			    $max_length = "maxlength='{$field["maxLength"]}'";
			 } else {
			   $max_length = '';
			 }

			 // html5 attributes
			 $html5_attributes = '';

			 // disabled text for admin
			 $disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";

			 ob_start();
			 ?>

			  <div class="ginput_container">
			     <input name="input_<?php echo $field["id"]; ?>" id="<?php echo $field["id"]; ?>" type="text" value="<?php echo esc_html( $value ); ?>" class="<?php echo $class; ?> <?php echo $css; ?>" <?php echo $max_length; ?> <?php echo $tabindex; ?> <?php echo $html5_attributes; ?> <?php echo $disabled_text; ?>/>
			   </div>

			 <?php

			 return ob_get_clean();

			break;

		case 'payment_email':

			$tabindex   = GFCommon::get_tabindex();

			$size = rgar( $field, "size" );
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
			$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
			$class = $size . $class_suffix;

			$css = isset( $field['cssClass'] ) ? $field['cssClass'] : '';

			// max length
			if ( is_numeric( rgget( "maxLength", $field ) ) ) {
				$max_length = "maxlength='{$field["maxLength"]}'";
			} else {
				$max_length = '';
			}

			// html5 attributes
			$html5_attributes = '';

			$html_input_type = RGFormsModel::is_html5_enabled() ? "email" : "text";

			// disabled text for admin
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";

			ob_start();
			?>

			<div class="ginput_container">
				<input name="input_<?php echo $field["id"]; ?>" id="<?php echo $field["id"]; ?>" type="<?php echo $html_input_type; ?>" value="<?php echo esc_html( $value ); ?>" class="<?php echo $class; ?> <?php echo $css; ?>" <?php echo $max_length; ?> <?php echo $tabindex; ?> <?php echo $html5_attributes; ?> <?php echo $disabled_text; ?>/>
			</div>

			 <?php

			return ob_get_clean();

			break;

		case 'promotion_method':

			$tabindex   = GFCommon::get_tabindex();

			$size = rgar( $field, "size" );
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
			$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
			$class = $size . $class_suffix;

			$css = isset( $field['cssClass'] ) ? $field['cssClass'] : '';

			// max length
			if ( is_numeric( rgget( "maxLength", $field ) ) ) {
				$max_length = "maxlength='{$field["maxLength"]}'";
			} else {
				$max_length = '';
			}

			// disabled text for admin
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";

			ob_start();
			?>

			<div class="ginput_container">
				<textarea name="input_<?php echo $field["id"]; ?>" id="<?php echo $field["id"]; ?>" class="textarea <?php echo $field["type"]; ?> <?php echo $class; ?> <?php echo $css; ?>" <?php echo $tabindex; ?> <?php echo $max_length; ?> rows="10" cols="50" <?php echo $disabled_text; ?>><?php echo esc_html( $value ); ?></textarea>
			</div>

			 <?php

			return ob_get_clean();

			break;
	}

	return $input;

}
add_action( 'gform_field_input' , 'affwp_afgf_gform_field_input', 10, 5 );


/**
 * Configure field settings
 *
 */
function affwp_afgf_gform_editor_js() {

?>
  <script type='text/javascript'>

  jQuery(document).ready(function($) {

    fieldSettings["username"] = ".conditional_logic_field_setting, .prepopulate_field_setting, .error_message_setting, .label_setting, .admin_label_setting, .size_setting, .maxlen_setting, .rules_setting, .description_setting, .css_class_setting";

    fieldSettings["payment_email"] = ".conditional_logic_field_setting, .prepopulate_field_setting, .error_message_setting, .label_setting, .admin_label_setting, .size_setting, .maxlen_setting, .rules_setting, .description_setting, .css_class_setting";

    fieldSettings["promotion_method"] = ".conditional_logic_field_setting, .prepopulate_field_setting, .error_message_setting, .label_setting, .admin_label_setting, .maxlen_setting, .size_setting, .rules_setting, .visibility_setting, .default_value_textarea_setting, .description_setting, .css_class_setting";

    // add email tag checkbox

	fieldSettings["text"] += ", .affwp_email_tag_setting";
    fieldSettings["textarea"] += ", .affwp_email_tag_setting";
    fieldSettings["phone"] += ", .affwp_email_tag_setting";
    fieldSettings["number"] += ", .affwp_email_tag_setting";
    fieldSettings["date"] += ", .affwp_email_tag_setting";
    fieldSettings["time"] += ", .affwp_email_tag_setting";
    fieldSettings["select"] += ", .affwp_email_tag_setting";
    fieldSettings["multiselect"] += ", .affwp_email_tag_setting";
    fieldSettings["checkbox"] += ", .affwp_email_tag_setting";
    fieldSettings["radio"] += ", .affwp_email_tag_setting";
    fieldSettings["address"] += ", .affwp_email_tag_setting";
   	fieldSettings["list"] += ", .affwp_email_tag_setting";
	fieldSettings["fileupload"] += ", .affwp_email_tag_setting";

	// post fields
	fieldSettings["post_title"] += ", .affwp_email_tag_setting";
	fieldSettings["post_content"] += ", .affwp_email_tag_setting";
	fieldSettings["post_excerpt"] += ", .affwp_email_tag_setting";
	fieldSettings["post_tags"] += ", .affwp_email_tag_setting";
	fieldSettings["post_category"] += ", .affwp_email_tag_setting";
	fieldSettings["post_image"] += ", .affwp_email_tag_setting";
	fieldSettings["post_custom_field"] += ", .affwp_email_tag_setting";


   	// product fields
    fieldSettings["product"] += ", .affwp_email_tag_setting";
    fieldSettings["singleproduct"] += ", .affwp_email_tag_setting";
 	fieldSettings["price"] += ", .affwp_email_tag_setting";
 	fieldSettings["shipping"] += ", .affwp_email_tag_setting";
    fieldSettings["singleshipping"] += ", .affwp_email_tag_setting";
    fieldSettings["option"] += ", .affwp_email_tag_setting";
    fieldSettings["quantity"] += ", .affwp_email_tag_setting";
    fieldSettings["donation"] += ", .affwp_email_tag_setting";
    fieldSettings["total"] += ", .affwp_email_tag_setting";

    $(document).bind("gform_load_field_settings", function( event, field, form ) {

      $( "#field_username_value" ).val( field["username"] );

      $( "#field_payment_email_value" ).val( field["payment_email"] );

      $( "#field_promotion_method_value" ).val( field["promotion_method"] );

		// email tag
		jQuery("#affwp_email_tag").attr("checked", field["affwp_email_tag"] == true);

    });

  });

  </script>
<?php
}
add_action( "gform_editor_js", "affwp_afgf_gform_editor_js" );


/**
 * Field validation
 *
 * @since 1.0
 */
function affwp_afgf_gform_field_validation( $result, $value, $form, $field ) {

	$form_id = affwp_afgf_get_registration_form_id();

	// only validate affiliate registration form
	if ( $form['id'] !== $form_id ) {
		return $result;
	}

	// email field is always required
	if ( 'email' == $field['type'] ) {

		if ( is_user_logged_in() ) {

			// Skip validation by setting it to true when user is logged in. This field is only required when the user is logged out
			$result['is_valid'] = true;

		} else {
			// user is not already logged in
			if ( rgblank( $value ) ) {
				$result['is_valid'] = false;
				$result['message'] = empty( $result['errorMessage'] ) ? __( 'You must enter an email address.', 'gravityforms' ): $result['errorMessage'];
			}

            if ( ! rgblank( $value ) ) {
                // email already in use

                if ( $field['emailConfirmEnabled'] ) {
                    // email confirmation so check first value of array
                    if ( email_exists( $value[0] ) ) {
        				$result['is_valid'] = false;
        				$result['message'] = empty( $result['errorMessage'] ) ? __( 'This email address is already in use.', 'gravityforms' ): $result['errorMessage'];
        			}
                } else {
                    if ( email_exists( $value ) ) {
        				$result['is_valid'] = false;
        				$result['message'] = empty( $result['errorMessage'] ) ? __( 'This email address is already in use.', 'gravityforms' ): $result['errorMessage'];
        			}
                }


            }

		}

	}

	// password field
	if ( 'password' == $field['type'] ) {

		// Skip validation by setting it to true when user is logged in. This field is only required when the user is logged out
		if ( is_user_logged_in() ) {
			$result['is_valid'] = true;
		}
	}

	// valid payment email as an email fields
	if ( 'payment_email' == $field['type'] ) {

		if ( ! rgblank( $value ) && ! GFCommon::is_valid_email( $value ) ) {
		    $result['is_valid'] = false;
		    $result['message'] = empty( $result['errorMessage'] ) ? __( 'Please enter a valid email address.', 'gravityforms' ): $result['errorMessage'];
		}

	}

	// username
	if ( 'username' == $field['type'] ) {

		if ( is_user_logged_in() ) {
			// Skip validation by setting it to true when user is logged in. This field is only required when the user is logged out
			$result['is_valid'] = true;

		} elseif ( username_exists( $value ) ) {
			$result['is_valid'] = false;
			$result['message'] = empty( $result['errorMessage'] ) ? __( 'This username is already in use.', 'gravityforms' ): $result['errorMessage'];
		}

	}

	return $result;
}
add_filter( 'gform_field_validation', 'affwp_afgf_gform_field_validation', 10, 4 );

/**
 * Output a message just inside the form tag if the form does not contain an email field
 *
 * @since 1.0
 */
function affwp_afgf_gform_form_tag( $form ) {

	if ( ! affwp_afgf_get_field_id( 'email' ) ) {
		$missing_email = '<div class="validation_error">' . __( 'This form must have an email field.', 'gravityforms' ) . '</div>';

		return $form . $missing_email;
	}

	return $form;

}
add_filter( 'gform_form_tag_' . affwp_afgf_get_registration_form_id() , 'affwp_afgf_gform_form_tag', 10, 1 );

/**
 * Field validation
 *
 * @since 1.0
 */
function affwp_afgf_setting_email_tag( $position, $form_id ) {

	if ( $position == 25 ) {
		?>
		<li class="affwp_email_tag_setting field_setting">
			<input type="checkbox" id="affwp_email_tag" onclick="SetFieldProperty( 'affwp_email_tag', this.checked );" />
			<label for="affwp_email_tag" class="inline">
				<?php _e( 'Create AffiliateWP email tag', 'gravityforms' ); ?>
				<?php gform_tooltip( 'form_field_affwp_email_tag' ) ?>
			</label>
		</li>
		<?php
	}
}
add_action( 'gform_field_standard_settings', 'affwp_afgf_setting_email_tag', 10, 2 );


/**
 * Field validation
 *
 * @since 1.0
 */
function affwp_afgf_setting_email_tag_tooltips( $tooltips ) {

   $tooltips["form_field_affwp_email_tag"] = "<h6>Create Email Tag</h6>Creates an email tag for use in AffiliateWP emails.";

   return $tooltips;

}
add_filter( 'gform_tooltips', 'affwp_afgf_setting_email_tag_tooltips' );

/**
 * Hide registration fields for logged in users
 *
 * @since 1.0
 */
function affwp_afgf_form_remove_fields( $form ) {

	if ( ! is_user_logged_in() ) {
		return $form;
	}

	$fields_to_hide = array(
		affwp_afgf_get_field_id( 'password' ),
		affwp_afgf_get_field_id( 'username' ),
		affwp_afgf_get_field_id( 'email' )
	);

	foreach ( $form['fields'] as $key => $field ) {
		if ( in_array( $field['id'], $fields_to_hide ) ) {
			unset( $form['fields'][$key] );
		}
	}

	return $form;
}
add_filter( 'gform_pre_render_' . affwp_afgf_get_registration_form_id(), 'affwp_afgf_form_remove_fields' );
