(function($) {
    
    /**
     * Clears up the search and resets all the fields
     * @returns
     */
    function resetClientSearch() {
        $('.affwp-client-search').val('');
        $( '#client_id' ).val("");

        var items = [
            "client_name"
            ,"client_street_address"
            ,"client_city_address"
            ,"client_zip_address"
            ,"client_phone"
            ,"client_email"
        ];
        items.forEach(function(item) {
            $('#' + item).prop("readonly", false)
                    .val("");
        });
        
        $('.readonly-description').addClass('hidden');
    }
    function addSplitRow(evt, agentRate) {
        if (!agentRate) {
            agentRate = 0;
        }
        
        var splitRow = [];
           splitRow.push("<tr>");
           
           // agent search
           splitRow.push("<td>");
           splitRow.push("<span class='affwp-ajax-search-wrap'>");
           splitRow.push("<input class='agent_name' type='text' name='agents[]['user_name'] class='affwp-user-search' data-affwp-status='active' autocomplete='off' />");
           splitRow.push("<input class='agent_id' type='hidden' name='agents[]['user_id'] value='' />");
           splitRow.push("</span>");
           splitRow.push("</td>");
           
           // rate
           splitRow.push("<td>");
           splitRow.push("<input class='agent_rate' type='text' name='agents[]['agent_rate'] value='" + agentRate + "' />");
           splitRow.push("</td>");
           
           // actions
           splitRow.push("<td>");
           splitRow.push("<input type='button' class='remove-row' value='Remove' />");
           splitRow.push("</td>");
           
           splitRow.push("</tr>");
           $("#affwp_add_referral .split-list tbody").append(splitRow.join(""));
           $("#affwp_add_referral .split-list tbody tr:last-child .remove-row").click(function removeSplitRow() {
               $(this).closest("tr").remove();
           });
    }
    function setupAddReferralScreen() {
        var firstTimeSplitShown = true;
        // hide the different pieces of the site based on the check value.
        $( '#affwp_add_referral #cb_split_commission' ).click(function() {
            $('#affwp_add_referral .commission_row_single').toggleClass('hidden');
            $('#affwp_add_referral .commission_row_multiple').toggleClass('hidden');
            
            // add a row with the default being zero
            if (firstTimeSplitShown) {
                firstTimeSplitShown = false;
                addSplitRow({}, 50);
                addSplitRow({}, 50);
            }
        });
        
        $( '#affwp_add_referral .split-add').click(addSplitRow);
        
    }
    $(document).ready(function() {
        setupAddReferralScreen();
        
       $( '.affwp-client-search-reset').click(resetClientSearch);
       
       $( '.affwp-client-search' ).each( function() {
		var	$this    = $( this ),
			$action  = 'affwp_ltp_search_clients',
			$search  = $this.val(),
			$client_id = $( '#client_id' );

		$this.autocomplete( {
			source: ajaxurl + '?action=' + $action + '&term=' + $search,
			delay: 500,
			minLength: 2,
			position: { offset: '0, -1' },
			select: function( event, data ) {
				$client_id.val( data.item.client_id );
                                $('.readonly-description').removeClass('hidden');
                                $('#client_name')
                                        .prop("readonly", true)
                                        .val(data.item.name);
                                $('#client_street_address')
                                        .prop("readonly", true)
                                        .val(data.item.street_address);
                                $('#client_city_address')
                                        .prop("readonly", true)
                                        .val(data.item.city);
                                $('#client_zip_address')
                                        .prop("readonly", true)
                                        .val(data.item.zip);
                                $('#client_phone')
                                        .prop("readonly", true)
                                        .val(data.item.phone);
                                $('#client_email').prop("readonly", true)
                                        .val(data.item.email);
			},
			open: function() {
				$this.addClass( 'open' );
			},
			close: function() {
				$this.removeClass( 'open' );
			}
		} );

		// Unset the user_id input if the input is cleared.
		$this.on( 'keyup', function() {
			if ( ! this.value ) {
                            resetClientSearch();
			}
		} );
	} ); 
    });
})(jQuery);