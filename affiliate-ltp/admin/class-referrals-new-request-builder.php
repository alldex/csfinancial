<?php

namespace AffiliateLTP\admin;

require_once "class-referrals-new-request.php";

use AffiliateLTP\CommissionType;
use \Exception;

/**
 * Description of class-referrals-new-request-builder
 *
 * @author snielson
 */
class Referrals_New_Request_Builder {
    
    
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
        if ( !array_key_exists( 'id', $agentData )) {
            throw new Exception("For agent row: $rowNumber missing id ");
        }
        $userId      = absint( $agentData['id'] );

        $agentId = affiliate_wp()->affiliates->get_column_by( 'affiliate_id', 'user_id', $userId );
        $split = abs($agentData['split']);

        if ( ! empty( $agentId ) ) {
            $agent = new Referrals_Agent_Request();
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
     * @return Referrals_New_Request
     * @throws Exception
     */
    public static function build( $requestData ) {
        $request = new Referrals_New_Request();
        if ( empty( $requestData['agents'] )) {
            throw new Exception("No agent information was submitted");
        }
        
        // if there is no other split then we drop all the other agent pieces
        // and force the commission to be 100.
        if (!isset($requestData['split_commission'])) {
            $requestData['agents'] = array(
                self::SINGLE_AGENT_ROW_NUMBER => $requestData['agents'][self::SINGLE_AGENT_ROW_NUMBER]
            );
            // make sure that the split is always 100%
            $requestData['agents'][self::SINGLE_AGENT_ROW_NUMBER]['split'] = 100; 
        }
        
        if (isset($requestData['skip_company_haircut'])) {
            $request->skipCompanyHaircut = true;
        }
        
        if (isset($requestData['company_haircut_all'])) {
            $request->companyHaircutAll = true;
        }
        
        foreach ( $requestData['agents'] as $rowNumber => $agent) {
            $request->agents[] = self::parseAgent($rowNumber, $agent);
        }

        $request->client = self::parseClientArgs($requestData);
        $request->amount = ! empty( $requestData['amount'] ) ? sanitize_text_field( $requestData['amount'] )      : '';
        $request->date = self::parseDate($requestData);
        $request->points = $request->amount;
        
        if (isset($requestData['is_life_commission'])) {
            $request->type = CommissionType::TYPE_LIFE;
            // set the points to be whatever was entered for a life commission
            $request->points = !empty( $requestData['points'] ) ? sanitize_text_field( $requestData['points'] ) : $request->amount;
        }
        else {
            $request->type = CommissionType::TYPE_NON_LIFE;
        }
        
        return $request;
    }
}
