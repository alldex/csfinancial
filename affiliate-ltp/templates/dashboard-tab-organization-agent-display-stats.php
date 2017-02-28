<li class="statistics-row-category <?= $key ?>">
    <i class="fa <?= $data['icon'] ?>"></i>
    <span class='title'><?= $data['title'] ?></span>
    <i class="fa fa-chevron-down"></i>
    <ul class='statistics-row-category-items hidden'>
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