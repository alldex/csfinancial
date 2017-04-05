<?php

/*
        Plugin Name: Life Test Prep Affiliate-WP
        Plugin URI: https://www.lifetestprep.com/
        Version: 0.2.1
        Description: Shortcodes, customization, classes, and assessment functionality.
        Author: stephen@nielson.org
        Author URI: http://stephen.nielson.org
        License: All Rights Reserved
*/

// include the autoloader
require_once 'vendor/autoload.php';
require_once 'autoloader.php';

$plugin_dir_path = plugin_dir_path( __FILE__ );
$parent_plugin_dir_path = dirname($plugin_dir_path);
$loader = new \AffiliateLTP\Psr4AutoloaderClass();
$loader->register();
$loader->addNamespace("AffiliateLTP\\",  $plugin_dir_path . "includes/");
$loader->addNamespace("AffiliateLTP\\", $plugin_dir_path . "tests/");

//$loader->addNamespace("AffWP\\", $parent_plugin_dir_path 
//        . DIRECTORY_SEPARATOR . "affiliate-wp" . DIRECTORY_SEPARATOR . "includes");
//
//$loader->addNamespace("AffWP\\Admin\\", $parent_plugin_dir_path 
//        . DIRECTORY_SEPARATOR . "affiliate-wp" . DIRECTORY_SEPARATOR 
//        . "includes"
//        . DIRECTORY_SEPARATOR . "admin");
//
//// some admin classes are in the abstracts folder...
//$loader->addNamespace("AffWP\\Admin\\", $parent_plugin_dir_path 
//        . DIRECTORY_SEPARATOR . "affiliate-wp" . DIRECTORY_SEPARATOR 
//        . "includes"
//        . DIRECTORY_SEPARATOR . "abstracts");


AffiliateLTP\Plugin::instance();
