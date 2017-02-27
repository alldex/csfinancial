<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
?>
<div id="affwp-mlm-sub-affiliates-tree">
    <?php if ($is_parent) : ?>
        <?php if ($show_controls) : ?>
    <form method="POST">   
        <input type="checkbox" id="affiliate_ltp_show_partners" name="affiliate_ltp_show_partners" value="1"
               <?php if ($show_partners_checked) : ?>
               checked="checked"
               <?php endif; ?>/>
        <label for="affiliate_ltp_show_partners"><?php _e("Show partners", "affiliate-ltp"); ?></label>
        <input type="submit" value="Filter" />
    </form>
        <?php endif; ?>
    

        <h4><?php echo __('Sub Affiliates', 'affiliatewp-multi-level-marketing'); ?></h4>
        <?php if ($has_sub_agents) {  ?>
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
            <script type="text/javascript">
                google.charts.load('current', {packages: ["orgchart"]});
                google.charts.setOnLoadCallback(drawChart);

                function drawChart() {
                    var data = new google.visualization.DataTable();
                    data.addColumn('string', 'Affiliate Name');
                    data.addColumn('string', 'Parent Affiliate');
                    data.addColumn('string', 'ToolTip');

                    // For each orgchart node, provide the name, parent, and tooltip to show.
                    data.addRows([
        <?php
        foreach ($nodes as $node) :

            $sub_data = show_affiliate_data($node['id']);
            $sub_avatar = addslashes(get_avatar($node['user_id']));
            $sub_node = '<div class="sub_node ' . $node['status'] . ' affwp-mlm-aff">';
            $sub_node .= '<div class="affwp-mlm-aff-avatar">' . $sub_avatar . '</div>';
            $sub_node .= '<span class="affwp-mlm-aff-name">' . $node['name'] . '</span>';
            $sub_node .= $sub_data;
            $sub_node .= '</div>';

            $tooltip = 'Affiliate ID: ' . $sub_id;
            ?>

                            [{v: '<?php echo $node['name']; ?>', f: '<?php echo $sub_node; ?>'}, '<?php echo $node['parent_name']; ?>', '<?php echo $tooltip; ?>'],
        <?php endforeach; ?>

                    ]);

                    var options = {
                        allowHtml: true,
                        allowCollapse: true,
                        size: 'medium',
                        nodeClass: 'sub_affiliate_node',
                        selectedNodeClass: 'sub_affiliate_node_selected'
                    };

                    // Create the chart.
                    var chart = new google.visualization.OrgChart(document.getElementById('tree_wrap'));
                    // example event handling.
//                    google.visualization.events.addListener(chart, 'collapse', function() {
//                        console.log(arguments);
//                    });
//                    google.visualization.events.addListener(chart, 'onmouseover', function() {
//                        console.log(arguments);
//                    });
                    // Draw the chart, setting the allowHtml option to true for the tooltips.
                    chart.draw(data, options);
                }
            </script>

            <div id="tree_wrap"></div>

    <?php } ?>


    <?php else : ?>

        <h4><?php _e('No Sub Affiliates yet.', 'affiliatewp-multi-level-marketing'); ?></h4>

    <?php if ($allow_affiliate_registration) : ?>
            <p><?php _e('When a user registers as an affiliate using your referral URL they will become your sub-affiliate.', 'affiliatewp-multi-level-marketing'); ?></p>
        <?php endif; ?>

    <?php endif; ?>

</div>	
    <?php
    