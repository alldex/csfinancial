<?php
namespace AffiliateLTP\ProfileBuilder;

use AffiliateLTP\mailchimp\MailChimp_Service;
use Psr\Log\LoggerInterface;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
/**
 * Handles extensions to functionality with the profile-builder plugin.
 *
 * @author snielson
 */
class Profile_Builder_Extensions {

    /**
     *
     * @var MailChimp_Service
     */
    private $service;
    
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(LoggerInterface $logger, MailChimp_Service $mailchimp) {
        $this->service = $mailchimp;
        $this->logger = $logger;
        // we tie in to where we check to see if we should send an email
        // this feels a bit hacky, but I can't find a better hook than this.
        add_filter('send_email_change_email', array($this, 'update_email_address'), 10, 3);
        $this->logger->info("in profile builder constructor");
    }
    
    public function update_email_address($should_change_email, $user, $userdata) {
        // verify again that we need to change it.
        $this->logger->info("in update_email_address");
        
        try {
            if ( isset( $userdata['user_email'] ) && $user['user_email'] !== $userdata['user_email'] ) {
                $old_email = $user['user_email'];
                $new_email = $userdata['user_email'];
                $this->logger->info("running update_email_address for $old_email to $new_email");
                $this->service->update_subscription($old_email, $new_email);
                $this->logger->info("subscription updated");
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex);
        }
        
        return $should_change_email;
        
    }
}