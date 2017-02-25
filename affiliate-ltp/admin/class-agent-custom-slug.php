<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin;

/**
 * Description of class-agent-custom-slug
 *
 * @author snielson
 */
class Agent_Custom_Slug {
    // TODO: stephen this is static but we should change it to be instance
    // for testing purposes later.
    public static function get_agent_id_for_slug( $slug ) {
        global $wpdb;
        if ( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
            // Allows a single affiliate meta table for the whole network
            $table_name = 'affiliate_wp_affiliatemeta';
        } else {
            $table_name = $wpdb->prefix . 'affiliate_wp_affiliatemeta';
        }
        
        $meta_key = 'custom_slug';
        
        $agent_id = null;
        // slug cannot be all numeric
        if ( is_numeric( $slug ) ) {
            $agent_id = null;
        }
        else {
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT affiliate_id FROM $table_name where meta_key = '%s' and meta_value = %s",
                $meta_key, $slug ) );

            if ( ( $wpdb->num_rows ) > 0 ) {
                // TODO: stephen there should not be any duplicates but we need to add in code to check for that here.
               foreach ( $results as $result ) {
                   $agent_id = $result->affiliate_id;
                   break;
               }
            }
        }
        return $agent_id;
    }
}
