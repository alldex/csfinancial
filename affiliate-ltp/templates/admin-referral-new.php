<h3>Client Information</h3>
<table class="form-table">


    <tr class="form-row form-required">

        <th scope="row">
            <label for="client_name"><?php _e('Contract Number', 'affiliate-ltp'); ?></label>
        </th>

        <td>
            <span class="affwp-ajax-search-wrap">
                    <input type="text" name="client_name" id="client_contract_number" class="affwp-client-search" autocomplete="off" />
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
            <textarea class="medium-text" name="client_name" id="client_name"></textarea>
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