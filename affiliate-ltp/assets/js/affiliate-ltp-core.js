/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
jQuery(document).ready(function() {
    // setup our base shop filter.
    function update_super_base_shop_checkbox() {
         if (jQuery("#affwp_ltp_show_super_base_shop").prop("checked")) {
          jQuery("#affwp_ltp_include_super_base_shop").val('Y');
       }
       else {
           jQuery("#affwp_ltp_include_super_base_shop").val('N');
       }
    }
   jQuery("#affwp-graphs-filter").append("<input type='hidden' name='affwp_ltp_include_super_base_shop' id='affwp_ltp_include_super_base_shop'  value='N' />");
   update_super_base_shop_checkbox();
   jQuery("#affwp_ltp_show_super_base_shop").click(function() {
       update_super_base_shop_checkbox();
       jQuery("#affwp-graphs-filter").submit();
   });
   jQuery(".statistics-row-category-items .progress-item").click(function() {
       var item = jQuery(this);
       
       // do nothing if the item can't be changed.  Someone could try to change
       // this, but the server side prevents changes as well.
       if (item.attr('readonly') === 'readonly') {
           return;
       }
       
       var action = item.closest(".checklist").data("action");
       var completed = 0;
       if (item.attr("checked") === "checked") {
           completed = 1;
       }
       var status = item.parent().find(".status");
       
       var post_data = {
           "action": action
           ,"agent_id": item.data("agent-id")
           ,"progress_item_admin_id": item.data("id")
           ,"completed": completed
       };
       /*
        * $action  = 'affwp_ltp_search_clients',
			$search  = $this.val(),
			$client_id = $( '#client_id' );

		$this.autocomplete( {
			source: ajaxurl + '?action=' + $action + '&term=' + $search,
        */
       // wp_ajax_object comes from wp_localize_script
       status.text("Saving...");
       jQuery.post(wp_ajax_object.ajaxurl, post_data, function() {
           if (completed === 1) {
            status.text(new Date().toDateString());
           }
           else {
               status.text("Not started");
           }
       });
   }) 
});