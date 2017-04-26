<tr class="form-row">
        <th scope="row">
                <?php _e( 'Life License Number', 'affiliate-ltp' ); ?>
        </th>
        <td>
                <label for="life_license_number">
                <input type="text" name="life_license_number" id="life_license_number" value="<?php echo $licenseNumber; ?>" />
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
                    <input type="text" name="life_expiration_date" id="life_expiration_date" class="affwp-datepicker" autocomplete="off" value="<?php echo $expirationDate; ?>"/>
                    <?php _e( 'The expiration date of the licensing.' ); ?>
                </label>

        </td>
</tr
<tr class="form-row">
        <th scope="row">
                <?php _e( 'Co-Leadership Agent', 'affiliate-ltp' ); ?>
        </th>
        <td>
                <span class="affwp-ajax-search-wrap">
                        <input class="agent-name affwp-agent-search" type="text" name="coleadership_agent_username" data-affwp-status="active" autocomplete="off" value="<?= $coleadership_username; ?>" />
                        <input class="agent-id" type="hidden" name="coleadership_user_id" value="<?= $coleadership_user_id; ?>" />
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
                <option value="<?= $rate ?>"
                        <?php if ($rate == $coleadership_agent_rate) : ?>
                        selected="selected"
                        <?php endif; ?>
                        ><?= $name; ?></option>
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
                <input type="text" name="phone" id="phone" value="<?php echo $phone; ?>" />
                <?php _e( 'The agent contact phone number.' ); ?>
                </label>

        </td>
</tr>
<?php