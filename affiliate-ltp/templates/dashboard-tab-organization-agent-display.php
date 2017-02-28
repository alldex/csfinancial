<ul class="affwp-mlm-aff-data">
    <?php
    $stats = $node['statistics'];
    foreach ($stats as $key => $data) {
        include "dashboard-tab-organization-agent-display-stats.php";
    }
    ?>
    <li>
        <?php
        $checklist = $node['checklist'];
        include "dashboard-tab-organization-agent-display-checklist.php";
        ?>
    </li>
</ul>