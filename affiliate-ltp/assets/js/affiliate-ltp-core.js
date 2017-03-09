/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
jQuery(document).ready(function() {
   jQuery(".statistics-row-category-items .progress-item").click(function() {
       var item = jQuery(this);
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