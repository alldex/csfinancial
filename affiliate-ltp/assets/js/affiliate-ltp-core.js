/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
(function (jQuery, window, ajaxurl) {
    window.ltpjs = window.ltpjs || {};
    
    function setupAgentSearch(selector, action) {
        var $this = jQuery(selector),
                $action = action,
                $search = $this.val(),
                $status = $this.data('affwp-status'),
                $agent_id = $this.siblings(".agent-id");

        $this.autocomplete({
            source: ajaxurl + '?action=' + $action + '&status=' + $status,
            delay: 500,
            minLength: 2,
            position: {offset: '0, -1'},
            select: function (event, data) {
                $agent_id.val(data.item.user_id);
            },
            open: function () {
                $this.addClass('open');
            },
            close: function () {
                $this.removeClass('open');
            }
        });

        // Unset the user_id input if the input is cleared.
        $this.on('keyup', function () {
            if (!this.value) {
                $agent_id.val('');
            }
        });
    }
    
    // setup our base shop filter.
    function update_super_base_shop_checkbox() {
         if (jQuery("#affwp_ltp_show_super_base_shop").prop("checked")) {
          jQuery("#affwp_ltp_include_super_base_shop").val('Y');
       }
       else {
           jQuery("#affwp_ltp_include_super_base_shop").val('N');
       }
    }
    
    function updateProgressItem() {
        
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
    }
    
    jQuery(document).ready(function() { 
        setupAgentSearch(".ginput_container .affwp-agent-search", 'affwp_ltp_search_partners');

        setupAgentSearch(".affwp-tab-content .affwp-agent-search", 'affwp_ltp_search_agents');
        jQuery("#affwp-graphs-filter").append("<input type='hidden' name='affwp_ltp_include_super_base_shop' id='affwp_ltp_include_super_base_shop'  value='N' />");
        update_super_base_shop_checkbox();
        jQuery("#affwp_ltp_show_super_base_shop").click(function() {
            update_super_base_shop_checkbox();
            jQuery("#affwp-graphs-filter").submit();
        });
        jQuery(".statistics-row-category-items .progress-item").click(updateProgressItem); 
        // set default active to be false so they are all collapsed.
        jQuery(".events-accordion").accordion({collapsible: true, heightStyle: "content", active:false});
    });
    
    // handle the exports
    window.ltpjs.setupAgentSearch = setupAgentSearch;
    
})(jQuery, window, wp_ajax_object.ajaxurl);