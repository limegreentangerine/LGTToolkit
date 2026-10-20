<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php
$editing = $editing ?? null;
$formContent = $formContent ?? [];
$isEditing = $editing !== null;

$sourceValue = $formContent['source'] ?? ($isEditing ? $editing->getSourcePath() : '');
$destinationValue = $formContent['destination'] ?? ($isEditing ? $editing->getPageID() : '');
$codeValue = $formContent['code'] ?? ($isEditing ? $editing->getStatusCode() : $defaultStatus);
$listUrl = (string) \URL::to('/dashboard/lgt_toolkit/redirections');
?>

<?php if (isset($importReport)) { ?>
    <div class="alert alert-info">
        <strong><?php echo t2('%d redirection imported.', '%d redirections imported.', $importReport['created'], $importReport['created']); ?></strong>
        <?php if ($importReport['errors'] !== []) { ?>
            <?php echo t2('%d row was skipped:', '%d rows were skipped:', count($importReport['errors']), count($importReport['errors'])); ?>
            <ul class="mb-0 mt-2">
                <?php foreach ($importReport['errors'] as $rowError) { ?>
                    <li>
                        <?php if ($rowError['line'] > 0) { ?><?php echo t('Line %s', (int) $rowError['line']); ?>
                            <?php if ($rowError['source'] !== '') { ?>(<code><?php echo h($rowError['source']); ?></code>)<?php } ?>:
                        <?php } ?>
                        <?php echo h($rowError['message']); ?>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
<?php } ?>

<form method="post" action="<?php echo $isEditing ? $view->action('update', $editing->getID()) : $view->action('add'); ?>">
    <?php echo $token->output($isEditing ? 'lgt_redirections_update' : 'lgt_redirections_add'); ?>

    <fieldset>
        <legend><?php echo $isEditing ? t('Edit Redirection') : t('Add Redirection'); ?></legend>

        <div class="form-group">
            <?php echo $form->label('source', t('Old URL')); ?>
            <?php echo $form->text('source', $sourceValue, ['placeholder' => '/old-page']); ?>
            <div class="form-text"><?php echo t('A path on this site (or a full URL on this domain). Any query string is ignored when matching.'); ?></div>
        </div>

        <div class="form-group">
            <?php echo $form->label('destination', t('Redirect To')); ?>
            <?php echo $form_page_selector->selectPage('destination', $destinationValue ?: false); ?>
        </div>

        <div class="form-group">
            <?php echo $form->label('code', t('Redirect Type')); ?>
            <?php echo $form->select('code', $statusOptions, (string) $codeValue); ?>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary"><?php echo $isEditing ? t('Save') : t('Add Redirection'); ?></button>
            <?php if ($isEditing) { ?>
                <a href="<?php echo $listUrl; ?>" class="btn btn-secondary"><?php echo t('Cancel'); ?></a>
            <?php } ?>
        </div>
    </fieldset>
</form>

<?php if (!$isEditing) { ?>
    <form method="post" action="<?php echo $view->action('import'); ?>" enctype="multipart/form-data">
        <?php echo $token->output('lgt_redirections_import'); ?>

        <fieldset>
            <legend><?php echo t('Import from CSV'); ?></legend>

            <p class="text-muted">
                <?php echo t('Columns: old URL, destination page path, and an optional code (301 or 302, defaulting to 302). Rows that clash with an existing page or redirection are skipped and reported.'); ?>
                <a href="<?php echo $view->action('sample'); ?>"><?php echo t('Download a sample'); ?></a>
            </p>

            <div class="form-group">
                <input type="file" name="csv" accept=".csv,text/csv" class="form-control" />
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-secondary"><?php echo t('Upload and Import'); ?></button>
            </div>
        </fieldset>
    </form>

    <fieldset>
        <legend><?php echo t('Redirections'); ?></legend>

        <form method="get" action="<?php echo $listUrl; ?>" class="row g-2 mb-3">
            <div class="col-auto">
                <input type="search" name="source" value="<?php echo h($filterSource); ?>" class="form-control" placeholder="<?php echo h(t('Filter by old URL...')); ?>" />
            </div>
            <div class="col-auto">
                <?php echo $form->select('code', ['' => t('Any type')] + $statusOptions, $filterCode); ?>
            </div>
            <div class="col-auto">
                <?php echo $form->select('num_results', $numResultsOptions, $numResults); ?>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-secondary"><?php echo t('Filter'); ?></button>
            </div>
        </form>

        <?php if (empty($items)) { ?>
            <p class="text-muted"><?php echo t('No redirections found.'); ?></p>
        <?php } else { ?>
            <form method="post" action="<?php echo $view->action('bulk_delete'); ?>" id="lgt-redirections-list">
                <?php echo $token->output('lgt_redirections_delete'); ?>

                <table class="table table-striped ccm-search-results-table">
                    <thead>
                        <tr>
                            <th style="width: 2rem;"><input type="checkbox" id="lgt-redirections-all" aria-label="<?php echo h(t('Select all')); ?>" /></th>
                            <?php foreach ($result->getColumns() as $column) { ?>
                                <?php if ($column->isColumnSortable()) { ?>
                                    <th class="<?php echo $column->getColumnStyleClass(); ?>">
                                        <a href="<?php echo $column->getColumnSortURL(); ?>"><?php echo h($column->getColumnTitle()); ?></a>
                                    </th>
                                <?php } else { ?>
                                    <th><span><?php echo h($column->getColumnTitle()); ?></span></th>
                                <?php } ?>
                            <?php } ?>
                            <th><?php echo t('Redirects To'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item) { ?>
                            <?php $destination = $item->getDestination(); ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?php echo h($item->getID()); ?>" /></td>
                                <?php foreach ($item->getColumns() as $column) { ?>
                                    <td><?php echo h($column->getColumnValue()); ?></td>
                                <?php } ?>
                                <td>
                                    <?php if ($destination) { ?>
                                        <a href="<?php echo h($destination->getCollectionLink()); ?>" target="_blank" rel="noopener"><?php echo h($destination->getCollectionName()); ?></a>
                                    <?php } else { ?>
                                        <span class="badge bg-danger text-bg-danger"><?php echo t('Broken'); ?></span>
                                        <span class="text-muted"><?php echo t('Destination page was deleted or unpublished. This redirect is inactive.'); ?></span>
                                    <?php } ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo h($item->getViewUrl()); ?>" class="btn btn-sm btn-secondary"><?php echo t('Edit'); ?></a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <button type="submit" class="btn btn-danger"><?php echo t('Delete Selected'); ?></button>
            </form>

            <?php if ($pagination) { ?>
                <div class="ccm-search-results-pagination mt-3">
                    <?php echo $pagination; ?>
                </div>
            <?php } ?>
        <?php } ?>
    </fieldset>

    <script>
        (function () {
            var list = document.getElementById('lgt-redirections-list');
            if (!list) return;

            var all = document.getElementById('lgt-redirections-all');
            var boxes = list.querySelectorAll('tbody input[type=checkbox]');

            all.addEventListener('change', function () {
                boxes.forEach(function (box) {
                    box.checked = all.checked;
                });
            });

            list.addEventListener('submit', function (e) {
                if (!list.querySelector('input[name="ids[]"]:checked') || !confirm(<?php echo json_encode(t('Delete the selected redirections?')); ?>)) {
                    e.preventDefault();
                }
            });
        })();
    </script>
<?php } ?>
