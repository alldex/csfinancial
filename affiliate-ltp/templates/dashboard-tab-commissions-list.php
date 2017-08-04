

<h4><?php _e('Commissions', 'affiliate-ltp'); ?></h4>
<table id="affwp-affiliate-dashboard-referrals" class="affwp-table">
    <thead>
        <tr>
            <th class="commission-policy"><a href="<?= $sort_links['reference']['link']; ?>"><?php _e('Policy Number', 'affiliate-ltp'); ?></a>
            <?php if ($sort == 'reference') : ?>
                <?php if ($sort_links['reference']['sort_order'] == 'ASC') : ?>
                <i class="fa fa-sort-asc" aria-hidden="true"></i>    
                <?php elseif ($sort_links['reference']['sort_order'] == 'DESC') : ?>
                <i class="fa fa-sort-desc" aria-hidden="true"></i>
                <?php endif; ?>
            <?php endif; ?>
            </th>
            <th class="commission-client"><a href="<?= $sort_links['client_name']['link']; ?>"><?php _e('Client Name', 'affiliate-ltp'); ?></a>
            <?php if ($sort == 'client_name') : ?>
                <?php if ($sort_links['client_name']['sort_order'] == 'ASC') : ?>
                <i class="fa fa-sort-asc" aria-hidden="true"></i>    
                <?php elseif ($sort_links['client_name']['sort_order'] == 'DESC') : ?>
                <i class="fa fa-sort-desc" aria-hidden="true"></i>
                <?php endif; ?>
            <?php endif; ?>
            </th>
            <th class="commission-amount"><?php _e('Amount', 'affiliate-wp'); ?></th>
            <th class="commission-description"><?php _e('Description', 'affiliate-wp'); ?></th>
            <th class="commission-status"><?php _e('Status', 'affiliate-wp'); ?></th>
            <th class="commission-date"><a href="<?= $sort_links['date']['link']; ?>"><?php _e('Date', 'affiliate-wp'); ?></a>
            <?php if ($sort == 'date') : ?>
                <?php if ($sort_links['date']['sort_order'] == 'ASC') : ?>
                <i class="fa fa-sort-asc" aria-hidden="true"></i>    
                <?php elseif ($sort_links['date']['sort_order'] == 'DESC') : ?>
                <i class="fa fa-sort-desc" aria-hidden="true"></i>
                <?php endif; ?>
            <?php endif; ?>
            </th>
        </tr>
    </thead>

    <tbody>
        <?php if ($commissions) : ?>

            <?php foreach ($commissions as $commission) : ?>
                <tr>
                    <td class="commission-policy"><?= $commission->reference; ?></td>
                    <td class="commission-client"><?= $commission->client_name; ?></td>
                    <td class="commission-amount"><?php echo affwp_currency_filter(affwp_format_amount($commission->amount)); ?></td>
                    <td class="commission-description"><?php echo wp_kses_post(nl2br($commission->description)); ?></td>
                    <td class="commission-status <?php echo $commission->status; ?>"><?= $commission->status; ?></td>
                    <td class="commission-date"><?php echo date_i18n(get_option('date_format'), strtotime($commission->date)); ?></td>
                </tr>
            <?php endforeach; ?>

        <?php else : ?>

            <tr>
                <td colspan="4"><?php _e('You have not made any commissions yet.', 'affiliate-ltp'); ?></td>
            </tr>

        <?php endif; ?>
    </tbody>
</table>

<?php if ($has_pagination) : ?>

    <p class="affwp-pagination">
        <?php
        echo $pagination;
        ?>
    </p>

<?php endif; ?>
