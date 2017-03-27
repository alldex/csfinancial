<div class="affwp-mlm-aff-avatar"><?php echo $node['avatar']; ?></div>
<span class="affwp-mlm-aff-name <?= $node['life_licensed'] === true ? 'life-licensed': ''; ?>"><?= $node['name'] ?></span>
<ul class="agent-data">
    <?php
    $stats = $node['statistics'];
    foreach ($stats as $key => $data) {
        include "dashboard-tab-organization-agent-display-stats.php";
    }
    ?>
    <?php
    if (!empty($node['checklist'])) {
        $checklist = $node['checklist'];
        include "dashboard-tab-organization-agent-display-checklist.php";
    }
    ?>
</ul>