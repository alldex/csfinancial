<?php

class AffiliateWP_Ranks_Settings {
	
	/**
	 * AffiliateWP options
	 *
	 * @since 1.0
	 * @var array
	 */
	private $options;	

	public function __construct() {
		
		$this->options = affiliate_wp()->settings->get_all();

		add_filter( 'affwp_settings_tabs', array( $this, 'settings_tab' ) );
		add_filter( 'affwp_settings', array( $this, 'settings' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'default_rank_settings' ) );
		add_action( 'admin_init', array( $this, 'rank_settings' ) );
		
		add_filter( 'affwp_settings_rates_sanitize', array( $this, 'sanitize_ranks' ) );
		add_action( 'affwp_edit_affiliate_end', array( $this, 'edit_affiliate_rank' ), 10, 1 );

	}

	/**
	 * Register the Ranks Settings Tab
	 *
	 * @since 1.0
	 */
	public function settings_tab( $tabs ) {
		$tabs['ranks'] = __( 'Ranks', 'affiliatewp-ranks' );
		return $tabs;
	}

	/**
	 * Rank Settings
	 *
	 * @since 1.0
	 */
	public function rank_settings() {

		add_settings_section(
			'affwp_settings_ranks',
			__return_null(),
			'__return_false',
			'affwp_settings_ranks'
		);

		add_settings_field(
			'affwp_settings[ranks]',
			__( 'Ranks', 'affiliatewp-ranks' ), 
			array( $this, 'ranks_table' ),
			'affwp_settings_ranks',
			'affwp_settings_ranks'
		);
	}

	/**
	 * Default Rank Settings
	 *
	 * @since 1.0
	 */
	public function default_rank_settings() {

		add_settings_field(
			'affwp_settings[default_rank]',
			__( 'Default Rank', 'affiliatewp-ranks' ),
			array( $this, 'ranks_list' ),
			'affwp_settings_ranks',
			'affwp_settings_ranks'
		);
	}

	/**
	 * Register Rank Settings
	 *
	 * @since 1.0
	 */
	public function settings( $settings ) {
	
		// Rank Settings
		$rank_settings = array(
			
			'ranks' => apply_filters( 'affwp_settings_ranks',
				array(
					'affwp_ranks_ranks_header' => array(
						'name' => '<strong>' . __( 'Rank Settings', 'affiliatewp-ranks' ) . '</strong>',
						'type' => 'header',
					),
				
				
				)
			)
		);

		$settings = array_merge( $settings, $rank_settings );
		
		return $settings;
	}

	/**
	 * Get ALL Ranks
	 *
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	public function get_ranks() {
		$ranks = affiliate_wp()->settings->get( 'ranks', array() );
		return apply_filters( 'affwp_ranks_all_ranks', array_values( $ranks ) );
	}


	/**
	 * Add Rank Selector for Default Rank
	 *
	 * @since 1.0
	 * @return void
	 */
	public function ranks_list() {

		// Returns an array
		$default_rank = affiliate_wp()->settings->get( 'default_rank' );

		// Get all ranks
		$ranks = get_ranks();
		
		if ( $ranks && ! empty( $ranks ) ) {

			?>
						
				<table class="form-table">
	
					<tr class="form-row form-required">
	
						<td>
							<select name="affwp_settings[default_rank][0]">
								<option value=""></option>
								<?php foreach( $ranks as $rank_key => $rank_option ) : ?>
                                    
                                    <option value="<?php echo esc_attr( $rank_option['id'] ); ?>"<?php selected( $rank_option['id'], $default_rank[0] ); ?>><?php echo esc_html( $rank_option['name'] ); ?></option>
                                    
								<?php endforeach; ?>
							</select>
							<p class="description"><?php _e( 'What Rank should be given to new affiliates?', 'affiliatewp-ranks' ); ?></p>
						</td>
	
					</tr>
					
				</table>
	
		<?php
		}
		
	}

	public function sanitize_ranks( $input ) {

		if( ! empty( $input['ranks'] ) ) {

			if( ! is_array( $input['ranks'] ) ) {
				$input['ranks'] = array();
			}

			foreach( $input['ranks'] as $key => $rank ) {

				// Require the Name, Type, and Mode fields		To DO - Add Error Message "You must enter a Rank Name, Type, and Mode"
				if( empty( $rank['name'] ) || empty( $rank['type'] ) || empty( $rank['mode'] ) ) {
				
					unset( $input['ranks'][ $key ] );
				
				} else {

					$input['ranks'][ $key ]['id'] = absint( $rank['id'] );
					$input['ranks'][ $key ]['order'] = absint( $rank['order'] );
					$input['ranks'][ $key ]['name'] = sanitize_text_field( $rank['name'] );
					$input['ranks'][ $key ]['mode'] = sanitize_text_field( $rank['mode'] );
					$input['ranks'][ $key ]['type'] = sanitize_text_field( $rank['type'] );
					$input['ranks'][ $key ]['requirement'] = absint( $rank['requirement'] ); 
					$input['ranks'][ $key ]['rate'] = sanitize_text_field( $rank['rate'] );
					$input['ranks'][ $key ]['rate_type'] = sanitize_text_field( $rank['rate_type'] ); 

				}

			}

		}

		return $input;
	}

	public function ranks_table() {

		$ranks = $this->get_ranks();
		$count = count( $ranks );
									
?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.affwp_ranks_remove_rank').on('click', function(e) {
				e.preventDefault();
				$(this).parent().parent().remove();
			});

			$('#affwp_ranks_new_rank').on('click', function(e) {

				e.preventDefault();

				var row = $('#affiliatewp-ranks tbody tr:last');

				clone = row.clone();

				var count = $('#affiliatewp-ranks tbody tr').length;

				clone.find( 'td input, td select' ).val( '' );
				clone.find( 'input, select' ).each(function() {
					var name = $( this ).attr( 'name' );

					name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');

					$( this ).attr( 'name', name ).attr( 'id', name );
				});

				clone.insertAfter( row );

			});
		});
		</script>
		<style type="text/css">
		#affiliatewp-ranks th { padding-left: 10px; }
		.affwp_ranks_remove_rank { margin: 8px 0 0 0; cursor: pointer; width: 10px; height: 10px; display: inline-block; text-indent: -9999px; overflow: hidden; }
		.affwp_ranks_remove_rank:active, .affwp_ranks_remove_rank:hover { background-position: -10px 0!important }
		</style>
		<form id="affiliatewp-ranks-form">
			<table id="affiliatewp-ranks" class="form-table wp-list-table widefat fixed posts">
				<thead>
					<tr>
                    	<th style="width: 7.5%; text-align: center;"><?php _e( 'ID', 'affiliatewp-ranks' ); ?></th>
                        <th style="width: 7.5%; text-align: center;"><?php _e( 'Order', 'affiliatewp-ranks' ); ?></th>
						<th style="width: 20%; text-align: center;"><?php _e( 'Name', 'affiliatewp-ranks' ); ?></th>
                        <th style="width: 12%; text-align: center;"><?php _e( 'Mode', 'affiliatewp-ranks' ); ?></th>
						<th style="width: 12%; text-align: center;"><?php _e( 'Type', 'affiliatewp-ranks' ); ?></th>
						<th style="width: 11%; text-align: center;"><?php _e( 'Requirement', 'affiliatewp-ranks' ); ?></th>
						<th style="width: 10%; text-align: center;"><?php _e( 'Rate', 'affiliatewp-ranks' ); ?></th>
						<th style="width: 15%; text-align: center;"><?php _e( 'Rate Type', 'affiliatewp-ranks' ); ?></th>
						<th style="width: 5%;"></th>
					</tr>
				</thead>
				<tbody>
                	<?php if( $ranks ) :

							foreach( $ranks as $key => $rank ) : 
								
								$rank_id = !empty( $rank['id'] ) ? $rank['id'] : affwp_ranks_new_rank_id();
								$mode = $rank['mode'];
								$type = $rank['type'];
								$rate_type = $rank['rate_type'];
								
							?>
							<tr>                                             
                                <td>
                                    <input name="affwp_settings[ranks][<?php echo $key; ?>][id]" class="small-text" type="text" value="<?php echo esc_attr( $rank_id ); ?>"  />
                                </td>
								<td>
									<input name="affwp_settings[ranks][<?php echo $key; ?>][order]" class="small-text" type="text" value="<?php echo esc_attr( $rank['order'] ); ?>" style="width: 100%;" />
								</td>
								<td>
									<input name="affwp_settings[ranks][<?php echo $key; ?>][name]" type="text" value="<?php echo esc_attr( $rank['name'] ); ?>" style="width: 100%;" />
								</td>
								<td>
                                    <select name="affwp_settings[ranks][<?php echo $key; ?>][mode]">
                                        <?php foreach( affwp_ranks_get_rank_modes() as $mode_key => $mode_option ) { ?>
                                            <option value="<?php echo esc_attr( $mode_key ); ?>"<?php selected( $mode_key, $mode ); ?>><?php echo esc_html( $mode_option ); ?></option>
                                        <?php }; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="affwp_settings[ranks][<?php echo $key; ?>][type]">
                                    	<option value=""><?php _e( 'None', 'affiliatewp-ranks' ); ?></option>
                                        <?php foreach( affwp_ranks_get_rank_types() as $type_key => $type_option ) { ?>
                                            <option value="<?php echo esc_attr( $type_key ); ?>"<?php selected( $type_key, $type ); ?>><?php echo esc_html( $type_option ); ?></option>
                                        <?php }; ?>
                                    </select>
                                </td>
								<td>
									<input name="affwp_settings[ranks][<?php echo $key; ?>][requirement]" type="text" value="<?php echo esc_attr( $rank['requirement'] ); ?>" style="width: 100%;" />
								</td>                    
                                <td>
                                    <input name="affwp_settings[ranks][<?php echo $key; ?>][rate]" type="text" value="<?php echo esc_attr( $rank['rate'] ); ?>" style="width: 100%;" />
                                </td>
                                <td>
                                    <select name="affwp_settings[ranks][<?php echo $key; ?>][rate_type]">
                                        <option value=""><?php _e( 'Site Default', 'affiliate-wp' ); ?></option>
                                        <?php foreach( affwp_get_affiliate_rate_types() as $rate_type_key => $rate_type_option ) { ?>
                                            <option value="<?php echo esc_attr( $rate_type_key ); ?>"<?php selected( $rate_type_key, $rate_type ); ?>><?php echo esc_html( $rate_type_option ); ?></option>
                                        <?php }; ?>
                                    </select>
                                </td>
								<td>
									<a href="#" class="affwp_ranks_remove_rank" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;">&times;</a>
								</td>   
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="9" style="text-align: center;"><?php _e( 'No ranks created yet', 'affiliatewp-ranks' ); ?></td>
						</tr>
					<?php endif; ?>
                    <?php if( empty( $ranks ) ) : ?>
                    
							<tr>
                                <td>
                                    <input name="affwp_settings[ranks][<?php echo $count; ?>][id]" class="small-text" type="text" value="<?php echo esc_attr( $rank_id ); ?>" />
                                </td>
								<td>
									<input name="affwp_settings[ranks][<?php echo $count; ?>][order]" class="small-text" type="text" value="<?php echo esc_attr( $rank['order'] ); ?>" style="width: 100%;" />
								</td>  
								<td>
									<input name="affwp_settings[ranks][<?php echo $count; ?>][name]" type="text" value="<?php echo esc_attr( $rank['name'] ); ?>" style="width: 100%;" />
								</td>
								<td>
                                    <select name="affwp_settings[ranks][<?php echo $count; ?>][mode]">
                                        <?php foreach( affwp_ranks_get_rank_modes() as $mode_key => $mode_option ) { ?>
                                            <option value="<?php echo esc_attr( $mode_key ); ?>"<?php selected( $mode_key, $mode ); ?>><?php echo esc_html( $mode_option ); ?></option>
                                        <?php }; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="affwp_settings[ranks][<?php echo $count; ?>][type]">
                                    <option value=""><?php _e( 'None', 'affiliatewp-ranks' ); ?></option>
                                        <?php foreach( affwp_ranks_get_rank_types() as $type_key => $type_option ) { ?>
                                            <option value="<?php echo esc_attr( $type_key ); ?>"<?php selected( $type_key, $type ); ?>><?php echo esc_html( $type_option ); ?></option>
                                        <?php }; ?>
                                    </select>
                                </td>
								<td>
									<input name="affwp_settings[ranks][<?php echo $count; ?>][requirement]" type="text" value="<?php echo esc_attr( $rank['requirement'] ); ?>" style="width: 100%;" />
								</td>                    
                                <td>
                                    <input name="affwp_settings[ranks][<?php echo $count; ?>][rate]" type="text" value="<?php echo esc_attr( $rank['rate'] ); ?>" style="width: 100%;" />
                                </td>
                                <td>
                                    <select name="affwp_settings[ranks][<?php echo $count; ?>][rate_type]">
                                        <option value=""><?php _e( 'Site Default', 'affiliate-wp' ); ?></option>
                                        <?php foreach( affwp_get_affiliate_rate_types() as $rate_type_key => $rate_type_option ) { ?>
                                            <option value="<?php echo esc_attr( $rate_type_key ); ?>"<?php selected( $rate_type_key, $rate_type ); ?>><?php echo esc_html( $rate_type_option ); ?></option>
                                        <?php }; ?>
                                    </select>
                                </td>
								<td>
									<a href="#" class="affwp_ranks_remove_rank" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;">&times;</a>
								</td>
							</tr>                        
                        
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="9">
							<button id="affwp_ranks_new_rank" name="affwp_ranks_new_rank" class="button" style="width: 100%; height: 110%;"><?php _e( 'Add New Rank', 'affiliatewp-ranks' ); ?></button>
						</th>
					</tr>
				</tfoot>
			</table>
		</form>
<?php
	}

	/**
	 * Add Rank Selector to Edit Affiliate Screen
	 *
	 * @since 1.0
	 * @return void
	 */
	public function edit_affiliate_rank( $affiliate ) {

		$affiliate_id = absint( $affiliate->affiliate_id );
		$affiliate_rank_id = affwp_ranks_get_affiliate_rank( $affiliate_id );

		// Get all ranks
		$ranks = get_ranks();
		
		if ( $ranks && ! empty( $ranks ) ) {

			?>
						
				<table class="form-table">
	
					<tr class="form-row form-required">
	
						<th scope="row">
							<label for="affiliate_rank_id"><?php _e( 'Rank', 'affiliatewp-ranks' ); ?></label>
						</th>
	
						<td>
							<select name="affiliate_rank_id" id="affiliate_rank_id">
								<option value=""></option>
								<?php foreach( $ranks as $rank_key => $rank_option ) : ?>
                                    
                                    <option value="<?php echo esc_attr( $rank_option['id'] ); ?>"<?php selected( $rank_option['id'], $affiliate_rank_id ); ?>><?php echo esc_html( $rank_option['name'] ); ?></option>
                                    
								<?php endforeach; ?>
							</select>
							<p class="description"><?php _e( 'Add or change the rank for this affiliate.', 'affiliatewp-ranks' ); ?></p>
						</td>
	
					</tr>
					
				</table>
	
		<?php
		}	
	}
}
