<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;
use GFCommon;

/**
 * Description of class-gravity-forms
 *
 * @author snielson
 */
class Gravity_Forms {

    public function __construct() {
        add_filter('gform_add_field_buttons', array($this, 'add_fields'));
        add_filter( 'gform_field_type_title' , array($this, 'field_type_title' ), 10, 2 );
        
        add_action( 'gform_field_input' , array($this, 'field_input'), 10, 5 );
    }
    
    public function field_input($input, $field, $value, $lead_id, $form_id) {
        
        error_log($field["type"]);
        
        switch ( $field["type"] ) {
            case 'referral_url': {
                return $this->get_field_input_agent_slug($input, $field, $value, $lead_id, $form_id);
            }
            break;
        }
        return $input;
    }
    
    private function get_field_input_agent_slug($input, $field, $value, $lead_id, $form_id) {
        $max_chars = "";

        $tabindex   = GFCommon::get_tabindex();

        $size = rgar( $field, "size" );
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
             Testing display
            <input name="input_<?php echo $field["id"]; ?>" 
                   id="<?php echo $field["id"]; ?>" type="text" 
                   value="<?php echo esc_html( $value ); ?>" 
                   class="<?php echo $class; ?> <?php echo $css; ?>" 
                   <?php echo $max_length; ?> <?php echo $tabindex; ?> 
                    <?php echo $html5_attributes; ?>     
                    <?php echo $disabled_text; ?>/>
          </div>

        <?php

        return ob_get_clean();
    }
    
    public function field_type_title($title, $type ) {
        switch ( $type ) {
            case 'referral_url': {
                    $title = __( 'Agent Slug' , 'affiliate-ltp' );
                    break;
            }
	}
        
        return $title;
    }

    public function add_fields($field_groups) {

        // fields
        $fields = array(
            // username
            array(
                'class' => 'button',
                'value' => __('Agent Slug', ''),
                'onclick' => "StartAddField( 'referral_url' );"
            )
        );

        if (affwp_afgf_integration_enabled()) {
            // add custom AffiliateWP field group
            $field_groups[] = array(
                'name' => 'affiliate_ltp_fields',
                'label' => __('AffiliateLTP Fields', 'affiliate-ltp'),
                'fields' => $fields
            );
        }

        return $field_groups;
    }
    
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


}
