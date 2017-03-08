<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;

use GFForms;
use GFAddOn;
use GF_Fields;

GFForms::include_addon_framework();

/**
 * Uses the Gravity Forms Addon framework to create the extensions needed for
 * the agent fields we've added to the form.
 *
 * @author snielson
 */
class AffiliateLTP_Gravity_Forms_Add_On extends GFAddOn {

    protected $_version = GF_LIFETESTPREP_ADDON_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'affiliate-ltp';
    protected $_path = 'affiliate-ltp/class-gravity-forms-referral-url.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms Affiliate-LTP Add-On';
    protected $_short_title = 'Affiliate-LTP Add-On';
    private static $_instance = null;

    /**
     * Returns an instance of the gravity forms.
     * @return AffiliateLTP_Gravity_Forms_Add_On
     */
    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Register the agent fields to be used by Gravity Forms.
     */
    public function pre_init() {
        parent::pre_init();

        if ($this->is_gravityforms_supported()) {
            if (class_exists('GF_Field')) {
                require_once( 'class-agent-slug-field.php' );
                GF_Fields::register(new Agent_Slug_Field());
            }
            
            require_once( 'class-agent-register.php');
            new Agent_Register(); // instantiate it once.
        }
    }
}
