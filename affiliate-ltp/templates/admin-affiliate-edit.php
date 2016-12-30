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
</tr>
<?php