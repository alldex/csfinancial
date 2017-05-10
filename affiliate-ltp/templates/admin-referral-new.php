<div class="wrap" ng-app="commissionsApp" ng-controller="CommissionAddController as commissionAdd"
     ng-init="">

	<h2><?php _e( 'New Commission', 'affiliate-ltp' ); ?></h2>
	
	<form method="post" id="affwp_add_referral">

		<?php do_action( 'affwp_new_referral_top' ); ?>

		<p><?php _e( 'Use this screen to manually create a new referral record for an affiliate.', 'affiliate-wp' ); ?></p>

        <h3>Client Information</h3>
        <table class="form-table">


            <tr class="form-row form-required">

                <th scope="row">
                    <label for="client_name"><?php _e('Contract Number', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <span class="affwp-ajax-search-wrap">
                        <input 
                               ltp-client-autocomplete type="text" 
                               name="client_contract_number" id="client_contract_number" 
                               class="affwp-client-search"
                               ng-readonly="commissionAdd.readonlyClient" 
                               ng-model="commissionAdd.client.contract_number" />
                            <input type="button" class="affwp-client-search-reset" value="Clear" ng-click="commissionAdd.resetClient()" />
                    </span>
                    <p class="description"><?php _e('The unique contract number of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>

            </tr>

            <tr class="form-row form-required">

                <th scope="row">
                    <label for="client_name"><?php _e('Name', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <input class="medium-text" type="text" name="client_name" id="client_name" 
                           ng-readonly="commissionAdd.readonlyClient" ng-model="commissionAdd.client.name" />
                    <p class="description"><?php _e('The name of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>

            </tr>

            <tr class="form-row form-required">

                <th scope="row">
                    <label for="client_street_address"><?php _e('Street Address', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <textarea class="medium-text" ng-readonly="commissionAdd.readonlyClient" ng-model="commissionAdd.client.street_address" 
                              name="client_street_address" id="client_street_address"></textarea>
                    <p class="description"><?php _e('The street address of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>

            </tr>

            <tr class="form-row form-required">

                <th scope="row">
                    <label for="client_city_address"><?php _e('City', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <input class="medium-text" type="text" name="client_city_address" id="client_city_address"
                           ng-readonly="commissionAdd.readonlyClient" ng-model="commissionAdd.client.city"/>
                    <p class="description"><?php _e('The city of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>

            </tr>
            <tr class="form-row form-required">

                <th scope="row">
                    <label for="client_state_address"><?php _e('State', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <select name="client_state_address" id="client_state_address"
                           ng-disabled="commissionAdd.readonlyClient" ng-model="commissionAdd.client.state"/>
                        <?php foreach ($state_list as $state) : ?>
                        <option value="<?= $state['abbr']; ?>"><?= $state['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('The state of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>

            </tr>
            
            <tr class="form-row form-required">

                <th scope="row">
                    <label for="client_state_check"><?php _e('Sale originated in a different state?', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <input type="checkbox" name="sale_origination" id="client_state_check"
                           ng-disabled="commissionAdd.readonlyClient" ng-model="commissionAdd.saleOriginatedOutOfState"/>
                    <p class="description"><?php _e('Did the sale originate in another state than the one the client resides in.', 'affiliate-ltp'); ?></p>
                </td>

            </tr>
            
            <tr class="form-row form-required" ng-show="commissionAdd.saleOriginatedOutOfState">

                <th scope="row">
                    <label for="client_state_of_sale"><?php _e('Contract Origination State', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <select name="client_state_of_sale" id="client_state_of_sale"
                           ng-disabled="commissionAdd.readonlyClient" ng-model="commissionAdd.client.state_of_sale"/>
                        <?php foreach ($state_list as $state) : ?>
                        <option value="<?= $state['abbr']; ?>"><?= $state['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('The state where the contract originated.', 'affiliate-ltp'); ?></p>
                </td>

            </tr>

            <tr class="form-row form-required">

                <th scope="row">
                    <label for="client_zip_address"><?php _e('Zipcode', 'affiliate-ltp'); ?></label>
                </th>

                <td>
                    <input class="medium-text" type="text" name="client_zip_address" id="client_zip_address" 
                           ng-readonly="commissionAdd.readonlyClient" ng-model="commissionAdd.client.zip"/>
                    <p class="description"><?php _e('The zipcode of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>
            </tr>

            <tr class="form-row form-required">
                <th scope="row">
                    <label for="client_phone"><?php _e('Phone', 'affiliate-ltp'); ?></label>
                </th>
                <td>
                    <input class="medium-text" type="text" name="client_phone" id="client_phone"
                           ng-readonly="commissionAdd.readonlyClient" ng-model="commissionAdd.client.phone"/>
                    <p class="description"><?php _e('The phone number of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>
            </tr>
            <tr class="form-row form-required">
                <th scope="row">
                    <label for="client_email"><?php _e('Email', 'affiliate-ltp'); ?></label>
                </th>
                <td>
                    <input class="medium-text" type="text" name="client_email" id="client_email"
                           ng-readonly="commissionAdd.readonlyClient" ng-model="commissionAdd.client.email"/>
                    <p class="description"><?php _e('The email of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
                </td>
            </tr>
        </table>
        
                <h3>Commission Information</h3>
        <table class="form-table">
                    
            <tr class="form-row form-required commission_row_single">

                <th scope="row">
                    <label for="user_name"><?php _e( 'Writing Agent', 'affiliate-ltp' ); ?></label>
                </th>

                <td>
                    <span class="affwp-ajax-search-wrap">
                        <input class="agent-name affwp-agent-search" 
                               ng-model="commissionAdd.commission.writing_agent.name"
                               ltp-agent-autocomplete="writing" type="text" name="agents[0][user_name]"
                               ng-disabled="commissionAdd.isRepeatBusiness()">
                    </span>
                    <p class="description"><?php _e( 'Enter the name of the affiliate or enter a partial name or email to perform a search.', 'affiliate-wp' ); ?></p>
                </td>

            </tr>
            
            <tr class="form-row form-required">

                <th scope="row">
                    <label for="cb_split_commission"><?php _e( 'Split Commission?', 'affiliate-ltp' ); ?></label>
                </th>

                <td>
                    <input type="checkbox" name="cb_split_commission" id="cb_split_commission" ng-checked="commissionAdd.isSplit()" ng-click="commissionAdd.toggleSplit()" />
                    <p class="description"><?php _e( 'If the commission is split up between two or more agents.', 'affiliate-ltp' ); ?></p>
                </td>
            </tr>

            <tr class="form-row form-required commission_row_multiple" ng-show="commissionAdd.isSplit()">

                <th scope="row">
                    <label><?php _e( 'Splits', 'affiliate-ltp' ); ?></label>
                </th>

                <td>
                    <input type="button" class="split-add" value="Add Split" ng-disabled="commissionAdd.isRepeatBusiness()" ng-click="commissionAdd.addSplit()" />
                    <table class="split-list">
                        <thead>
                            <th><?php _e( 'Agent', 'affiliate-ltp') ; ?></th>
                            <th><?php _e( 'Split %', 'affiliate-ltp') ; ?></th>
                            <th></th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <?php _e('Writing Agent', 'affiliate-ltp') ; ?>
                                </td>
                                <td>
                                    <input class="agent-split" type="number" step="1" max="100" 
                                           min="0" name="agents[0][agent_split]" ng-model="commissionAdd.commission.writing_agent.split"
                                           ng-disabled="commissionAdd.isRepeatBusiness()" />
                                </td>
                                <td>
                                </td>
                            </tr>
                            <tr ng-repeat="agent in commissionAdd.commission.split_agents track by $index">
                                <td><input type='text' ltp-agent-autocomplete="split" ltp-agent-autocomplete-index="{{$index}}" ng-model="agent.name"
                                           ng-disabled="commissionAdd.isRepeatBusiness()" /></td>
                                <td><input type="number" step="1" max="100" min="0" 
                                           ng-model="agent.split"
                                           ng-disabled="commissionAdd.isRepeatBusiness()" /></td>
                                <td><input type='button' class='remove-row' value='Remove' ng-click="commissionAdd.removeSplit(agent)"
                                           ng-disabled="commissionAdd.isRepeatBusiness()"/>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th><?php _e( 'Total Split %:', 'affiliate-ltp') ;?></th>
                                <th><span class="split-total" ng-class="{error: commissionAdd.isSplitTotalInvalid()}">{{commissionAdd.getSplitTotal()}}</span></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
				</td>
            </tr>

            <tr class="form-row form-required">

				<th scope="row">
					<label for="cb_is_life_commission"><?php _e( 'Life Insurance?', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
					<input type="checkbox" name="cb_is_life_commission" id="cb_is_life_commission" 
                                               ng-checked="commissionAdd.isLifePolicy()"
                                               ng-click="commissionAdd.toggleLifePolicy()"
                                               ng-disabled="commissionAdd.isRepeatBusiness()" />
					<p class="description"><?php _e( 'If the commission is for a life insurance policy.', 'affiliate-ltp' ); ?></p>
				</td>

			</tr>

            <tr class="form-row form-required life-commission-row" ng-show="commissionAdd.isLifePolicy()">

				<th scope="row">
					<label for="points"><?php _e( 'Points', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
					<input type="text" name="points" id="points" ng-model="commissionAdd.commission.points" />
					<p class="description"><?php _e( 'The points earned for this commission.', 'affiliate-ltp' ); ?></p>
				</td>

			</tr>      
                        
			<tr class="form-row form-required">

				<th scope="row">
					<label for="amount"><?php _e( 'Amount', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" name="amount" id="amount" ng-model="commissionAdd.commission.amount" />
					<p class="description"><?php _e( 'The amount of the referral, such as 15.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required">

				<th scope="row">
					<label for="amount"><?php _e( 'Date', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input type="text" ltp-date-picker
                                               ng-model="commissionAdd.commission.date"
                                               name="date" id="date" placeholder="<?php echo esc_attr( date_i18n( 'm/d/y', strtotime( 'today' ) ) ); ?>"/>
				</td>

			</tr>
                        <tr class="form-row form-required" ng-show="commissionAdd.isLifePolicy()">
				<th scope="row">
					<label for="haircut_percent"><?php _e( 'Company haircut percent', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
                                    <select ng-model="commissionAdd.commission.haircut_percent" id="haircut_percent"
                                            ng-options="haircut for haircut in commissionAdd.haircut_percent_list">
                                    </select>
                                    <p class="description"><?php _e( 'The amount of the haircut.', 'affiliate-ltp' ); ?></p>
				</td>
			</tr>
                        <tr class="form-row form-required" ng-hide="commissionAdd.isLifePolicy()">
				<th scope="row">
					<label for="haircut_percent"><?php _e( 'Company haircut percent', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
                                    {{commissionAdd.commission.haircut_percent}}
                                    <p class="description"><?php _e( 'The amount of the haircut.', 'affiliate-ltp' ); ?></p>
				</td>
			</tr>
                        
                        <tr class="form-row form-required">

				<th scope="row">
					<label for="cb_skip_company_haircut"><?php _e( 'Skip Company Haircut?', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
					<input type="checkbox" ng-model="commissionAdd.commission.skip_company_haircut" name="cb_skip_company_haircut" id="cb_skip_company_haircut" />
					<p class="description"><?php _e( 'If the company haircut should be skipped.', 'affiliate-ltp' ); ?></p>
				</td>

			</tr>
                        <tr class="form-row form-required">

				<th scope="row">
					<label for="cb_company_haircut_all"><?php _e( 'Give 100% Company Haircut?', 'affiliate-ltp' ); ?></label>
				</th>

				<td>
					<input type="checkbox" ng-model="commissionAdd.commission.company_haircut_all" name="cb_company_haircut_all" id="cb_company_haircut_all" />
					<p class="description"><?php _e( 'If the company haircut should be 100% of the commission.', 'affiliate-ltp' ); ?></p>
				</td>

			</tr>
		</table>
                
		<?php do_action( 'affwp_new_referral_bottom' ); ?>

		<?php echo wp_nonce_field( '', '' ); ?>
		<input ng-model="commissionAdd.nonce" type="hidden" ng-init="commissionAdd.nonce='<?php echo wp_create_nonce( 'affwp_add_referral_nonce' ); ?>'" />
                <input type="hidden" name="affwp_action" value="add_referral" />

                <input type="button" value="<?php _e( 'Add Referral', 'affiliate-wp' ); ?>" ng-click="commissionAdd.save()" />

	</form>

</div>