<?php
namespace AffiliateLTP;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
class Template_Loader implements I_Register_Hooks_And_Actions
{
    public function __construct() {}
    
    public function register_hooks_and_actions() {
        add_filter( 'affwp_template_paths', array( $this, 'get_theme_template_paths' ) );
    }
    
     /**
    * Add template folder to hold the organization tab content
    *
    *
    * @return void
    */
   public function get_theme_template_paths( $file_paths ) {
           $file_paths[80] = AFFILIATE_LTP_PLUGIN_DIR . '/templates';

           return $file_paths;
   }
   
   
   
   public function get_template_part( $slug, $name = null, $load = true ) {
       return affiliate_wp()->templates->get_template_part($slug, $name, $load);
   }
}