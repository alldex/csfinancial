<?php

/*
        Plugin Name: Life Test Prep Affiliate-WP
        Plugin URI: https://www.lifetestprep.com/
        Version: 0.2.0
        Description: Shortcodes, customization, classes, and assessment functionality.
        Author: stephen@nielson.org
        Author URI: http://stephen.nielson.org
        License: All Rights Reserved
*/

// include the autoloader
require_once 'vendor/autoload.php';

require_once 'class-plugin.php';

AffiliateLTP\Plugin::instance();
