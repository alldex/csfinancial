<table class="form-table">


    <tr class="form-row form-required">

        <th scope="row">
            <label for="client_contract_number"><?php _e('Contract Number', 'affiliate-ltp'); ?></label>
        </th>

        <td>
            <input class="medium-text" type="text" name="client_contract_number" id="client_contract_number" value="<?php echo esc_attr($client["contract_number"]); ?>" disabled="disabled"/>
            <p class="description"><?php _e('The unique contract number of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
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
            <label for="client_address"><?php _e('Client Address', 'affiliate-ltp'); ?></label>
        </th>

        <td>
            <textarea class="medium-text" name="client_address" id="client_address" disabled="disabled"><?php echo esc_attr($client["address"]); ?></textarea>
            <p class="description"><?php _e('The address of the Client this commission belongs to.', 'affiliate-ltp'); ?></p>
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