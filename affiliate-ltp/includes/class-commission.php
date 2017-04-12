<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP;

/**
 * Represents a commission in the system and provides type safety.
 *
 * @author snielson
 */
class Commission {
    public $commission_id;
    public $agent_id;
    public $status;
    public $amount;
    public $reference;
    public $context;
    public $date;
    public $client;
    public $meta;
    public $description;
    public $payout_id;
    
    public function __construct() {
        $this->status = admin\Commission_Status::UNPAID;
        $this->meta = [];
        $this->client = null;
    }
}
/*
 * "agent_id" => $item->agent->id
            , "description" => $description
            , "amount" => $adjusted_amount
            , "reference" => $request->client['contract_number']
            , "custom" => $custom
            , "context" => $request->type
            , "status" => $this->get_status_for_save($item)
            , "date" => $request->date
            , "client" => $request->client
            , "meta" => [
                "points" => $item->points
                // TODO: stephen agent_rate and agent_real_rate may not be needed anymore...?
                , "agent_rate" => $item->agent->rate
                , "agent_real_rate" => $item->rate
                // TODO: stephen should this be bundled into the referrals_new_request piece??
                , "commission_request_id" => $this->commission_request_id
            ]
        );
 */