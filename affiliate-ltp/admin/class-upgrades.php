<?php
namespace AffiliateLTP\admin;

use AffiliateLTP\Plugin;

/**
 * Handles database upgrades of the plugin.
 *
 * @author snielson
 */
class Upgrades {
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
        
        if ( version_compare( $version, "0.1.0", '<' ) ) {
            $this->v0_1_0_upgrades();
        }
        
        if ( version_compare( $version, "0.2.0", '<')) {
            $this->v0_2_0_upgrades();
        }
        
        // If upgrades have occurred
        if ( $this->upgraded ) {
                update_option( 'affwp_ltp_version_upgraded_from', $version );
                update_option( 'affwp_ltp_version', Plugin::AFFILIATEWP_LTP_VERSION );
        }
    }
    
    public function v0_1_0_upgrades() {
        // TODO: stephen I don't like this being public... is there a better
        // way to do this?
        Plugin::instance()->referralMeta->create_table();
    }
    
    public function v0_2_0_upgrades() {
        // TODO: stephen I don't like this being public... is there a better
        // way to do this?
        Plugin::instance()->get_progress_items_db()->create_table();
        $this->upgraded = true;
    }
    
}
new Upgrades();