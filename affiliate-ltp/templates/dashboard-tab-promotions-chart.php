<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
?>
<div id="affwp-mlm-sub-affiliates-tree">
        <h4><?php echo __('Promotions', 'affiliate-ltp'); ?></h4>
        <?php $filter_widget->display(); ?>
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
        $count = 0;
        foreach ($nodes as $node) :
//            ob_start();
//            include 'dashboard-tab-organization-agent-display.php';
//            $sub_data = ob_get_clean();
            
            $life_licensed_class = $node['life_licensed'] === true ? 'life-licensed': '';
            $checklist_complete_class = $node['checklist_complete'] === true ? 'checklist-complete': '';
//            $life_licensed_class = 'life-licensed';
//            $checklist_complete_class = 'checklist-complete';
            //$sub_data = show_affiliate_data($node['id']);
            $sub_node = '<div class="sub_node ' . $node['status'] . ' affwp-mlm-aff">';
            $sub_node .= '<div class="affwp-mlm-aff-avatar">' . addslashes($node['avatar']) . '</div>';
            $sub_node .= '<span class="affwp-mlm-aff-name ' . $life_licensed_class . '">' 
                    . $node['name'] . " (" . $node['code'] . ")</span>";
            if (!empty($node['points'])) {
                $sub_node .= '<div class="points">'. __("Points", 'affiliate-ltp')
                    . " " . $node['points'] . '</div>';
            }
            //$sub_node .= preg_replace( "/\r|\n/", "", $sub_data );
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
                    google.visualization.events.addListener(chart, 'collapse', function() {
                        console.log("collapse",arguments);
                    });
                    google.visualization.events.addListener(chart, 'onmouseover', function() {
                        console.log("onmouseover",arguments);
                    });
                    // Draw the chart, setting the allowHtml option to true for the tooltips.
                    chart.draw(data, options);
                }
            </script>

            <div id="tree_wrap"></div>
</div>	
    <?php
    