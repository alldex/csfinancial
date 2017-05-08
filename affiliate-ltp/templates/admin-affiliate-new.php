<tr class="form-row">
        <th scope="row">
                <?php _e( 'Life License Number', 'affiliate-ltp' ); ?>
        </th>
        <td>
                <label for="life_license_number">
                <input type="text" name="life_license_number" id="life_license_number" />
                <?php _e( 'The license number authorizing an agent to sell life insurance.' ); ?>
                </label>

        </td>
</tr>
<tr class="form-row">
        <th scope="row">
                <?php _e( 'Life License Expiration Date', 'affiliate-ltp' ); ?>
        </th>
        <td>
                <label for="life_expiration_date">
                    <input type="text" name="life_expiration_date" id="life_expiration_date" class="affwp-datepicker" autocomplete="off" placeholder="<?php echo esc_attr( date_i18n( 'm/d/y', strtotime( 'today' ) ) ); ?>"/>
                    <?php _e( 'The expiration date of the licensing.' ); ?>
                </label>

        </td>
</tr>
<tr class="form-row">
        <th scope="row">
                <?php _e( 'Life License States', 'affiliate-ltp' ); ?>
        </th>
        <td>
            <?php foreach ($state_licenses as $item) : ?>
            <div>
                <label>
                        <input type="checkbox" name="life_license_state[]" value="<?= $item['abbr']; ?>" 
                               <?= ($item['licensed'] === true) ? "checked" : ""; ?>
                        />
                        <?= $item['name']; ?>
                </label>
            </div>
            <?php endforeach; ?>
            <?php _e( 'The states in which the agent is licensed to sell life insurance policies.', 'affiliate-ltp' ); ?>

        </td>
</tr
<tr class="form-row">
        <th scope="row">
                <?php _e( 'Co-Leadership Agent', 'affiliate-ltp' ); ?>
        </th>
        <td>
                <span class="affwp-ajax-search-wrap">
                        <input class="agent-name affwp-agent-search" type="text" name="coleadership_agent_username" data-affwp-status="active" autocomplete="off" />
                        <input class="agent-id" type="hidden" name="coleadership_user_id" value="" />
                    </span>
                    <p class="description"><?php _e( 'Enter the name of the affiliate or enter a partial name or email to perform a search.', 'affiliate-wp' ); ?></p>
        </td>
</tr>
<tr class="form-row">
        <th scope="row">
                <?php _e( 'Co-Leadership Ratio', 'affiliate-ltp' ); ?>
        </th>
        <td>
            <select name="coleadership_agent_rate">
                <option value="0"></option>
                <?php foreach ($coleadership_agent_rates as $rate => $name) : ?>
                <option value="<?= $rate ?>"><?= $name; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
</tr>
<tr class="form-row">
        <th scope="row">
                <?php _e( 'Phone', 'affiliate-ltp' ); ?>
        </th>
        <td>
                <label for="phone">
                <input type="text" name="phone" id="phone" value="" />
                <?php _e( 'The agent contact phone number.' ); ?>
                </label>

        </td>
</tr>
<?php