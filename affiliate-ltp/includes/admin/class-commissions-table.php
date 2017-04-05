<?php 
namespace AffiliateLTP\admin;

// we'd use our autoloader but the affiliate-wp doesn't follow any kind
// of convention
// TODO: stephen is there a way to centralize some of this junk?
if (!class_exists('AffWP\Admin\List_Table')) {
    require_once dirname(dirname(dirname(plugin_dir_path(__FILE__)))) 
    . "/affiliate-wp/includes/abstracts/class-affwp-list-table.php";
}

use AffiliateLTP\CommissionType;
use AffiliateLTP\admin\Commission_DAL;
use AffWP\Admin\List_Table;

class Commissions_Table extends List_Table {

    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;

    /**
	 * @param  Commission_DAL $commission_dal The database for commissions
	 */
	public function __construct(Commission_DAL $commission_dal) {
            $this->commission_dal = $commission_dal;
		add_filter( 'affwp_referral_table_columns', array($this, 'add_table_columns' ) );
		add_filter( 'affwp_referral_table_points', array($this, 'column_points'), 10, 2);
		add_filter( 'affwp_referral_table_type', array($this, 'column_type'), 10, 2);
                
                add_filter( 'affwp_referral_row_actions', array($this, 'update_commission_actions'), 10, 2);
	}

	public function add_table_columns($columns) {
		$new = array(
			'cb' => $columns['cb']
			,'amount' => $columns['amount']
			,'affiliate' => $columns['affiliate']
			,'type' => __('Type', 'affiliate-ltp')
			,'reference' => $columns['reference']
			,'description' => $columns['description']
			,'date' => $columns['date']
			,'status' => $columns['status']
			,'points' => __('Points', 'affiliate-ltp')
			,'actions' => $columns['actions']
		);
		return $new;
	}
        
        public function update_commission_actions($row_actions, $commission) {
            
            $request = $this->get_commission_request_for_referral($commission);
            if (!empty($request) && $commission->affiliate_id == $request['writing_agent_id']) {
                $row['chargeback'] = $this->get_row_action_link(
			__( 'Chargeback', 'affiliate-ltp' ),
			array_merge( $base_query_args, array(
				'affwp_action' => 'process_chargeback_commission'
			) ),
			array(
				'nonce' => 'affwp_chargeback_commission_nonce',
				'class' => 'chargeback'
			))
                ;
                return $row_actions;
            }
            
            return [];
        }
        
        public function get_commission_request_for_referral($commission) {
            
            // need to cache out the commission meta
            $request_id = $this->commission_dal->get_commission_request_id_from_commission($commission->referral_id);
            if (!empty($request_id)) {
                return $this->commission_dal->get_commission_request($request_id);
            }
            return null;
        }

	public function column_type($value, $commission) {
		$value = $commission->context;
		if ($value == CommissionType::TYPE_LIFE) {
			return __('Life Insurance', 'affiliate-ltp');
		}
		else {
			return __('Non-Life Insurance', 'affiliate-ltp');
		}
	}

	public function column_points($value, $commission) {
                $value = $this->commission_dal->get_commission_agent_points($commission->referral_id);
        if (empty($value)) {
			return '';
		}
		return $value;
	}
}