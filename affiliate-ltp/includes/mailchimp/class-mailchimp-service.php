<?php
namespace AffiliateLTP\mailchimp;

use \DrewM\MailChimp\MailChimp;
use GFMailChimp;
use Psr\Log\LoggerInterface;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

/**
 * Websites to help with this:
 * @see https://symfony.com/doc/current/service_container/factories.html symfony factories
 * @see https://github.com/drewm/mailchimp-api for the DrewM mailchimp api
 * @see http://jsonpatch.com/ for patch information
 * @see http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/ for detailed explanation of patching
 * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/ for mailchimp patch api doc
 * 
 * Handles the updating of mail chimp email subscriptions.
 * @author snielson
 */
class MailChimp_Service {
    /**
     *
     * @var GFMailChimp
     */
    private $gfMailChimp;
    
    /**
     * Used for writing to the logs
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(LoggerInterface $logger, GFMailChimp $gfMailChimp) {
        $this->logger = $logger;
        $this->gfMailChimp = $gfMailChimp;
    }
    
    public function update_subscription($old_email, $new_email) {
        
        $api_key = $this->gfMailChimp->get_plugin_setting( 'apiKey' );
        $this->logger->info("using api key of $api_key");
        $api = new MailChimp($api_key);
        $subscription_list_ids = $this->get_subscription_list_ids($api);
        foreach ( $subscription_list_ids as $id ) {
            $this->update_email($id, $api, $old_email, $new_email);
        }
    }
    
    
    private function get_subscription_list_ids($api) {
        $list_ids = [];
        $active_feeds = $this->gfMailChimp->get_active_feeds();
        foreach ($active_feeds as $feed) {
            $list_ids[] = $feed['meta']['mailchimpList'];
        }
        return $list_ids;
    }
    
    private function update_email($list_id, MailChimp $api, $old_email, $new_email) {
        $this->logger->debug("Updating list($list_id) email $old_email => $new_email");
        $subscriber_hash = $api->subscriberHash($old_email);
        $this->logger->debug("Printing lists/$list_id/members/$subscriber_hash");
        $member_result = $api->get("lists/$list_id/members/$subscriber_hash");
        if (empty($member_result['status']) || $member_result['status'] == 404) {
            $this->logger->error("MailChimp email subscription update.  Failed to find $old_email in list($list_id) to change to new email $new_email.");
            return;
        }
        
        $this->logger->debug("Patching lists/$list_id/members/$subscriber_hash");
        $result = $api->patch("lists/$list_id/members/$subscriber_hash", [
            'email_address' => $new_email
        ]);
        
        if (empty($result['status']) || $result['status'] != 'subscribed') {
            $this->logger->error("ERROR. Patch lists/$list_id/members/$subscriber_hash of $old_email => $new_email failed!.Result was: " 
                    . var_export($result, true));
        }
    }
    
    private function subscribe_new_email($list_id, $api, $new_email) {
        
        
        $list_id = $feed['meta']['mailchimpList']; // need to figure out where this comes from...
        // need to figure out how to get the list id from the feed
        try {

			// Log the subscriber to be added or updated.
			$this->log_debug( __METHOD__ . '(): Subscriber to be added/updated: ' . print_r( $subscription, true ) );

			// Add or update subscriber.
			$this->api->update_list_member( $list_id, $subscription['email_address'], $subscription );

			// Log that the subscription was added or updated.
			$this->log_debug( __METHOD__ . '(): Subscriber successfully added/updated.' );

		} catch ( Exception $e ) {

			// Log that subscription could not be added or updated.
			$this->add_feed_error( sprintf( esc_html__( 'Unable to add/update subscriber: %s', 'gravityformsmailchimp' ), $e->getMessage() ), $feed, $entry, $form );

			return;

		}
    }
    
    private function unsubscribe_old_email($list_id, $api, $old_email) {
        
        try {

			// Get member info.
            // need to figure out how to get the mail chimp list id from the feed...
            // the email will come from the old email.
            $member = $api->get_list_member( $list_id, $old_email );
            
                        // need to figure out how to unsubscribe this member.

		} catch ( Exception $e ) {

			// If the exception code is not 404, abort feed processing.
			if ( 404 !== $e->getCode() ) {

				// Log that we could not get the member information.
				$this->add_feed_error( sprintf( esc_html__( 'Unable to check if email address is already a member: %s', 'gravityformsmailchimp' ), $e->getMessage() ), $feed, $entry, $form );

				return $entry;

			}

		}
                
    }
}
