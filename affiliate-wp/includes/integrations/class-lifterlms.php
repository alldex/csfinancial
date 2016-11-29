<?php

class Affiliate_WP_LifterLMS extends Affiliate_WP_Base {

	private $order;

	/**
	 * Setup actions and filters
	 *
	 * @since  1.8.3
	 *
	 * @access  public
	*/
	public function init() {

		$this->context = 'lifterlms';

		// Create a pending referral, and then mark it complete immediately after,
		// because there's no 'pending' order status in LifterLMS.
		add_action( 'lifterlms_order_complete', array( $this, 'create_pending_referral' ), 10, 1 );

		// revoke referral on refund is not yet possible but will be in the future
		// add_action( 'tbd', array( $this, 'revoke_referral' ), 10 );

		// Add link to the LifterLMS order on the referral screen.
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

		// Add affiliate product fields to LifterLMS courses and memberships.
		add_filter( 'llms_meta_fields_course_main', array( $this, 'product_meta_output' ), 77, 1 );
		add_filter( 'llms_meta_fields_llms_membership_settings', array( $this, 'product_meta_output' ), 77, 1 );

		// save affiliate product fields to post meta.
		add_action( 'lifterlms_process_course_meta', array( $this, 'product_meta_save' ), 10, 2 );
		add_action( 'lifterlms_process_llms_membership_meta', array( $this, 'product_meta_save' ), 10, 2 );

		// Add affiliate coupon fields
		add_filter( 'llms_meta_fields_coupon', array( $this, 'coupon_meta_output' ), 10, 1 );

		// Save affiliate coupon fields to post meta.
		add_action( 'lifterlms_process_llms_coupon_meta', array( $this, 'coupon_meta_save' ), 10, 2 );


		// add some data to the llms order screen
		add_action( 'lifterlms_after_order_meta_box', array( $this, 'order_meta_output' ) );


	}


	/**
	 * Create a pending referral (and mark it complete)
	 *
	 * LifterLMS doesn't have a 'pending' order status.
	 *
	 * @since  1.8.3
	 *
	 * @param  int    $order_id WP Post ID of the LifterLMS Order
	 * @return void
	 *
	 * @access public
	 */
	public function create_pending_referral( $order_id ) {

		$order = $this->get_order( $order_id );

		if ( ! $order ) {

			return;

		}

		// if this was a referral or we have a coupon and a coupon affiliate id
		if ( $this->was_referred() || ( $order->coupon_id && $order->coupon_affiliate_id ) ) {

			// if WooCommerce is being use as the LLMS payment method for the order skip referrals for the order
			// because WooCommerce methods will handle the affiliate stuff
			if ( 'woocommerce' === $order->payment_type ) {

				if ( $this->debug ) {

					$this->log( __( 'Referral not created because WooCommerce was used for payment.', 'affiliate-wp' ) );

				}

				return;
			}

			// if referrals are disabled for the LLMS product, don't create a referral
			if ( get_post_meta( $order->product_id, '_affwp_disable_referrals', true ) ) {

				return;
			}

			// check for an existing referral
			$existing = affiliate_wp()->referrals->get_by( 'reference', $order_id, $this->context );

			// if an existing referral exists and it is paid or unpaid exit.
			if ( $existing && ( 'paid' === $existing->status || 'unpaid' === $existing->status ) ) {

				return;
			}

			// get the referring affiliate's affiliate id
			$affiliate_id = $this->get_affiliate_id( $order_id );

			// use our coupon affiliate if we have one
			if ( false !== $order->coupon_affiliate_id ) {

				$affiliate_id = $order->coupon_affiliate_id;
			}

			// customers cannot refer themselves
			if ( $this->is_affiliate_email( $order->user_data->user_email, $affiliate_id ) ) {

				if( $this->debug ) {

					$this->log( __( 'Referral not created because affiliate\'s own account was used.', 'affiliate-wp' ) );
				}

				return;
			}

			$amount = $this->calculate_referral_amount( $order->total, $order->id, $order->product_id, $affiliate_id );

			// Ignore a zero amount referral
			if ( 0 == $amount && affiliate_wp()->settings->get( 'ignore_zero_referrals' ) ) {

				if ( $this->debug ) {

					$this->log( __( 'Referral not created due to 0.00 amount.', 'affiliate-wp' ) );
				}

				return;
			}


			$description = apply_filters( 'affwp_llms_get_referral_description', $order->product_title, $order, $affiliate_id );
			$visit_id = affiliate_wp()->tracking->get_visit_id();

			// Update existing referral if it exists
			// this isn't currently ever going to happen with LifterLMS but leaving it here for future use
			if ( $existing ) {

				// update the previously created referral
				affiliate_wp()->referrals->update_referral( $existing->referral_id, array(
					'amount'       => $amount,
					'reference'    => $order->id,
					'description'  => $description,
					'campaign'     => affiliate_wp()->tracking->get_campaign(),
					'affiliate_id' => $affiliate_id,
					'visit_id'     => $visit_id,
					'products'     => $this->get_products( $order->id ),
					'context'      => $this->context
				) );

				// Complete the referral automatically because we don't have a pending status
				// will update in the future when / if the status becomes available
				$this->complete_referral( $order->id );

				if( $this->debug ) {

					$this->log( sprintf( __( 'LifterLMS Referral #%d updated successfully.', 'affiliate-wp' ), $existing->referral_id ) );

				}

			}
			// No referral exists, so create a new one.
			else {

				// create a new referral
				$referral_id = affiliate_wp()->referrals->add( apply_filters( 'affwp_insert_pending_referral', array(
					'amount'       => $amount,
					'reference'    => $order->id,
					'description'  => $description,
					'campaign'     => affiliate_wp()->tracking->get_campaign(),
					'affiliate_id' => $affiliate_id,
					'visit_id'     => $visit_id,
					'products'     => $this->get_products( $order->id ),
					'context'      => $this->context
				), $amount, $order_id, $description, $affiliate_id, $visit_id, array(), $this->context ) ); // what's this array for?

				if ( $referral_id ) {

					// complete referral automatically because we don't have pending status
					// will update in the future when / if the status becomes available
					$this->complete_referral( $order->id );

					if( $this->debug ) {

						$this->log( sprintf( __( 'Referral #%d created successfully.', 'affiliate-wp' ), $referral_id ) );
					}

				} else {

					if( $this->debug ) {

						$this->log( __( 'Referral failed to be created.', 'affiliate-wp' ) );
					}
				}
			}
		}
	}


	/**
	 * Add an AffiliateWP Tab to LifterLMS Coupon Admin screen
	 *
	 * Allow users to associate a coupon with a specific affiliate
	 *
	 * @since  1.8.3
	 *
	 * @param  array  $fields  An associate array of LifterLMS settings.
	 * @return array
	 *
	 * @access public
	 */
	public function coupon_meta_output( $fields ) {

		global $post;

		add_filter( 'affwp_is_admin_page', '__return_true' );
		affwp_admin_scripts();

		$affiliate_id = get_post_meta( $post->ID, '_affwp_affiliate_id', true );
		$user_id      = affwp_get_affiliate_user_id( $affiliate_id );
		$user         = get_userdata( $user_id );
		$user_name    = ( $user ) ? $user->user_login : '';

		$html = '
			<span class="affwp-ajax-search-wrap">
				<span class="affwp-llms-coupon-input-wrap">
					<input type="hidden" name="_affwp_affiliate_user_id" id="user_id" value="' . esc_attr( $user_id ) . '" />
					<input type="text" name="_affwp_affiliate_user_name" id="user_name" value="' . esc_attr( $user_name ) . '" class="affwp-user-search input-full" data-affwp-status="active" autocomplete="off" />
					<img class="affwp-ajax waiting" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" style="display: none;"/>
				</span>
				<span id="affwp_user_search_results"></span>
			</span>
			<em>' . __( 'Search for an affiliate by username or email.', 'affiliate-wp' ) . '</em>
		';

		$fields[] = array(
			'title' => 'AffiliateWP',
			'fields' => array(
				array(
					'type'	 	 => 'custom-html',
					'label'		 => __( 'Affiliate Discount', 'affiliate-wp' ),
					'desc'		 => __( 'Connect this coupon with an affiliate.', 'affiliate-wp' ),
					'id'		 => '_affwp_affiliate_user_id',
					'value' 	 => $html,
					'desc_class' => 'd-all',
				),
			),
		);

		return apply_filters( 'affwp_llms_meta_fields_coupon' , $fields );

	}


	/**
	 * Save the related coupon fields during coupon post type save actions
	 *
	 * @since  1.8.3
	 *
	 * @param  int    $post_id WP Post ID of the coupon being saved
	 * @param  obj    $post    Instance of WP_Post
	 * @return void
	 *
	 * @access public
	 */
	public function coupon_meta_save( $post_id, $post ) {

		// remove the affiliate id if the username is cleared
		if ( empty( $_POST['_affwp_affiliate_user_name'] ) ) {

			delete_post_meta( $post_id, '_affwp_affiliate_id' );
			return;
		}

		// We need either a username, or a user ID to locate the affiliate.
		// so don't continue without at least one of them (i guess)
		if ( empty( $_POST['_affwp_affiliate_user_id'] ) && empty( $_POST['_affwp_affiliate_user_name'] ) ) {
			return;
		}

		// Locate the userid if we didn't get one from ajax methods
		if ( empty( $_POST['_affwp_affiliate_user_id'] ) ) {

			$user = get_user_by( 'login', $_POST['_affwp_affiliate_user_name'] );
			if( $user ) {
				$user_id = $user->ID;
			}
		}
		// Use the posted user id
		else {
			$user_id = absint( $_POST['_affwp_affiliate_user_id'] );
		}

		// Locate an affiliate, looks like this returns null if the
		// user is not a valid affiliate.
		$affiliate_id = affwp_get_affiliate_id( $user_id );

		// $affiliate_id is null if none found so update regardless of the value
		update_post_meta( $post_id, '_affwp_affiliate_id', $affiliate_id );

		do_action( 'affwp_lifterlms_process_llms_coupon_meta', $post_id, $post );

	}



	/**
	 * Retrieve order details for an order by id
	 *
	 * @since  1.8.3
	 *
	 * @param  int     $order_id  WP Post ID of the LifterLMS Order
	 * @param  boolean $force     If true, will skip the "cached" data
	 *
	 * @return mixed              object of order-related data, or false if
	 *                            no order is found.
	 *
	 * @access private
	 */
	private function get_order( $order_id, $force = false ) {

		// Only perform lookups once, unless forced.
		if ( ! $this->order || $force ) {

			$post = get_post( $order_id );

			if( ! $post ) {

				return false;

			}

			$order = new stdClass();

			$order->id = absint( $order_id );

			// WP Post
			$order->post = $post;

			// payment
			$order->payment_type = get_post_meta( $order->id, '_llms_payment_type', true );
			$order->total = get_post_meta( $order->id, '_llms_order_total', true );

			// Coupon post meta.
			$order->coupon_id = get_post_meta( $order->id , '_llms_order_coupon_id', true );
			// Affiliate ID for the coupon.
			$order->coupon_affiliate_id = ( $order->coupon_id ) ? $this->get_order_coupon_affiliate_id( $order->coupon_id ) : false;

			// user related
			$order->user_id = get_post_meta( $order->id , '_llms_user_id', true );
			$order->user_data = get_userdata( $order->user_id );

			// product related
			$order->product_id = get_post_meta( $order->id , '_llms_order_product_id', true );
			$order->product_title = get_post_meta( $order->id, '_llms_order_product_title', true );

			// "cache"
			$this->order = $order;

		}

		return $this->order;

	}


	/**
	 * Retrieve the affiliate ID associated with a LifterLMS Coupon
	 *
	 * @since  1.8.3
	 *
	 * @param  int    $coupon_id  WP Post ID of the LifterLMS Coupon
	 * @return mixed|int|bool     The affiliate id, or false if no affiliate is found
	 *
	 * @access private
	 */
	private function get_order_coupon_affiliate_id( $coupon_id ) {

		$affiliate_id = get_post_meta( $coupon_id, '_affwp_affiliate_id', true );

		if ( $affiliate_id && affiliate_wp()->tracking->is_valid_affiliate( $affiliate_id ) ) {

			return $affiliate_id;

		}

		return false;

	}


	/**
	 * Retrive an array of product information to pass to AffiliateWP
	 * when creating a referral
	 *
	 * LifterLMS doesn't have the ability to purchase multiple products simultaneously,
	 * but this is still returning an array of arrays, in case it's needed in the future.
	 *
	 * @since  1.8.3
	 *
	 * @param  integer $order_id WordPress Post ID of the LifterLMS Order
	 * @return array
	 *
	 * @access private
	 */
	public function get_products( $order_id = 0 ) {

		$order = $this->get_order( $order_id );

		if ( $order ) {

			return array( array(
				'name'            => $order->product_title,
				'id'              => $order->product_id,
				'price'           => $order->total,
				'referral_amount' => $this->calculate_referral_amount( $order->total, $order->id, $order->product_id )
			) );

		} else {

			return array( array(
				'id' => $order_id,
			) );
		}
	}


	/**
	 * Output some AffiliateWP data on the LifterLMS Order post edit screen
	 *
	 * @since  1.8.3
	 *
	 * @return void
	 * @access public
	 */
	public function order_meta_output( ) {

		global $post;

		$referral = affiliate_wp()->referrals->get_by( 'reference', $post->ID, $this->context );

		if ( ! $referral ) {
			return;
		}

		$affiliate_name = affiliate_wp()->affiliates->get_affiliate_name( $referral->affiliate_id );
		$referral_amount = affwp_currency_filter( affwp_format_amount( $referral->amount ) );
		$referral_status = affwp_get_referral_status_label( $referral->referral_id );
		?>

		<table class="form-table">
		<tbody>

			<tr>
				<th><label><?php _e( 'Referral Details', 'affiliate-wp' ); ?></label></th>

				<td>

					<table class="form-table">

						<tr>
							<td><label><?php _e( 'Amount', 'affiliate-wp' ); ?></label></td>
							<td><?php echo $referral_amount; ?>  (<?php echo $referral_status; ?>)</td>
						</tr>

						<tr>
							<td><label><?php _e( 'Affiliate', 'affiliate-wp' ); ?></label></td>
							<td><a href="<?php echo admin_url( 'admin.php?page=affiliate-wp-referrals&affiliate_id=' . $referral->affiliate_id ); ?>"><?php echo $affiliate_name; ?></a></td>
						</tr>

						<tr>
							<td><label><?php _e( 'Reference', 'affiliate-wp' ); ?></label></td>
							<td><a href="<?php echo admin_url( 'admin.php?page=affiliate-wp-referrals&action=edit_referral&referral_id=' . $referral->referral_id ); ?>">#<?php echo $referral->referral_id; ?></a></td>
						</tr>

					</table>

				</td>

			</tr>

		</tbody>
		</table>
		<?php

	}

	/**
	 * Add an AffiliateWP Tab to LifterLMS Course & Membership Admin screen
	 *
	 * Allow users to disable referrals for the product
	 * Allow users to define custom referral rates for the product
	 *
	 * @since  1.8.3
	 *
	 * @param  array  $fields  associate array of llms settings
	 * @return array
	 *
	 * @access public
	 */
	public function product_meta_output( $fields ) {

		add_filter( 'affwp_is_admin_page', '__return_true' );

		// Inject inline LifterLMS javascript.
		$this->inline_js();

		global $post;

		$product_type = str_replace( 'llms_', '', $post->post_type );

		$fields[] = array(

			'title' => 'AffiliateWP',
			'fields' => array(
				array(
					'type'		 => 'checkbox',
					'label'		 => __( 'Disable Referrals', 'affiliate-wp' ),
					'desc' 		 => sprintf( __( 'Check this box to prevent orders for this %s from generating referral commissions for affiliates.', 'affiliate-wp' ), $product_type ),
					'desc_class' => 'd-3of4 t-3of4 m-1of2',
					'id' 		 => '_affwp_disable_referrals',
					'value' 	 => '1',
					'group'      => '_affwp_enable_referral_overrides-hide'
				),
				array(
					'type'		 => 'checkbox',
					'label'		 => sprintf( __( 'Enable %s Referral Rate', 'affiliate-wp' ), ucfirst( $product_type ) ),
					'desc' 		 => sprintf( __( 'Check this box to enable %s referral rate overrides', 'affiliate-wp' ), $product_type ),
					'desc_class' => 'd-3of4 t-3of4 m-1of2',
					'id' 		 => '_affwp_enable_referral_overrides',
					'value' 	 => '1',
					'group'      => 'llms-affwp-disable-fields',
				),
				array(
					'type'		 => 'number',
					'label'		 => sprintf( __( '%s Referral Rate', 'affiliate-wp' ), ucfirst( $product_type ) ),
					'desc' 		 => sprintf( __( 'Enter a referral rate for this %s', 'affiliate-wp' ), $product_type ),
					'id' 		 => '_affwp_referral_rate',
					'class'  	 => 'input-full',
					'desc_class' => 'd-all',
					'group'      => '_affwp_enable_referral_overrides-show'
				),
				// JS uses this to only bind on llms pages
				array(
					'type' => 'custom-html',
					'id' => 'affwp_llms_enabled',
					'label' => '',
					'value' => '<div id="affwp-llms-enabled"></div>',
				),

			),

		);

		return apply_filters( 'affwp_llms_meta_fields_product', $fields );

	}

	/**
	 * Save the related product fields during course & membership post type save actions
	 *
	 * @since  1.8.3
	 *
	 * @param  int    $post_id WP Post ID of the coupon being saved
	 * @param  obj    $post    Instance of WP_Post
	 * @return void
	 *
	 * @access public
	 */
	public function product_meta_save( $post_id, $post ) {

		$overrides = '';
		$disable = '';
		$rate = '';

		// if disable is set, clear everything else and update disable postmeta
		if ( isset( $_POST['_affwp_disable_referrals'] ) ) {

			$disable = 1;

		}

		// If overrides are set, update the override-related fields.
		elseif ( isset( $_POST['_affwp_enable_referral_overrides'] ) ) {

			$overrides = 1;
			$rate = $_POST['_affwp_referral_rate'];

		}

		// Update post meta
		update_post_meta( $post_id, '_affwp_enable_referral_overrides', $overrides );
		update_post_meta( $post_id, '_affwp_disable_referrals', $disable );
		update_post_meta( $post_id, '_affwp_' . $this->context . '_product_rate', $rate );

	}

	/**
	 * Link the Reference column on the AffWp screen to a LifterLMS Order
	 *
	 * @since  1.8.3
	 *
	 *  @param int   $reference  WP Post ID of the LifterLMS ORder
	 * @param obj   $referral   object of referral data
	 *
	 * @return  html
	 *
	 * @access  public
	*/
	public function reference_link( $reference = 0, $referral ) {

		if( empty( $referral->context ) || 'lifterlms' != $referral->context ) {

			return $reference;

		}

		$url = get_edit_post_link( $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';

	}

	/**
	 * Provides static js for the LifterLMS AffiliateWP integration
	 *
	 * @since  1.8.3
	 *
	 * @return string  Static javascript, specific to the LifterLMS integration.
	 */
	public function inline_js() { ?>
		<script>
		( function( $ ) {

			window.llms = window.llms || {};

			/**
			 * Handle the AffiliateWP Tab JS interaction
			 * @return obj
			 */
			window.llms.metabox_product_affwp = function() {

				/**
				 * Initialize and Bind events if our check element is found
				 * @return void
				 */
				this.init = function() {

					// only bind if our hidden input exists in the dom
					if ( $( '#affwp-llms-enabled' ).length ) {

						this.bind();

					}

				};

				/**
				 * Bind dom events
				 * @return void
				 */
				this.bind = function() {

					this.bind_disable_field();
					$( '#_affwp_disable_referrals' ).trigger( 'change' );

					this.bind_override_field();
					$( '#_affwp_enable_referral_overrides' ).trigger( 'change' );

				};

				/**
				 * Bind thie "disable referrals" fields
				 * @return void
				 */
				this.bind_disable_field = function() {

					$( '#_affwp_disable_referrals' ).on( 'change', function() {

						var $group = $( '.llms-affwp-disable-fields');

						if ( $(this).is( ':checked' ) ) {

							$group.hide( 200 );
							$( '#_affwp_enable_referral_overrides' ).removeAttr( 'checked' ).trigger( 'change' );

						} else {

							$group.show( 200 );

						}

					} );

				};

				/**
				 * Bind the "enable overrides" field
				 * @return void
				 */
				this.bind_override_field = function() {

					$( '#_affwp_enable_referral_overrides' ).on( 'change', function() {

						var $show = $( '._affwp_enable_referral_overrides-show'),
							$hide = $( '._affwp_enable_referral_overrides-hide');

						if ( $(this).is( ':checked' ) ) {

							$show.show( 200 );
							$hide.hide( 200 );
							$( '#_affwp_disable_referrals' ).removeAttr( 'checked' ).trigger( 'change' ).hide( 200 );

						} else {

							$show.hide( 200 );
							$hide.show( 200 );

						}

					} );

				};

				// go
				this.init();

				// return, just bc
				return this;

			};

			// instatiate the class
			var a = new window.llms.metabox_product_affwp();

		} )( jQuery );
		</script>
	<?php }

}
new Affiliate_WP_LifterLMS;