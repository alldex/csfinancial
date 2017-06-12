<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;

use \GFForms;
use \GFAddOn;
use \GF_Fields;
use AffiliateLTP\admin\GravityForms\Agent_Slug_Field;
use AffiliateLTP\admin\GravityForms\Agent_Register;
use AffiliateLTP\admin\GravityForms\Stripe_Errors_Ommissions;
use AffiliateLTP\Plugin;

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
                GF_Fields::register(new Agent_Slug_Field());
            }
            
            new Agent_Register(); // instantiate it once.
            
            // new feature flag... so we can turn this off if things go wrong.
            if (Plugin::STRIPE_EO_HANDLING_ENABLED) {
                new Stripe_Errors_Ommissions(Plugin::instance()->get_settings_dal());
            }
        }
    }
}
