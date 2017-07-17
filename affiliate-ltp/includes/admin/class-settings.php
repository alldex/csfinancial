<?php
namespace AffiliateLTP\admin;

use AffiliateLTP\I_Register_Hooks_And_Actions;
/**
 * Description of class-settings
 *
 * @author snielson
 */
class Settings implements I_Register_Hooks_And_Actions {
    
        public function __construct() {}
        
        public function register_hooks_and_actions() {
            
		add_filter( 'affwp_settings_tabs', array( $this, 'settings_tab' ) );
		add_filter( 'affwp_settings', array( $this, 'settings' ), 10, 1 );
                
                // make it so we register the table settings
                add_action( 'admin_init', array( $this, 'add_table_settings' ), 20);
                
                add_filter( 'affwp_settings_ltp_sanitize', array( $this, 'sanitize_progress_items' ) );
        }
	
	/**
	 * Register the MLM Settings Tab
	 *
	 * @since 1.0
	 */
	public function settings_tab( $tabs ) {
		$tabs['ltp'] = __( 'Company', 'affiliate-ltp' );
                $tabs['ltp-stripe'] = __( 'Stripe', 'affiliate-ltp' );
		return $tabs;
	}
	
	/**
	 * Register MLM Settings
	 *
	 * @since 1.0
	 */
	public function settings( $settings = array() ) {
            
            $ranks = get_ranks();
            $rank_options = array();
            foreach ($ranks as $rank) {
                $rank_options[$rank['id']] = $rank['name'];
            }

		$ltp_settings = array(	
			'ltp' => apply_filters( 'affwp_settings_ltp',
				array(
					'affwp_mlm_general_header' => array(
						'name' => '<strong>' . __( 'General Settings', 'affiliate-ltp' ) . '</strong>',
						'type' => 'header',
					),
					'affwp_ltp_company_agent_id' => array(
						'name' => __( 'Company Agent', 'affiliate-ltp' ),
						'desc' => '<p class="description">' . __( 'Enter an Agent ID that represents the company user.' ) . '</p>',
						'type' => 'number',
						'size' => 'small',
						'std' => ''
					),
					'affwp_ltp_company_rate' => array(
						'name' => '<strong>' . __( 'Company Percentage Rate', 'affiliate-ltp' ) . '</strong>',
						'desc' => '<p class="description">' . __( 'Enter the Company Percentage Rate that will be taken off every commission ie 5, 10, 20.' ) . '</p>',
                                                'type' => 'number',
						'size' => 'small',
                                                'step' => '0.01',
						'std' => ''
					),
                                    'affwp_ltp_generational_override_1_rate' => array(
						'name' => '<strong>' . __( '1st Generation Override Rate', 'affiliate-ltp' ) . '</strong>',
						'desc' => '<p class="description">' . __( 'Enter the 1st Generation Override Percentage Rate ie 4, 9, 17.' ) . '</p>',
                                                'type' => 'number',
						'size' => 'small',
                                                'step' => '0.01',
						'std' => ''
					),
                                    'affwp_ltp_generational_override_2_rate' => array(
						'name' => '<strong>' . __( '2nd Generation Override Rate', 'affiliate-ltp' ) . '</strong>',
						'desc' => '<p class="description">' . __( 'Enter the 2nd Generation Override Percentage Rate ie 4, 9, 17.' ) . '</p>',
                                                'type' => 'number',
						'size' => 'small',
                                                'step' => '0.01',
						'std' => ''
					),
                                    'affwp_ltp_generational_override_3_rate' => array(
						'name' => '<strong>' . __( '3rd Generation Override Rate', 'affiliate-ltp' ) . '</strong>',
						'desc' => '<p class="description">' . __( 'Enter the 3rd Generation Override Percentage Rate ie 4, 9, 17.' ) . '</p>',
                                                'type' => 'number',
						'size' => 'small',
                                                'step' => '0.01',
						'std' => ''
					),
                                    'affwp_ltp_partner_rank_id' => array(
						'name' => '<strong>' . __( 'Partner Rank', 'affiliate-ltp' ) . '</strong>',
						'desc' => '<p class="description">' . __( 'Select the rank for a partner' ) . '</p>',
                                                'type' => 'select',
                                                'options' => $rank_options,
						'size' => 'small',
						'std' => ''
					),
                                    'affwp_ltp_trainer_rank_id' => array(
						'name' => '<strong>' . __( 'Trainer Rank', 'affiliate-ltp' ) . '</strong>',
						'desc' => '<p class="description">' . __( 'Select the rank for a trainer' ) . '</p>',
                                                'type' => 'select',
                                                'options' => $rank_options,
						'size' => 'small',
						'std' => ''
					),
                                    'affwp_ltp_minimum_default_payout_amount' => array(
						'name' => '<strong>' . __( 'Minimum Default Payout Amount', 'affiliate-ltp' ) . '</strong>',
						'desc' => '<p class="description">' . __( 'Enter the default value for the minimum payout amount an agent must earn before they can receive their commissions.' ) . '</p>',
                                                'type' => 'number',
						'size' => 'small',
                                                'step' => '0.01',
						'std' => ''
					),
                                    
				)
			)
                        ,'ltp-stripe' => apply_filters( 'affwp_settings_ltp_stripe',
                            array(
                                    'ltp_stripe_general_header' => array(
                                            'name' => '<strong>' . __( 'Errors & Ommissions Stripe Account Settings', 'affiliate-ltp' ) . '</strong>',
                                            'type' => 'header',
                                    )
                                    ,'affwp_ltp_stripe_errors_and_ommissions_mode' => array(
                                            'name' => __( 'E&O Test API Mode', 'affiliate-ltp' ),
                                            'desc' => '<p class="description">' . __( 'Set the E&O account Stripe API in test or live mode.' ) . '</p>',
                                            'type' => 'radio',
                                            'std' => 'test',
                                            'options' => ['test' => __('Test', 'affiliate-ltp'),'live' => __('Live', 'affiliate-ltp')]
                                    )
                                    ,'affwp_ltp_stripe_errors_and_ommissions_test_public_key' => array(
                                            'name' => __( 'E&O Test Public Key', 'affiliate-ltp' ),
                                            'desc' => '<p class="description">' . __( 'Enter the stripe api test public key for the Errors & Ommissions account.' ) . '</p>',
                                            'type' => 'text'
                                    )
                                    ,'affwp_ltp_stripe_errors_and_ommissions_test_secret_key' => array(
                                            'name' => __( 'E&O Test Secret Key', 'affiliate-ltp' ),
                                            'desc' => '<p class="description">' . __( 'Enter the stripe api test secret key for the Errors & Ommissions account.' ) . '</p>',
                                            'type' => 'text'
                                    )
                                    ,'affwp_ltp_stripe_errors_and_ommissions_live_public_key' => array(
                                            'name' => __( 'E&O Live Public Key', 'affiliate-ltp' ),
                                            'desc' => '<p class="description">' . __( 'Enter the stripe api live public key for the Errors & Ommissions account.' ) . '</p>',
                                            'type' => 'text'
                                    )
                                    ,'affwp_ltp_stripe_errors_and_ommissions_live_secret_key' => array(
                                            'name' => __( 'E&O Live Secret Key', 'affiliate-ltp' ),
                                            'desc' => '<p class="description">' . __( 'Enter the stripe api live secret key for the Errors & Ommissions account.' ) . '</p>',
                                            'type' => 'text'
                                    )
                                )
                        )
		);
                
                
		$settings = array_merge( $settings, $ltp_settings );
		
		return $settings;
	}
        
        public function add_table_settings( ) {
            // deal with settings for the checklist of items.
            add_settings_field(
                    'affwp_settings[affwp_ltp_progress_items]'
                    ,'<strong>' . __( 'Progress Items', 'affiliate-ltp' ) . '</strong>'
                    ,array($this, 'progress_item_table')
                    ,'affwp_settings_ltp'
                    ,'affwp_settings_ltp'
            );
        }
        
        public function progress_item_table() {
//            $items = [
//                0 => [
//                    "id" => 1
//                    ,"name" => "Checklist Item 1"
//                ]
//                ,1 => [
//                    "id" => 2
//                    ,"name" => "Checklist Item 2"
//                ]
//            ];
            $progress_items = $this->get_progress_items();
            if (empty($progress_items)) {
                $progress_items = [];
            }
            ?>
            <script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.affwp_ltp_remove_item').on('click', function(e) {
				e.preventDefault();
				$(this).parent().parent().remove();
			});

			$('#affwp-ltp-new-item').on('click', function(e) {

				e.preventDefault();

				var row = $('#affiliate-ltp-progress-items tbody tr:first');

				clone = row.clone();

				var count = $('#affiliate-ltp-progress-items tbody tr').length - 1;

				clone.find( 'td input[type="text"], td select' ).val( '' );
				clone.find( 'input, select' ).each(function() {
					var name = $( this ).attr( 'name' );

					name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');

					$( this ).attr( 'name', name ).attr( 'id', name );
				});
                                clone.removeClass('hidden'); // so it shows.
				clone.insertAfter( '#affiliate-ltp-progress-items tbody tr:last' );

			});
		});
		</script>
            
            <table id='affiliate-ltp-progress-items' class="form-table wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <td>ID</td>
                        <td>Name</td>
                        <td>Actions</td>
                    </tr>
                </thead>
                <tbody>
                    <tr class='hidden'>
                        <td>
                            <input name="affwp_settings[affwp_ltp_progress_items][99999][id]" class="small-text" type="text" value=""  />
                        </td>
                        <td>
                            <input name="affwp_settings[affwp_ltp_progress_items][99999][name]" class="text" type="text" value=""  />
                        </td>
                        <td>
                            <input name="affwp_settings[affwp_ltp_progress_items][99999][delete]" class='affwp_ltp_remove_item' type='button' value='Delete' />
                        </td>
                    </tr>
                    <?php if ( !empty($progress_items) ) : ?>
                    <?php foreach ($progress_items as $key => $item) : ?>
                    <tr>
                        <td>
                            <input name="affwp_settings[affwp_ltp_progress_items][<?php echo $key; ?>][id]" class="small-text" type="text" value="<?php echo esc_attr( $item['id'] ); ?>"  />
                        </td>
                        <td>
                            <input name="affwp_settings[affwp_ltp_progress_items][<?php echo $key; ?>][name]" class="text" type="text" value="<?php echo esc_attr( $item['name'] ); ?>"  />
                        </td>
                        <td>
                            <input name="affwp_settings[affwp_ltp_progress_items][<?php echo $key; ?>][delete]" class='affwp_ltp_remove_item' type='button' value='Delete' />
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <input type='button' value='Add Progress Item' id='affwp-ltp-new-item' />
            <?php
        }
        
        /**
	 * Get ALL Ranks
	 *
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	public function get_progress_items() {
		$progress_items = affiliate_wp()->settings->get( 'affwp_ltp_progress_items', array() );
		return apply_filters( 'affwp_ltp_progress_items', array_values( $progress_items ) );
	}
        
        public function sanitize_progress_items( $input ) {

		if( ! empty( $input['affwp_ltp_progress_items'] ) ) {

			if( ! is_array( $input['affwp_ltp_progress_items'] ) ) {
				$input['affwp_ltp_progress_items'] = array();
			}
                        
                        $max_id = 0;
                        
			foreach( $input['affwp_ltp_progress_items'] as $key => $item ) {

				// Require the Name, Type, and Mode fields		To DO - Add Error Message "You must enter a Rank Name, Type, and Mode"
				if( empty( $item['name'] ) ) {

					unset( $input['affwp_ltp_progress_items'][ $key ] );
				
				} else {

					$input['affwp_ltp_progress_items'][ $key ]['id'] = absint( $item['id'] );
                                        $max_id = max([$input['affwp_ltp_progress_items'][ $key ]['id'], $max_id]);
					$input['affwp_ltp_progress_items'][ $key ]['name'] = sanitize_text_field( $item['name'] );

				}

			}
                        
                        // go through and add a new id here.
                        foreach ($input['affwp_ltp_progress_items'] as $key => $item) {
                            if ($input['affwp_ltp_progress_items'][ $key ]['id'] === 0) {
                                $input['affwp_ltp_progress_items'][ $key ]['id'] = ++$max_id;
                            }
                        }

		}
                
		return $input;
	}
}