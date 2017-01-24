<?php

use AffiliateLTP\Plugin;

/**
 * Description of class-upgrades
 *
 * @author snielson
 */
class AffiliateLTPUpgrades {
    /*
     * Whether an upgrade occurred or not.
     */
    private $upgraded = false;
    public function __construct() {
        add_action( 'admin_init', array( $this, 'init' ), -9999 );
    }
    
    public function init() {
        $version = get_option( 'affwp_ltp_version' );
        if ( empty( $version ) ) {
                $version = '0.0.1'; // last version that didn't have the version option set
        }
        
        if ( version_compare( $version, AffiliateLTP::AFFILIATEWP_LTP_VERSION, '<' ) ) {
                $this->v1_upgrades();
        }
        
        // If upgrades have occurred
        if ( $this->upgraded ) {
                update_option( 'affwp_ltp_version_upgraded_from', $version );
                update_option( 'affwp_ltp_version', AffiliateLTP::AFFILIATEWP_LTP_VERSION );
        }
    }
    
    public function v1_upgrades() {
        // TODO: stephen I don't like this being public... is there a better
        // way to do this?
        Plugin::instance()->referralMeta->create_table();
        $this->upgraded = true;
    }
    
}
new AffiliateLTPUpgrades();