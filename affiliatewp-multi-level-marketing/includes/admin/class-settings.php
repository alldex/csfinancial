<?php

class AffiliateWP_MLM_Settings {
	
	public function __construct() {

		add_filter( 'affwp_settings_tabs', array( $this, 'settings_tab' ) );
		add_filter( 'affwp_settings', array( $this, 'settings' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'level_rate_settings' ) );
		add_filter( 'affwp_settings_rates_sanitize', array( $this, 'sanitize_rates' ) );
		
		// Affiliate Admin
		add_action( 'affwp_edit_affiliate_bottom', array( $this, 'edit_affiliate' ), 10, 1 );
		add_action( 'affwp_new_affiliate_end', array( $this, 'add_new_affiliate' ));
		
		// Remove data on uninstall
		// add_filter( 'affwp_settings_misc', array( $this, 'settings_misc' ) );
		
		// Variable rate settings
		add_filter( 'affwp_settings_vrates', array( $this, 'settings_mlm_vrates' ) );
		
		// Per-level rank rate settings
		add_filter( 'affwp_settings_ranks', array( $this, 'settings_mlm_rank_rates' ) );

	}
	
	/**
	 * Misc settings
	 * 
	 * @since 1.0.5
	*/
	public function settings_misc( $settings = array() ) {

		$settings[ 'affwp_mlm_uninstall_on_delete' ] = array(
			'name' => __( 'MLM:<br/> Remove Data on Uninstall?', 'affiliatewp-multi-level-marketing' ),
			'desc' => __( 'Check this box if you would like to remove all MLM data when AffiliateWP MLM is deleted.', 'affiliatewp-multi-level-marketing' ),
			'type' => 'checkbox'
		);

		return $settings;

	}

	/**
	 * Variable Rate settings
	 * 
	 * @since 1.0.6.1
	*/
	public function settings_mlm_vrates( $settings = array() ) {

		$settings[ 'affwp_vr_mlm_referral_rate_type' ] = array(
			'name' => __( 'Indirect Referral Variable Rate Type', 'affiliatewp-variable-rates' ),
			'desc' => '<p class="description">' . __( 'Should referrals made by Sub Affiliates be based on a percentage or flat rate amounts?', 'affiliatewp-variable-rates' ) . '</p>',
				'type' => 'select',
				'options' => array(
					'' => __( 'Site Default', 'affiliate-wp' ),
					'percentage' => __( 'Percentage (%)', 'affiliate-wp' ),
					'flat'       => sprintf( __( 'Flat %s', 'affiliatewp-variable-rates' ), affwp_get_currency() ),
				)
			);

		return $settings;

	}

	/**
	 * Per-Level Rank Rate settings
	 * 
	 * @since 1.1
	*/
	public function settings_mlm_rank_rates( $settings = array() ) {

		$settings[ 'affwp_ranks_mlm_referral_rate_type' ] = array(
			'name' => __( 'Indirect Referral Rank Rate Type', 'affiliatewp-multi-level-marketing' ),
			'desc' => '<p class="description">' . __( 'Should referrals made by Sub Affiliates be based on a percentage or flat rate amounts?', 'affiliatewp-multi-level-marketing' ) . '</p>',
				'type' => 'select',
				'options' => array(
					'' => __( 'Site Default', 'affiliatewp-multi-level-marketing' ),
					'percentage' => __( 'Percentage (%)', 'affiliatewp-multi-level-marketing' ),
					'flat'       => sprintf( __( 'Flat %s', 'affiliatewp-multi-level-marketing' ), affwp_get_currency() ),
				)
			);

		return $settings;

	}

	/**
	 * Register the MLM Settings Tab
	 *
	 * @since 1.0
	 */
	public function settings_tab( $tabs ) {
		$tabs['mlm'] = __( 'MLM', 'affiliatewp-multi-level-marketing' );
		return $tabs;
	}
	
	/**
	 * Register MLM Settings
	 *
	 * @since 1.0
	 */
	public function settings( $settings = array() ) {

		$mlm_settings = array(
			// MLM Settings			
			'mlm' => apply_filters( 'affwp_settings_mlm',
				array(
					'affwp_mlm_general_header' => array(
						'name' => '<strong>' . __( 'General Settings', 'affiliatewp-multi-level-marketing' ) . '</strong>',
						'type' => 'header',
					),
					'affwp_mlm_integrations' => array(
						'name' => __( 'Integrations', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Choose the integrations that should have MLM enabled.', 'affiliatewp-multi-level-marketing' ) . '</p>',
						'type' => 'multicheck',
						'options' => apply_filters( 'affwp_mlm_integrations', array(
							'edd'            => 'Easy Digital Downloads',
							'formidablepro'  => 'Formidable Pro',
							'gravityforms'   => 'Gravity Forms',
							'exchange'       => 'iThemes Exchange',
							'jigoshop'       => 'Jigoshop',
							'marketpress'    => 'MarketPress',
							'membermouse'    => 'MemberMouse',
							'memberpress'    => 'MemberPress',
							'ninja-forms'    => 'Ninja Forms',
							'pmp'            => 'Paid Memberships Pro',
							'rcp'            => 'Restrict Content Pro',
							's2member'       => 's2Member',
							'shopp'	         => 'Shopp',
							'woocommerce'    => 'WooCommerce',
							'wpeasycart'     => 'WP EasyCart',
							'wpec'           => 'WP eCommerce',
						) )
					),
					'affwp_mlm_default_affiliate' => array(
						'name' => __( 'Default Affiliate', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Enter an Affiliate ID to assign sub affiliates to a particular affiliate when no referral is found.' ) . '</p>',
						'type' => 'number',
						'size' => 'small',
						'std' => ''
					),
					'affwp_mlm_matrix_header' => array(
						'name' => '<strong>' . __( 'Matrix Settings', 'affiliatewp-multi-level-marketing' ) . '</strong>',
						'type' => 'header',
					),
					'affwp_mlm_forced_matrix' => array(
						'name' => __( 'Forced Matrix', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Click to enable fixed width and depth matrix settings.', 'affiliatewp-multi-level-marketing' ) . '</p>',
						'type' => 'checkbox'
					),
					'affwp_mlm_matrix_width' => array(
						'name' => __( 'Initial Width', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Enter the number of Sub Affiliates to allow before "spilling over" to the next level.', 'affiliatewp-multi-level-marketing' ) . '</p>',
						'type' => 'number',
						'size' => 'small',
						'std' => ''
					),
					'affwp_mlm_matrix_width_extra' => array(
						'name' => __( 'Extra Branches', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Enter the number of additional "branches" an affiliate can have after their entire matrix is filled.', 'affiliatewp-multi-level-marketing' ) . '</p>',
						'type' => 'number',
						'size' => 'small',
						'std' => ''
					),
					'affwp_mlm_matrix_depth' => array(
						'name' => __( 'Depth', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Enter the number of sub affiliate levels that you want to allow.' ) . '</p>',
						'type' => 'number',
						'size' => 'small',
						'std' => ''
					),
					'affwp_mlm_total_depth' => array(
						'name' => __( 'Total Depth', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Click to apply the depth setting to the total matrix.', 'affiliatewp-multi-level-marketing' ) . '</p>',
						'type' => 'checkbox'
					),
					'affwp_mlm_view_header' => array(
						'name' => '<strong>' . __( 'View Settings', 'affiliatewp-multi-level-marketing' ) . '</strong>',
						'type' => 'header',
					),
					'affwp_mlm_view_subs' => array(
						'name' => __( 'Sub Affiliate View', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Should Sub Affiliates be viewed in a Tree or a List?', 'affiliatewp-multi-level-marketing' ) . '</p>',
						'type' => 'select',
						'options' => array(
							'tree' => __( 'Tree', 'affiliatewp-multi-level-marketing' ),
							'list' => __( 'List', 'affiliatewp-multi-level-marketing' ),
						)
					),
					'affwp_mlm_rates_header' => array(
						'name' => '<strong>' . __( 'Rate Settings', 'affiliatewp-multi-level-marketing' ) . '</strong>',
						'type' => 'header',
					),
					'affwp_mlm_referral_rate_type' => array(
						'name' => __( 'Indirect Referral Rate Type', 'affiliate-wp' ),
						'desc' => '<p class="description">' . __( 'Should referrals made by Sub Affiliates be based on a percentage or flat rate amounts?', 'affiliatewp-multi-level-marketing' ) . '</p>',
						'type' => 'select',
						'options' => array(
							'' => __( 'Site Default', 'affiliate-wp' ),
							'percentage' => __( 'Percentage (%)', 'affiliate-wp' ),
							'flat'       => sprintf( __( 'Flat %s', 'affiliatewp-multi-level-marketing' ), affwp_get_currency() ),
						)
					),
					'affwp_mlm_referral_rate' => array(
						'name' => __( 'Indirect Referral Rate', 'affiliatewp-multi-level-marketing' ),
						'desc' => __( '', 'affiliatewp-multi-level-marketing' ),
						'desc' => '<p class="description">' . __( 'Enter the Indirect Referral Rate Amount. A percentage if the Indirect Referral Rate Type is Percentage, a flat amount otherwise. Rates can also be set for each affiliate individually.', 'affiliatewp-multi-level-marketing' ) . '</p>',	
						'type' => 'number',
						'size' => 'small',
						'step' => '0.01',
						'std' => '',
					),
				)
			)
		);

		$settings = array_merge( $settings, $mlm_settings );
		
		return $settings;
	}

	/**
	 * Per Level Rate Settings
	 *
	 * @since 1.0
	 */
	public function level_rate_settings() {

		add_settings_field(
			'affwp_settings[mlm_rates]',
			__( 'Per Level Rates', 'affiliatewp-multi-level-marketing' ),
			array( $this, 'level_rates_table' ),
			'affwp_settings_mlm',
			'affwp_settings_mlm'
		);
	}

	/**
	 * Get the Rates for each Level
	 *
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	public function get_level_rates() {
		$rates = affiliate_wp()->settings->get( 'mlm_rates', array() );
		return apply_filters( 'affwp_mlm_level_rates', array_values( $rates ) );
	}

	public function sanitize_rates( $input ) {

		// TODO need to sort these from low to high
		
		if( ! empty( $input['mlm_rates'] ) ) {

			if( ! is_array( $input['mlm_rates'] ) ) {
				$input['mlm_rates'] = array();
			}

			foreach( $input['mlm_rates'] as $key => $rate ) {

				// Require the Rate field		To DO - Add Error Message "You must enter a Rate for your Level"
				if( empty( $rate['rate'] ) ) {
				
					unset( $input['mlm_rates'][ $key ] );
				
				} else {

					$input['mlm_rates'][ $key ]['rate'] = sanitize_text_field( $rate['rate'] ); 

				}

			}

		}

		return $input;
	}

	public function level_rates_table() {

		$rates = $this->get_level_rates();
		$count = count( $rates );
									
?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.affwp_mlm_remove_rate').on('click', function(e) {
				e.preventDefault();
				$(this).parent().parent().remove();
			});

			$('#affwp_mlm_new_rate').on('click', function(e) {

				e.preventDefault();

				var row = $('#affiliatewp-mlm-rates tbody tr:last');

				clone = row.clone();

				var count = $('#affiliatewp-mlm-rates tbody tr').length;

				clone.find( 'td input' ).val( '' );
				clone.find( 'input' ).each(function() {
					var name = $( this ).attr( 'name' );

					name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');

					$( this ).attr( 'name', name ).attr( 'id', name );
				});

				clone.insertAfter( row );

			});
		});
		</script>
		<style type="text/css">
		#affiliatewp-mlm-rates th { padding-left: 10px; }
		.affwp_mlm_remove_rate { margin: 8px 0 0 0; cursor: pointer; width: 10px; height: 10px; display: inline-block; text-indent: -9999px; overflow: hidden; }
		.affwp_mlm_remove_rate:active, .affwp_mlm_remove_rate:hover { background-position: -10px 0!important }
		</style>
		<form id="affiliatewp-mlm-rates-form">
			<table id="affiliatewp-mlm-rates" class="form-table wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th style="width: 20%; text-align: center;"><?php _e( 'Level', 'affiliatewp-multi-level-marketing' ); ?></th>
						<th style="width: 60%; text-align: center;"><?php _e( 'Commission Rate', 'affiliatewp-multi-level-marketing' ); ?></th>
						<th style="width: 20%;"><?php _e( 'Delete', 'affiliatewp-multi-level-marketing' ); ?></th>
					</tr>
				</thead>
				<tbody>
                	<?php if( $rates ) :
							$level_count = 0; 
							
							foreach( $rates as $key => $rate ) : 
								$level_count++;
							?>
							<tr>
								<td style="font-size: 18px; text-align: center;">
									<?php 
									
										if( ! empty( $level_count ) ) {
											echo $level_count;
										} else{
											echo '0';
										}
									
									?>
								</td>
								<td>
									<input name="affwp_settings[mlm_rates][<?php echo $key; ?>][rate]" type="text" value="<?php echo esc_attr( $rate['rate'] ); ?>" style="width: 100%;" />
								</td>
								<td>
									<a href="#" class="affwp_mlm_remove_rate" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;">&times;</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="3" style="text-align: center;"><?php _e( 'No level rates created yet', 'affiliatewp-multi-level-marketing' ); ?></td>
						</tr>
					<?php endif; ?>
                    <?php if( empty( $rates ) ) : ?>
                        <tr>
                            <td style="font-size: 18px; text-align: center;">
                                        <?php 
                                        
    
                                            if( ! empty( $level_count ) ) {
                                                echo $level_count;
                                            } else{
                                                echo '0';
                                            }
    
                                        
                                        ?>
                            </td>
                            <td>
                                <input name="affwp_settings[mlm_rates][<?php echo $count; ?>][rate]" type="text" value=""/>
                            </td>
                            <td>
                                <a href="#" class="affwp_mlm_remove_rate" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;">&times;</a>
                            </td>
                        </tr>
                    <?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="3">
							<button id="affwp_mlm_new_rate" name="affwp_mlm_new_rate" class="button" style="width: 100%; height: 110%;"><?php _e( 'Add New Rate', 'affiliatewp-multi-level-marketing' ); ?></button>
						</th>
					</tr>
				</tfoot>
			</table>
            <p style="margin-top: 10px;"><?php _e( 'Add rates from low to high', 'affiliatewp-multi-level-marketing' ); ?></p>
		</form>
<?php
	}
	 
	/**
	 * Edit Affiliate
	 *
	 * @since 1.0
	 * @return void
	 */
	public function edit_affiliate( $affiliate ) {

		$affiliate_connections = affwp_mlm_get_affiliate_connections( absint( $affiliate->affiliate_id ) );
		$parent_affiliate_id   = ! empty( $affiliate_connections->affiliate_parent_id ) ? $affiliate_connections->affiliate_parent_id : '';
		$direct_affiliate_id   = ! empty( $affiliate_connections->direct_affiliate_id ) ? $affiliate_connections->direct_affiliate_id : '';
		$matrix_level  	 	   = ! empty( $affiliate_connections->matrix_level ) ? $affiliate_connections->matrix_level : 0;
		$rate_type             = ! empty( $affiliate_connections->rate_type ) ? $affiliate_connections->rate_type : '';
		$rate                  = ! empty( $affiliate_connections->rate ) ? $affiliate_connections->rate : '';

		// is parent affiliate
		$is_parent_affiliate = affwp_mlm_is_parent_affiliate( $affiliate->affiliate_id );

		// Get all affiliates
		$all_affiliates = affiliate_wp()->affiliates->get_affiliates( array( 'number'  => 0 ) );

		// Build an array of affiliate IDs and names for the drop down
		$affiliate_dropdown = array();
		
		if ( $all_affiliates && ! empty( $all_affiliates ) ) {

			foreach ( $all_affiliates as $a ) {

				if ( $affiliate_name = affiliate_wp()->affiliates->get_affiliate_name( $a->affiliate_id ) ) {
					$affiliate_dropdown[$a->affiliate_id] = $affiliate_name;
				}

			}

			// Make sure to remove current affiliate from the array so they can't be their own parent affiliate
			unset( $affiliate_dropdown[$affiliate->affiliate_id] );

		}

		?>
					
			<table class="form-table">

				<tr class="form-row form-required">

					<th scope="row">
						<label for="parent_affiliate_id"><?php _e( 'Parent Affiliate', 'affiliatewp-multi-level-marketing' ); ?></label>
					</th>

					<td>
						<select name="parent_affiliate_id" id="parent_affiliate_id">
							<option value=""></option>
							<?php foreach( $affiliate_dropdown as $affiliate_id => $affiliate_name ) : ?>
								<option value="<?php echo esc_attr( $affiliate_id ); ?>"<?php selected( $parent_affiliate_id, $affiliate_id ); ?>><?php echo esc_html( $affiliate_name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php _e( 'Enter the name of the affiliate to perform a search.', 'affiliatewp-multi-level-marketing' ); ?></p>
					</td>

				</tr>

				<tr class="form-row form-required">

					<th scope="row">
						<label for="direct_affiliate_id"><?php _e( 'Direct Affiliate', 'affiliatewp-multi-level-marketing' ); ?></label>
					</th>

					<td>
						<select name="direct_affiliate_id" id="direct_affiliate_id">
							<option value=""></option>
							<?php foreach( $affiliate_dropdown as $affiliate_id => $affiliate_name ) : ?>
								<option value="<?php echo esc_attr( $affiliate_id ); ?>"<?php selected( $direct_affiliate_id, $affiliate_id ); ?>><?php echo esc_html( $affiliate_name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php _e( 'The affiliate that referred this affiliate.', 'affiliatewp-multi-level-marketing' ); ?></p>
					</td>

				</tr>
                
                <tr class="form-row form-required">
    
                    <th scope="row">
                        <label for="matrix_level"><?php _e( 'Matrix Level', 'affiliate-wp' ); ?></label>
                    </th>
    
                    <td>
                        <input class="small-text" type="text" name="matrix_level" id="matrix_level" value="<?php echo esc_attr( $matrix_level ); ?>" disabled="1" />
                        <p class="description"><?php _e( 'The affiliate\'s level in the matrix. This cannot be changed.', 'affiliate-wp' ); ?></p>
                    </td>
    
                </tr>
                
			</table>
    	<?php            
		show_sub_affiliates( $affiliate->affiliate_id, 'tree' );

	
	}

	 
	/**
	 * Add Parent Affiliate Field to the Add New Affiliate Form
	 *
	 * @since 1.1
	 * @return void
	 */
	public function add_new_affiliate() {
		
		// Get all affiliates
		$all_affiliates = affiliate_wp()->affiliates->get_affiliates( array( 'number'  => 0 ) );

		// Build an array of affiliate IDs and names for the drop down
		$affiliate_dropdown = array();
		
		if ( $all_affiliates && ! empty( $all_affiliates ) ) {

			foreach ( $all_affiliates as $a ) {

				if ( $affiliate_name = affiliate_wp()->affiliates->get_affiliate_name( $a->affiliate_id ) ) {
					$affiliate_dropdown[$a->affiliate_id] = $affiliate_name;
				}

			}

			// Make sure to remove current affiliate from the array so they can't be their own parent affiliate
			unset( $affiliate_dropdown[$affiliate->affiliate_id] );

		}
		
		ob_start(); ?>
				
		<tr class="form-row">
         
            <th scope="row">
                <label for="parent_affiliate_id"><?php _e( 'Parent Affiliate', 'affiliatewp-multi-level-marketing' ); ?></label>
            </th>
                
            <td>
				<select name="parent_affiliate_id" id="parent_affiliate_id">
					<option value=""></option>
						<?php foreach( $affiliate_dropdown as $affiliate_id => $affiliate_name ) : ?>
						<option value="<?php echo esc_attr( $affiliate_id ); ?>"><?php echo esc_html( $affiliate_name ); ?></option>
						<?php endforeach; ?>
				</select>
				<p class="description"><?php _e( 'Enter the name of the affiliate to perform a search.', 'affiliatewp-multi-level-marketing' ); ?></p>
			</td>
            
         </tr>
				
		<?php			
		$content = ob_get_contents();			
		ob_end_clean();
		echo $content;
    
	}

}