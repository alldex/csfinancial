<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\stripe;

use Psr\Log\LoggerInterface;

/**
 * Listens to the paid, canceled, and failed subscription events
 * and notifies the admin for non-paid events.
 *
 * @author snielson
 */
class Subscription_Event_Listener implements \AffiliateLTP\I_Register_Hooks_And_Actions {
    
    /**
     * Used for logging messages
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    public function register_hooks_and_actions() {
        add_filter( 'gform_stripe_webhook', 'handle_stripe_event', 10, 2 );
    }
    
    public function handle_stripe_event( $action, $event ) {
        
        $message = "handle_stripe_event(): action => " . var_export($action, true)
                . "\n event: " . var_export($event, true) . "\n";
        $this->logger->error($message);
    }

}
