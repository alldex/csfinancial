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
            $('#' + item).prop("disabled", false)
                    .val("");
        });
        
        $('.readonly-description').addClass('hidden');
    }
    $(document).ready(function() {
       $( '.affwp-client-search-reset').click(resetClientSearch);
       
       $( '.affwp-client-search' ).each( function() {
		var	$this    = $( this ),
			$action  = 'affwp_ltp_search_clients',
			$search  = $this.val(),
			$user_id = $( '#client_id' );

		$this.autocomplete( {
			source: ajaxurl + '?action=' + $action + '&term=' + $search,
			delay: 500,
			minLength: 2,
			position: { offset: '0, -1' },
			select: function( event, data ) {
				$user_id.val( data.item.user_id );
                                $('.readonly-description').removeClass('hidden');
                                $('#client_name')
                                        .prop("disabled", true)
                                        .val(data.item.name);
                                $('#client_street_address')
                                        .prop("disabled", true)
                                        .val(data.item.street_address);
                                $('#client_city_address')
                                        .prop("disabled", true)
                                        .val(data.item.city);
                                $('#client_zip_address')
                                        .prop("disabled", true)
                                        .val(data.item.zip);
                                $('#client_phone')
                                        .prop("disabled", true)
                                        .val(data.item.phone);
                                $('#client_email').prop("disabled", true)
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