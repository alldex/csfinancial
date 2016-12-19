<div class="wrap">

	<h2><?php _e( 'New Commission', 'affiliate-ltp' ); ?></h2>
	
	<form method="post" id="affwp_add_referral">

		<?php do_action( 'affwp_new_referral_top' ); ?>

		<p><?php _e( 'Use this screen to manually create a new referral record for an affiliate.', 'affiliate-wp' ); ?></p>

		<table class="form-table">
                    
                        <tr class="form-row form-required">

				<th scope="row">
					<label for="cb_split_commission"><?php _e( 'Split Commission?', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
                                    <input type="checkbox" name="cb_split_commission" id="cb_split_commission" />
                                    <p class="description"><?php _e( 'If the commission is split up between two or more agents.', 'affiliate-ltp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required commission_row_single">

				<th scope="row">
					<label for="user_name"><?php _e( 'Affiliate', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<span class="affwp-ajax-search-wrap">
						<input class="agent-name affwp-agent-search" type="text" name="agent_name" data-affwp-status="active" autocomplete="off" />
						<input class="agent-id" type="hidden" name="agent_id" value="" />
					</span>
					<p class="description"><?php _e( 'Enter the name of the affiliate or enter a partial name or email to perform a search.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>
                        
                        <tr class="form-row form-required commission_row_multiple hidden">

				<th scope="row">
					<label><?php _e( 'Splits', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
                                    <input type="button" class="split-add" value="Add Split" />
                                    <table class="split-list">
                                        <thead>
                                            <th><?php _e( 'Agent', 'affiliate-ltp') ; ?></th>
                                            <th><?php _e( 'Split %', 'affiliate-ltp') ; ?></th>
                                            <th></th>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th><?php _e( 'Total Split %:', 'affiliate-ltp') ;?></th>
                                                <th><span class="split-total">100</span></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
				</td>
			</tr>
                        

			<tr class="form-row form-required">

				<th scope="row">
					<label for="amount"><?php _e( 'Amount', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" name="amount" id="amount" />
					<p class="description"><?php _e( 'The amount of the referral, such as 15.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required">

				<th scope="row">
					<label for="amount"><?php _e( 'Date', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" name="date" id="date" class="affwp-datepicker" autocomplete="off" placeholder="<?php echo esc_attr( date_i18n( 'm/d/y', strtotime( 'today' ) ) ); ?>"/>
				</td>

			</tr>
		</table>
                
                <h3>Client Information</h3>
                <table class="form-table">


                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_name"><?php _e('Contract Number', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <span class="affwp-ajax-search-wrap">
                                    <input type="text" name="client_contract_number" id="client_contract_number" class="affwp-client-search" autocomplete="off" />
                                    <input type="hidden" name="client_id" id="client_id" value="" />
                                    <input type="button" class="affwp-client-search-reset" value="Clear" />
                            </span>
                            <p class="description"><?php _e('The unique contract number of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                            <p class="readonly-description hidden">
                                <?php printf(__('This client information can only be changed in the <a href="%s">Agent CMS</a>', 
                                        'affiliate-ltp'), "https://cms.mycommonsensefinancial.com/"); ?>
                            </p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_name"><?php _e('Name', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <input class="medium-text" type="text" name="client_name" id="client_name" value="" />
                            <p class="description"><?php _e('The name of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_street_address"><?php _e('Street Address', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <textarea class="medium-text" name="client_street_address" id="client_street_address"></textarea>
                            <p class="description"><?php _e('The street address of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_city_address"><?php _e('City', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <input class="medium-text" type="text" name="client_city_address" id="client_city_address" value="" />
                            <p class="description"><?php _e('The city of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>

                    </tr>

                    <tr class="form-row form-required">

                        <th scope="row">
                            <label for="client_zip_address"><?php _e('Zipcode', 'affiliate-ltp'); ?></label>
                        </th>

                        <td>
                            <input class="medium-text" type="text" name="client_zip_address" id="client_zip_address" value=""/>
                            <p class="description"><?php _e('The zipcode of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>
                    </tr>

                    <tr class="form-row form-required">
                        <th scope="row">
                            <label for="client_phone"><?php _e('Phone', 'affiliate-ltp'); ?></label>
                        </th>
                        <td>
                            <input class="medium-text" type="text" name="client_phone" id="client_phone" />
                            <p class="description"><?php _e('The phone number of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>
                    </tr>
                    <tr class="form-row form-required">
                        <th scope="row">
                            <label for="client_email"><?php _e('Email', 'affiliate-ltp'); ?></label>
                        </th>
                        <td>
                            <input class="medium-text" type="text" name="client_email" id="client_email"/>
                            <p class="description"><?php _e('The email of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                        </td>
                    </tr>
                </table>
                
		<?php do_action( 'affwp_new_referral_bottom' ); ?>

		<?php echo wp_nonce_field( 'affwp_add_referral_nonce', 'affwp_add_referral_nonce' ); ?>
		<input type="hidden" name="affwp_action" value="add_referral" />

		<?php submit_button( __( 'Add Referral', 'affiliate-wp' ) ); ?>

	</form>

</div>