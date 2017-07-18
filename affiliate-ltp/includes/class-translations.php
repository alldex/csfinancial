<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

/**
 * Description of class-translations
 *
 * @author snielson
 */
class Translations implements I_Register_Hooks_And_Actions {
    public function register_hooks_and_actions() {
         // come in last here.
        add_filter( 'load_textdomain_mofile', array($this, 'load_ltp_en_mofile'), 50, 2 );
        add_action( 'init', array($this, 'load_ltp_affiliate_ranks_translation' ) );
    }
    
    /**
     * Our plugin can override other plugin language files.
     * @param string $mofile
     * @param string $domain
     * @return string
     */
    public function load_ltp_en_mofile( $mofile, $domain )
    {
        // remove any slashes from the filename so we can't try to include
        // directories.
        $safe_domain = str_replace('/', '_', $domain);
        
        $include_file = AFFILIATE_LTP_PLUGIN_DIR . "/languages/" . $safe_domain . "-en.mo";
        if (file_exists($include_file)) {
            return $include_file;
        }
        return $mofile;
    }
    
    public function load_ltp_affiliate_ranks_translation() {
        load_plugin_textdomain( 'affiliatewp-ranks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
    
    

}
