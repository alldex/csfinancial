<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;
use GFAddOn;

// sets the version number for the gravity forms piece.
define( 'GF_LIFETESTPREP_ADDON_VERSION', '1.0' );



/**
 * Hooks into the gform_loaded and sets everything up to extend the Gravity Forms.
 *
 * @author snielson
 */
class Gravity_Forms_Bootstrap {

    /**
     * Hook into the gravity form events.
     */
    public function __construct() {
        add_action( 'gform_loaded', array( $this, 'load' ), 5 );
    }
    
    /**
     * Using the addon framework register to the form.
     * @return type
     */
    public function load() {
         if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
             error_log("addon framework not found");
            return;
        }

        require_once( 'class-affiliateltp-gravity-forms-add-on.php' );

        GFAddOn::register( AffiliateLTP_Gravity_Forms_Add_On::class );
    }
}
