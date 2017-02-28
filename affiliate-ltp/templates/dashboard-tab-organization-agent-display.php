<div class="affwp-mlm-aff-avatar"><?php echo get_avatar($node['user_id']); ?></div>
<span class="affwp-mlm-aff-name"><?= $node['name'] ?></span>
<ul class="agent-data">
    <?php
    $stats = $node['statistics'];
    foreach ($stats as $key => $data) {
        include "dashboard-tab-organization-agent-display-stats.php";
    }
    ?>
    <?php
    $checklist = $node['checklist'];
    include "dashboard-tab-organization-agent-display-checklist.php";
    ?>
</ul>