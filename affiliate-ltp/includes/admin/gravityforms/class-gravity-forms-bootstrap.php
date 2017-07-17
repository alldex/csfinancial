<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;
use GFAddOn;
use AffiliateLTP\admin\GravityForms\AffiliateLTP_Gravity_Forms_Add_On;

// sets the version number for the gravity forms piece.
define( 'GF_LIFETESTPREP_ADDON_VERSION', '1.0' );



/**
 * Hooks into the gform_loaded and sets everything up to extend the Gravity Forms.
 *
 * @author snielson
 */
class Gravity_Forms_Bootstrap implements \AffiliateLTP\I_Register_Hooks_And_Actions {

    public function __construct() {}
    
    /**
     * Using the addon framework register to the form.
     * @return type
     */
    public function load() {
         if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
             error_log("addon framework not found");
            return;
        }

        GFAddOn::register( AffiliateLTP_Gravity_Forms_Add_On::class );
    }

    /**
     * Hook into the gravity form events.
     */
    public function register_hooks_and_actions() {
        add_action( 'gform_loaded', array( $this, 'load' ), 5 );
    }

}
