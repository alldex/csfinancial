<?php
namespace AffiliateLTP\mailchimp;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
/**
 * Instantiates the GF_MailChimp instances
 *
 * @author snielson
 */
class GFMailChimp_Factory {
    public static function createInstance() {
        return \GFMailChimp::get_instance();
    }
}
