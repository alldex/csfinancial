<?php

/**
 * Description of class-shortcodes
 *
 * @author snielson
 */
class AffiliateLTPShortcodes {
    public function __construct() {
        add_shortcode( 'ltp_affiliate_area', array( $this, 'ltpAffiliateArea' ) );
    }
    
    public function ltpAffiliateArea( $atts, $content = null ) {
        // See https://github.com/AffiliateWP/AffiliateWP/issues/867
        
        if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
                return;
        }
        $direct_affiliate_id = affiliate_wp()->tracking->get_affiliate_id();
        if (empty($direct_affiliate_id)) {
            // try the fallback as the first time visit hasn't had the javascript
            // run to track the cookie
            $direct_affiliate_id = affiliate_wp()->tracking->get_fallback_affiliate_id();
        }
        
        if (!is_user_logged_in() && empty($direct_affiliate_id)) {
            ob_start();
            affiliate_wp()->templates->get_template_part( 'login' );
            return ob_get_clean();
        }
        else {
            return do_shortcode("[affiliate_area]");
        }
    }
}
new AffiliateLTPShortcodes();