<li class="<?= $key ?>">
    <i class="fa <?= $data['icon'] ?>"></i>
    <span><?= $data['title'] ?></span>
    <ul>
        <?php
        foreach ($data['content'] as $content_key => $content_data) {
            $content_key = str_replace("_", " ", $content_key);
            $content_key = ucwords($content_key);
            ?>
            <li>
                <strong><?= $content_key ?></strong>
                <span><?= $content_data ?> </span>
            </li>
        <?php } ?>
    </ul>
</li>