<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php
$listUrl = (string) \URL::to('/dashboard/support/tickets');
// Stored URLs are validated when written; this keeps a tampered row from rendering a javascript: link.
$safeUrl = static fn(?string $url): ?string => $url !== null && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null;
$badgeClass = static fn(string $status): string => match ($status) {
    'delivered' => 'bg-success',
    'queued' => 'bg-warning text-dark',
    default => 'bg-danger',
};
?>

<?php if (isset($ticket)) { ?>
    <?php /** @var Concrete\Package\LgtToolkit\Entity\SupportTicket $ticket */ ?>
    <p><a href="<?php echo h($listUrl); ?>">&larr; <?php echo t('All tickets'); ?></a></p>

    <h3 class="mb-3">
        <?php echo h($ticket->getSubject()); ?>
        <span class="badge <?php echo $badgeClass($ticket->getStatus()); ?> align-middle fs-6"><?php echo h($ticket->getStatusLabel()); ?></span>
    </h3>

    <dl class="row">
        <dt class="col-sm-3"><?php echo t('Portal ticket'); ?></dt>
        <dd class="col-sm-9">
            <?php if ($portalUrl = $safeUrl($ticket->getPortalUrl())) { ?>
                <a href="<?php echo h($portalUrl); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo h($ticket->getPortalReference() ?: t('View in portal')); ?>
                    <span class="visually-hidden"><?php echo t('(opens in a new tab)'); ?></span>
                </a>
            <?php } else { ?>
                <?php echo h($ticket->getPortalReference() ?: t('Not delivered yet')); ?>
            <?php } ?>
        </dd>

        <dt class="col-sm-3"><?php echo t('Local reference'); ?></dt>
        <dd class="col-sm-9"><code><?php echo h($ticket->getLocalReference()); ?></code></dd>

        <dt class="col-sm-3"><?php echo t('Submitted'); ?></dt>
        <dd class="col-sm-9"><?php echo h($dateHelper->formatDateTime($ticket->getDateCreated())); ?></dd>

        <?php if ($ticket->getDeliveredAt()) { ?>
            <dt class="col-sm-3"><?php echo t('Delivered'); ?></dt>
            <dd class="col-sm-9"><?php echo h($dateHelper->formatDateTime($ticket->getDeliveredAt())); ?></dd>
        <?php } ?>

        <dt class="col-sm-3"><?php echo t('Category'); ?></dt>
        <dd class="col-sm-9"><?php echo h($ticket->getCategoryLabel()); ?></dd>

        <dt class="col-sm-3"><?php echo t('From'); ?></dt>
        <dd class="col-sm-9">
            <?php echo h((string) $ticket->getName()); ?>
            <a href="mailto:<?php echo h($ticket->getEmail()); ?>"><?php echo h($ticket->getEmail()); ?></a>
            <?php if ($ticket->getUserID()) { ?>
                <span class="text-muted">(<?php echo t('user ID %s', (int) $ticket->getUserID()); ?>)</span>
            <?php } ?>
        </dd>

        <dt class="col-sm-3"><?php echo t('Page'); ?></dt>
        <dd class="col-sm-9">
            <?php if ($pageUrl = $safeUrl($ticket->getPageUrl())) { ?>
                <a href="<?php echo h($pageUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo h($pageUrl); ?></a>
            <?php } elseif ($ticket->getPageUrl()) { ?>
                <?php echo h($ticket->getPageUrl()); ?>
            <?php } else { ?>
                <span class="text-muted"><?php echo t('Not specified'); ?></span>
            <?php } ?>
        </dd>

        <?php if ($ticket->getStatus() !== 'delivered' && $ticket->getLastError()) { ?>
            <dt class="col-sm-3"><?php echo t('Last error'); ?></dt>
            <dd class="col-sm-9 text-danger"><?php echo h($ticket->getLastError()); ?></dd>
        <?php } ?>
    </dl>

    <h4><?php echo t('Message'); ?></h4>
    <pre class="border rounded p-3" style="white-space: pre-wrap;"><?php echo h($ticket->getMessage()); ?></pre>
<?php } else { ?>
    <p class="text-muted">
        <?php echo t('Every ticket submitted through this site in the last 12 months. Older records are removed by the scheduled task.'); ?>
    </p>

    <form method="get" action="<?php echo h($listUrl); ?>" class="row g-2 mb-3">
        <div class="col-auto">
            <label class="visually-hidden" for="keywords"><?php echo t('Search'); ?></label>
            <input type="search" id="keywords" name="keywords" value="<?php echo h($filterKeywords); ?>" class="form-control" placeholder="<?php echo h(t('Reference, subject or sender...')); ?>" />
        </div>
        <div class="col-auto">
            <label class="visually-hidden" for="status"><?php echo t('Status'); ?></label>
            <?php echo $form->select('status', ['' => t('Any status')] + $statusOptions, $filterStatus); ?>
        </div>
        <div class="col-auto">
            <label class="visually-hidden" for="category"><?php echo t('Category'); ?></label>
            <?php echo $form->select('category', ['' => t('Any category')] + $categoryOptions, $filterCategory); ?>
        </div>
        <div class="col-auto">
            <label class="visually-hidden" for="num_results"><?php echo t('Results per page'); ?></label>
            <?php echo $form->select('num_results', $numResultsOptions, $numResults); ?>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-secondary"><?php echo t('Filter'); ?></button>
        </div>
    </form>

    <?php if (empty($items)) { ?>
        <p class="text-muted"><?php echo t('No tickets found.'); ?></p>
    <?php } else { ?>
        <table class="table table-striped ccm-search-results-table">
            <thead>
                <tr>
                    <?php foreach ($result->getColumns() as $column) { ?>
                        <?php if ($column->isColumnSortable()) { ?>
                            <th class="<?php echo $column->getColumnStyleClass(); ?>">
                                <a href="<?php echo $column->getColumnSortURL(); ?>"><?php echo h($column->getColumnTitle()); ?></a>
                            </th>
                        <?php } else { ?>
                            <th><span><?php echo h($column->getColumnTitle()); ?></span></th>
                        <?php } ?>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) { ?>
                    <?php $url = h((string) $item->getViewUrl()); ?>
                    <tr data-details-url="<?php echo $url; ?>" style="cursor: pointer;">
                        <?php $first = true; ?>
                        <?php foreach ($item->getColumns() as $column) { ?>
                            <td>
                                <?php if ($first) { ?>
                                    <?php $first = false; ?>
                                    <a href="<?php echo $url; ?>"><?php echo h($column->getColumnValue()); ?></a>
                                <?php } elseif ($column->getColumnKey() === 't.status') { ?>
                                    <span class="badge <?php echo $badgeClass($item->getStatus()); ?>"><?php echo h($column->getColumnValue()); ?></span>
                                <?php } else { ?>
                                    <?php echo h($column->getColumnValue()); ?>
                                <?php } ?>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if ($pagination) { ?>
            <div class="ccm-search-results-pagination mt-3">
                <?php echo $pagination; ?>
            </div>
        <?php } ?>

        <script>
            document.querySelectorAll('tr[data-details-url]').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (!e.target.closest('a')) {
                        window.location = row.dataset.detailsUrl;
                    }
                });
            });
        </script>
    <?php } ?>
<?php } ?>
