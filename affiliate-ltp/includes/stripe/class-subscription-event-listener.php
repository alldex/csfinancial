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
        $this->logger->debug("Subscription_Event_Listener::register_hooks_and_actions() called");
        add_filter( 'gform_stripe_webhook', 'handle_stripe_event', 10, 2 );
    }
    
    public function handle_stripe_event( $action, $event ) {
        $this->logger->info("Subscription_Event_Listener::handle_stripe_event() called");
        
        if (!empty($action['type'])) {
            $type = $action['type'];
            if ($type === 'refund_payment'
                    || $type === 'fail_subscription_payment'
                    || $type === 'cancel_subscription')
            {
                $message = "handle_stripe_event(): action => " . var_export($action, true)
                . "\n event: " . var_export($event, true) . "\n";
                $this->logger->error($message);
            }
        }
        
//        'invoice.payment_failed', ''customer.subscription.deleted', ''charge.refunded'
        // $action['entry_id'] = $entry_id;
        // $action['type']     = 'refund_payment';
        // amount??
    }

}
