<div class="wrap">

	<h2><?php _e( 'Edit Referral', 'affiliate-wp' ); ?></h2>

	<form method="post" id="affwp_edit_referral">

		<?php do_action( 'affwp_edit_referral_top', $commission ); ?>

		<table class="form-table">


			<tr class="form-row form-required">

				<th scope="row">
					<label for="affiliate_id"><?php _e( 'Affiliate ID', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input class="small-text" type="text" name="affiliate_id" id="affiliate_id" value="<?php echo esc_attr( $commission->agent_id ); ?>" disabled="disabled"/>
					<p class="description"><?php _e( 'The affiliate&#8217;s ID this referral belongs to. This value cannot be changed.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<?php if ( $payout ) : ?>
				<tr class="form-row form-required">

					<th scope="row">
						<label for="payout_id"><?php _e( 'Payout ID', 'affiliate-wp' ); ?></label>
					</th>

					<td>
						<input class="small-text" type="text" name="payout_id" id="affiliate_id" value="<?php echo esc_attr( $payout->ID ); ?>" disabled="disabled"/>
						<?php
						/* translators: 1: View payout link, 2: payout amount */
						printf( __( '%1$s | Total: %2$s', 'affiliate-wp'),
							sprintf( '<a href="%1$s">%2$s</a>',
								esc_url( $payout_link ),
								esc_html_x( 'View', 'payout', 'affiliate-wp' )
							),
							affwp_currency_filter( affwp_format_amount( $payout->amount ) )
						)
						?>
					</td>

				</tr>
			<?php endif; ?>

			<tr class="form-row form-required">

				<th scope="row">
					<label for="amount"><?php _e( 'Amount', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" name="amount" id="amount" value="<?php echo esc_attr( $commission->amount ); ?>" disabled="disabled"/>
					<?php if ( $payout ) : ?>
						<p class="description"><?php esc_html_e( 'The referral amount cannot be changed once it has been included in a payout.', 'affiliate-wp' ); ?></p>
					<?php else : ?>
						<p class="description"><?php _e( 'The amount of the referral, such as 15.', 'affiliate-wp' ); ?></p>
					<?php endif; ?>
				</td>

			</tr>

			<tr class="form-row form-required">

				<th scope="row">
					<label for="date"><?php _e( 'Date', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" name="date" id="date" value="<?php echo esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $commission->date ) ) ); ?>" disabled="disabled" />
				</td>

			</tr>

			<tr class="form-row form-required">

				<th scope="row">
					<label for="description"><?php _e( 'Description', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<textarea name="description" id="description" rows="5" cols="60" disabled="disabled"><?php echo esc_html( $commission->description ); ?></textarea>
					<p class="description"><?php _e( 'Enter a description for this referral.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required">

				<th scope="row">
					<label for="reference"><?php _e( 'Reference', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" name="reference" id="reference" value="<?php echo esc_attr( $commission->reference ); ?>" disabled="disabled"/>
					<p class="description"><?php _e( 'Enter a reference for this referral (optional). Usually this would be the transaction ID of the associated purchase.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required">
				<?php $readonly = __checked_selected_helper( true, ! empty( $commission->context ), false, 'readonly' ); ?>
				<th scope="row">
					<label for="context"><?php _e( 'Context', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" name="context" id="context" value="<?php echo esc_attr( $commission->context ); ?>" <?php echo $readonly; ?> />
					<p class="description"><?php _e( 'Context for this referral (optional). Usually this is used to help identify the payment system that was used for the transaction.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required">

				<th scope="row">
					<label for="status"><?php _e( 'Status', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<select name="status" id="status" disabled="disabled">
						<option value="unpaid"<?php selected( 'unpaid', $commission->status ); ?>><?php _e( 'Unpaid', 'affiliate-wp' ); ?></option>
						<option value="paid"<?php selected( 'paid', $commission->status ); ?>><?php _e( 'Paid', 'affiliate-wp' ); ?></option>
						<option value="pending"<?php selected( 'pending', $commission->status ); ?>><?php _e( 'Pending', 'affiliate-wp' ); ?></option>
						<option value="rejected"<?php selected( 'rejected', $commission->status ); ?>><?php _e( 'Rejected', 'affiliate-wp' ); ?></option>
					</select>
					<?php if ( $payout ) : ?>
						<p class="description"><?php esc_html_e( 'The referral status cannot be changed once it has been included in a payout.', 'affiliate-wp' ); ?></p>
					<?php else : ?>
						<p class="description"><?php _e( 'Select the status of the referral.', 'affiliate-wp' ); ?></p>
					<?php endif; ?>
				</td>

			</tr>

		</table>

                <table class="form-table">
                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_contract_number"><?php _e('Contract Number', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <input class="medium-text" type="text" name="client_contract_number" id="client_contract_number" value="<?php echo esc_attr($client["contract_number"]); ?>" disabled="disabled"/>
                            <p class="description"><?php _e('The unique contract number of the Client this commission belongs to. None of the client values can be changed.', 'affiliate-ltp'); ?></p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_name"><?php _e('Client Name', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <input class="medium-text" type="text" name="client_name" id="client_name" value="<?php echo esc_attr($client["name"]); ?>" disabled="disabled"/>
                            <p class="description"><?php _e('The name of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_street_address"><?php _e('Street Address', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <textarea class="medium-text" name="client_street_address" id="client_street_address" disabled="disabled"><?php echo esc_attr($client["street_address"]); ?></textarea>
                            <p class="description"><?php _e('The street address of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_city_address"><?php _e('City', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <input class="medium-text" type="text" name="client_city_address" id="client_city_address" value="<?php echo esc_attr($client["city"]); ?>" disabled="disabled" />
                            <p class="description"><?php _e('The city of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_zip_address"><?php _e('Zipcode', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <input class="medium-text" type="text" name="client_zip_address" id="client_zip_address" value="<?php echo esc_attr($client["zip"]); ?>" disabled="disabled"/>
                            <p class="description"><?php _e('The zipcode of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>
                    </tr>

                    <tr class="form-row form-required">
                        <th scope="row">
                            <label for="client_phone"><?php _e('Client Phone', 'affiliate-ltp'); ?></label>
                        </th>
                        <td>
                            <input class="medium-text" type="text" name="client_phone" id="client_phone" value="<?php echo esc_attr($client["phone"]); ?>" disabled="disabled"/>
                            <p class="description"><?php _e('The phone number of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>
                    </tr>
                    <tr class="form-row form-required">
                        <th scope="row">
                            <label for="client_email"><?php _e('Client Email', 'affiliate-ltp'); ?></label>
                        </th>
                        <td>
                            <input class="medium-text" type="text" name="client_email" id="client_email" value="<?php echo esc_attr($client["email"]); ?>" disabled="disabled"/>
                            <p class="description"><?php _e('The email of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>
                    </tr>
                </table>
		<?php do_action( 'affwp_edit_referral_bottom', $commission ); ?>

		<?php echo wp_nonce_field( 'affwp_edit_referral_nonce', 'affwp_edit_referral_nonce' ); ?>
		<input type="hidden" name="referral_id" value="<?php echo absint( $commission->commission_id ); ?>" />
		<input type="hidden" name="affwp_action" value="process_update_referral" />

		<?php submit_button( __( 'Update Referral', 'affiliate-wp' ) ); ?>

	</form>

</div>