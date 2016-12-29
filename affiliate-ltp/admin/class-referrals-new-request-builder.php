<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "class-referrals-new-request.php";
require_once "class-commission-type.php";

/**
 * Description of class-referrals-new-request-builder
 *
 * @author snielson
 */
class AffiliateLTPReferralsNewRequestBuilder {
    
    
    const SINGLE_AGENT_ROW_NUMBER = 0;
    
    private static function parseClientArgs($data) {
         // TODO: stephen group all the client information into a single client array.
        $clientArgs = array (
            'id'      => ! empty( $data['client_id'] ) ? sanitize_text_field( $data['client_id'] ) : null,
            'contract_number' => ! empty( $data['client_contract_number'] ) ? sanitize_text_field( $data['client_contract_number'] ) : null,
            'name'    => ! empty( $data['client_name'] ) ? sanitize_text_field( $data['client_name'] ) : '',
            'street_address' => ! empty( $data['client_street_address'] ) ? sanitize_text_field( $data['client_street_address'] ) : '',
            'city' => ! empty( $data['client_city_address'] ) ? sanitize_text_field( $data['client_city_address'] ) : '',
            'country' => 'USA', // TODO: stephen extract this to a setting or constant.
            'zip' => ! empty( $data['client_zip_address'] ) ? sanitize_text_field( $data['client_zip_address'] ) : '',
            'phone'   => ! empty( $data['client_phone'] ) ? sanitize_text_field( $data['client_phone'] ) : '',
            'email'   => ! empty( $data['client_email'] ) ? sanitize_text_field( $data['client_email'] ) : '',
        );
        return $clientArgs;
    }
    private static function parseAgent($rowNumber, $agentData) {
        if ( !array_key_exists( 'user_id', $agentData )) {
            throw new Exception("For row: $rowNumber missing agent_id ");
        }
        $userId      = absint( $agentData['user_id'] );

        $agentId = affiliate_wp()->affiliates->get_column_by( 'affiliate_id', 'user_id', $userId );
        $split = abs($agentData['agent_split']);

        if ( ! empty( $agentId ) ) {
            $agent = new AffiliateLTPReferralsAgentRequest();
            $agent->split = $split;
            $agent->id = $agentId;
            return $agent;
        } else {
            throw new Exception("affiliate_id could not be found from user_id");
        }
    }
    
    private static function parseDate($requestData) {
	if ( ! empty( $requestData['date'] ) ) {
		return date_i18n( 'Y-m-d H:i:s', strtotime( $requestData['date'] ) );
	}
        // use the current time if no date is provided.
        else {
            return date_i18n( 'Y-m-d H:i:s', time() );
        }
    }
    
    /**
     * Validates and converts the request data into the right format to be used
     * for creating a new referral.
     * @param array $requestData
     * @return \AffiliateLTPReferralsNewRequest
     * @throws Exception
     */
    public static function build($requestData) {
        $request = new AffiliateLTPReferralsNewRequest();
        if ( empty( $requestData['agents'] )) {
            throw new Exception("No agent information was submitted");
        }
        
        // if there is no other split then we drop all the other agent pieces
        // and force the commission to be 100.
        if (!isset($requestData['cb_split_commission'])) {
            $requestData['agents'] = array(
                self::SINGLE_AGENT_ROW_NUMBER => $requestData['agents'][self::SINGLE_AGENT_ROW_NUMBER]
            );
            // make sure that the split is always 100%
            $requestData['agents'][self::SINGLE_AGENT_ROW_NUMBER]['agent_split'] = 100; 
        }
        
        foreach ( $requestData['agents'] as $rowNumber => $agent) {
            $request->agents[] = self::parseAgent($rowNumber, $agent);
        }

        if (isset($requestData['cb_is_life_commission'])) {
            $request->type = AffiliateLTPCommissionType::TYPE_LIFE;
        }
        else {
            $request->type = AffiliateLTPCommissionType::TYPE_NON_LIFE;
        }
        
        $request->client = self::parseClientArgs($requestData);
        $request->amount = ! empty( $requestData['amount'] ) ? sanitize_text_field( $requestData['amount'] )      : '';
        $request->date = self::parseDate($requestData);
        return $request;
    }
}
