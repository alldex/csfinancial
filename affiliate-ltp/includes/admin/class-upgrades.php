<?php
namespace AffiliateLTP\admin;

use AffiliateLTP\Plugin;
use AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB;
use AffiliateLTP\Progress_Item_DB;
use AffiliateLTP\Commission_Request_DB;

/**
 * Handles database upgrades of the plugin.
 *
 * @author snielson
 */
class Upgrades implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    /*
     * Whether an upgrade occurred or not.
     */
    private $upgraded = false;
    
    /**
     *
     * @var Affiliate_WP_Referral_Meta_DB 
     */
    private $referral_meta;
    
    /**
     *
     * @var Progress_Item_DB 
     */
    private $item_db;
    /**
     *
     * @var Commission_Request_DB 
     */
    private $commission_request_db;
    
    public function __construct(Affiliate_WP_Referral_Meta_DB $referral_meta
            ,  Progress_Item_DB $item_db
            ,  Commission_Request_DB $commission_request_db) {
        $this->referral_meta = $referral_meta;
        $this->item_db = $item_db;
        $this->commission_request_db = $commission_request_db;
    }
    
    
    public function register_hooks_and_actions() {
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
        
        if ( version_compare( $version, "0.3.0", '<')) {
            $this->v0_3_0_upgrades();
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
        $this->referral_meta->create_table();
    }
    
    public function v0_2_0_upgrades() {
        // TODO: stephen I don't like this being public... is there a better
        // way to do this?
        $this->item_db->create_table();
    }
    
    public function v0_3_0_upgrades() {
        // TODO: stephen I don't like this being public... is there a better
        // way to do this?
        $this->commission_request_db->create_table();
        $this->upgraded = true;
    }

}