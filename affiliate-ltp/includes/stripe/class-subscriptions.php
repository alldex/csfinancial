<?php
namespace AffiliateLTP\stripe;

use Stripe\Stripe;
use AffiliateLTP\admin\Settings_DAL;
use AffiliateLTP\Template_Loader;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
/**
 * Handles the display and retrieval of the stripe subscriptions
 *
 * @author snielson
 */
class Subscriptions {
    /**
     * Subscription settings
     * @var Settings_DAL
     */
    private $settings_dal;
    
    /**
     * Loads and displays template files.
     * @var Template_Loader
     */
    private $template_loader;
    
    
    
    public function __construct(Settings_DAL $settings_dal, Template_Loader $loader) {
        $this->settings_dal = $settings_dal;
        add_shortcode("affwp-ltp-eo-subscriptions", array($this, 'display_subscriptions'));
        $this->template_loader = $loader;
    }
    
    public function handleAdminSubMenuPage() {
        // for now we display the subscriptions.
        $this->display_subscriptions();
    }
    
    public function display_subscriptions() {
        $table = new \WP_List_Table();
        if (!class_exists('Stripe\Stripe')) {
            echo "Error stripe is not setup properly to retrieve subscriptions";
            error_log("Missing class Stripe to display subscriptions");
            return;
        }
        $mode = $this->settings_dal->get_errors_and_ommissions_mode();
        $keys = $this->settings_dal->get_errors_and_ommissions_keys();
        if ($mode == 'test') {
            Stripe::setApiKey($keys['test_secret_key']);
        }
        else {
            Stripe::setApiKey($keys['live_secret_key']);
        }
        try {
            $response = \Stripe\Subscription::all(array('expand' => array('data.customer')));
//            echo '<pre>';
//            var_dump($response->data[0]);
//            echo "</pre>";
//            exit;
            $subscriptions = $response->autoPagingIterator();
            $subscription_table = new Subscriptions_List_Table([], $subscriptions);
            $subscription_table->prepare_items();
            $file = $this->template_loader->get_template_part("admin", "subscriptions-list", false);
            include $file;
        }
        catch (\Exception $ex) {
            echo "Error could retrieve the subscriptions using the stripe api. Please check the logs for more details.";
            error_log($ex);
        }
    }
}

class Subscriptions_List_Table extends \WP_List_Table {
    private $data;
    
    public function __construct($args, $data) {
        parent::__construct($args);
        $this->data = $data;
    }
    
    function get_columns(){
        $columns = array(
          'id' => __('Subscription ID', 'affiliate-ltp'),
          'customer'    => __('Agent Email', 'affiliate-ltp'),
          'plan' => __('Plan', 'affiliate-ltp'),
          'status'      => __('Status', 'affiliate-ltp'),
        );
    return $columns;
  }

  function prepare_items() {
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = array();
    $this->_column_headers = array($columns, $hidden, $sortable);
    $this->items = $this->data;
  }
  
  function column_default( $item, $column_name ) {
      if ($column_name === 'customer') {
          return $item->customer->email;
      }
      
      if ($column_name === 'plan') {
          return $item->plan->name;
      }
      
      if ($column_name === 'status') {
          if ($item->status !== 'active') {
              return "<span class='error'>" . $item->status . "</span>";
          }
      }
      
      return $item[$column_name];
  }
    
}
