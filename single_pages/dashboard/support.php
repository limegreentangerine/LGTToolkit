<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php
$field = static function (string $name) use ($fieldErrors): array {
    $error = $fieldErrors[$name] ?? null;

    return [
        'class' => $error !== null ? ' is-invalid' : '',
        'aria' => $error !== null ? ' aria-invalid="true" aria-describedby="' . $name . '-error"' : '',
        'error' => $error !== null ? '<div class="invalid-feedback d-block" id="' . $name . '-error">' . h($error) . '</div>' : '',
    ];
};
$value = static fn(string $name): string => h((string) ($values[$name] ?? ''));
$mb = static fn(int $bytes): string => number_format($bytes / 1048576, 1);
?>

<?php if ($stats['configError']) { ?>
    <div class="alert alert-danger" role="alert">
        <?php echo t('The helpdesk rejected this site\'s key — contact Limegreentangerine.'); ?>
    </div>
<?php } ?>

<?php if ($stats['pending'] > 0 && !$scheduled) { ?>
    <div class="alert alert-warning" role="alert">
        <?php echo t('Queued requests are only sent by the scheduled task "Send queued support tickets", which has no schedule. Add one under Dashboard > System & Settings > Automation > Tasks.'); ?>
    </div>
<?php } elseif ($overdue !== null) { ?>
    <div class="alert alert-warning" role="alert">
        <?php echo t('The scheduled task may not be running — check its schedule under Automation > Tasks and the server\'s scheduler cron job.'); ?>
    </div>
<?php } ?>

<?php if ($disabledReason === 'unconfigured') { ?>
    <div class="alert alert-warning">
        <?php echo t('Support requests are not configured for this site yet. Please contact Limegreentangerine directly.'); ?>
    </div>
<?php } elseif ($disabledReason === 'non_production') { ?>
    <div class="alert alert-info">
        <?php echo t('Support requests are switched off on this staging or development site. Please raise requests from the live site.'); ?>
    </div>
<?php } else { ?>
    <div class="alert alert-info"><?php echo h($hoursText); ?></div>

    <form method="post" enctype="multipart/form-data" action="<?php echo $view->action('submit'); ?>" id="lgt-support-form" novalidate>
        <?php echo $token->output('lgt_support_submit'); ?>
        <input type="hidden" name="page_id" value="<?php echo $value('page_id'); ?>">
        <input type="hidden" name="page_version_id" value="<?php echo $value('page_version_id'); ?>">

        <p class="text-muted"><?php echo t('Fields marked (required) must be filled in.'); ?></p>

        <fieldset class="mb-4"<?php echo isset($fieldErrors['category']) ? ' aria-describedby="category-error"' : ''; ?>>
            <legend class="fs-6 fw-bold"><?php echo t('What is this about? (required)'); ?></legend>
            <?php foreach ($categories as $category) { ?>
                <?php $id = 'category-' . $category->value; ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="category" id="<?php echo $id; ?>" value="<?php echo h($category->value); ?>"
                        aria-describedby="<?php echo $id; ?>-help"
                        <?php echo ($values['category'] ?? '') === $category->value ? 'checked' : ''; ?> required>
                    <label class="form-check-label fw-semibold" for="<?php echo $id; ?>"><?php echo h($category->label()); ?></label>
                    <div class="form-text mt-0" id="<?php echo $id; ?>-help"><?php echo h($category->help()); ?></div>
                </div>
            <?php } ?>
            <?php echo $field('category')['error']; ?>
        </fieldset>

        <?php $f = $field('summary'); ?>
        <div class="mb-3">
            <label class="form-label" for="summary"><?php echo t('Summary (required)'); ?></label>
            <input type="text" class="form-control<?php echo $f['class']; ?>" id="summary" name="summary" maxlength="150" required
                value="<?php echo $value('summary'); ?>"<?php echo $f['aria']; ?>>
            <?php echo $f['error']; ?>
        </div>

        <?php $f = $field('description'); ?>
        <div class="mb-3">
            <label class="form-label" for="description"><?php echo t('Description (required)'); ?></label>
            <textarea class="form-control<?php echo $f['class']; ?>" id="description" name="description" rows="8" maxlength="5000" required
                aria-describedby="description-help<?php echo isset($fieldErrors['description']) ? ' description-error' : ''; ?>"
                <?php echo isset($fieldErrors['description']) ? 'aria-invalid="true"' : ''; ?>><?php echo $value('description'); ?></textarea>
            <div class="form-text" id="description-help"><?php echo t('What happened, what you expected, and the steps to reproduce it.'); ?></div>
            <?php echo $f['error']; ?>
        </div>

        <div class="row">
            <?php $f = $field('email'); ?>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="email"><?php echo t('Your email (required)'); ?></label>
                <input type="email" class="form-control<?php echo $f['class']; ?>" id="email" name="email" maxlength="255" required autocomplete="email"
                    value="<?php echo $value('email'); ?>" aria-describedby="email-help<?php echo isset($fieldErrors['email']) ? ' email-error' : ''; ?>"<?php echo isset($fieldErrors['email']) ? ' aria-invalid="true"' : ''; ?>>
                <div class="form-text" id="email-help"><?php echo t('The acknowledgement and replies go to this address.'); ?></div>
                <?php echo $f['error']; ?>
            </div>
            <?php $f = $field('name'); ?>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name"><?php echo t('Your name'); ?></label>
                <input type="text" class="form-control<?php echo $f['class']; ?>" id="name" name="name" maxlength="100" autocomplete="name"
                    value="<?php echo $value('name'); ?>"<?php echo $f['aria']; ?>>
                <?php echo $f['error']; ?>
            </div>
        </div>

        <div class="row">
            <?php $f = $field('contact_phone'); ?>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="contact_phone"><?php echo t('Contact phone'); ?></label>
                <input type="tel" class="form-control<?php echo $f['class']; ?>" id="contact_phone" name="contact_phone" maxlength="30" autocomplete="tel"
                    value="<?php echo $value('contact_phone'); ?>"<?php echo $f['aria']; ?>>
                <?php echo $f['error']; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="page_url"><?php echo t('Page'); ?></label>
                <?php if (($values['page_label'] ?? '') !== '') { ?>
                    <input type="url" class="form-control" id="page_url" name="page_url" readonly value="<?php echo $value('page_url'); ?>" aria-describedby="page_url-help">
                    <div class="form-text" id="page_url-help"><?php echo h($values['page_label']); ?></div>
                <?php } else { ?>
                    <input type="url" class="form-control" id="page_url" name="page_url" maxlength="255" value="<?php echo $value('page_url'); ?>" aria-describedby="page_url-help">
                    <div class="form-text" id="page_url-help"><?php echo t('Optional: the address of the page with the problem, on this site.'); ?></div>
                <?php } ?>
            </div>
        </div>

        <?php $f = $field('attachments'); ?>
        <div class="mb-3">
            <label class="form-label" for="attachments"><?php echo t('Screenshots or files'); ?></label>
            <input type="file" class="form-control<?php echo $f['class']; ?>" id="attachments" name="attachments[]" multiple
                accept="<?php echo h(implode(',', $allowedMime)); ?>"
                aria-describedby="attachments-help<?php echo isset($fieldErrors['attachments']) ? ' attachments-error' : ''; ?>">
            <div class="form-text" id="attachments-help">
                <?php echo t('Up to %s files: PNG, JPEG, WebP or PDF, %s MB each, %s MB in total.', $maxFiles, $mb($fileLimit), $mb(\LgtToolkit\Support\TicketValidator::MAX_TOTAL_BYTES)); ?>
            </div>
            <?php echo $f['error']; ?>
        </div>

        <details class="mb-4">
            <summary><?php echo t('What we include automatically'); ?></summary>
            <p class="mt-2 mb-1"><?php echo t('To help us investigate, your request also includes:'); ?></p>
            <ul>
                <li><?php echo t('The site name, address and environment'); ?></li>
                <li><?php echo t('The page you reported from (ID, version, path and name), if any'); ?></li>
                <li><?php echo t('Your user ID, username and groups'); ?></li>
                <li><?php echo t('Concrete, PHP and installed package versions'); ?></li>
                <li><?php echo t('Your browser\'s user agent'); ?></li>
                <li><?php echo t('The latest errors from the site log, with emails, passwords, tokens and card-like numbers removed'); ?></li>
            </ul>
            <p class="mb-0 text-muted"><?php echo t('Never included: passwords, cookies, session data or site configuration.'); ?></p>
        </details>

        <button type="submit" class="btn btn-primary" id="lgt-support-submit"><?php echo t('Send request'); ?></button>
    </form>

    <script>
        document.getElementById('lgt-support-form').addEventListener('submit', function () {
            var button = document.getElementById('lgt-support-submit');
            button.disabled = true;
            button.textContent = <?php echo json_encode(t('Sending…')); ?>;
        });
    </script>
<?php } ?>

<?php if ($queued !== []) { ?>
    <hr>
    <h4><?php echo t('Queued requests'); ?></h4>
    <p class="text-muted"><?php echo t('These requests could not be delivered yet. They are sent by the scheduled task.'); ?></p>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col"><?php echo t('Reference'); ?></th>
                    <th scope="col"><?php echo t('Submitted'); ?></th>
                    <th scope="col"><?php echo t('Subject'); ?></th>
                    <th scope="col"><?php echo t('Status'); ?></th>
                    <th scope="col"><?php echo t('Attempts'); ?></th>
                    <th scope="col"><?php echo t('Next attempt'); ?></th>
                    <th scope="col"><span class="visually-hidden"><?php echo t('Actions'); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queued as $item) { ?>
                    <tr>
                        <td><code><?php echo h($item->localReference); ?></code></td>
                        <td><?php echo h($dateHelper->formatDateTime($item->createdAt)); ?></td>
                        <td><?php echo h($item->subject); ?></td>
                        <td>
                            <?php if ($item->status === 'failed') { ?>
                                <span class="badge bg-danger"><?php echo t('Failed'); ?></span>
                                <?php if ($item->lastError) { ?><div class="small text-muted"><?php echo h($item->lastError); ?></div><?php } ?>
                            <?php } elseif ($item->status === 'delivering') { ?>
                                <span class="badge bg-info"><?php echo t('Sending'); ?></span>
                            <?php } else { ?>
                                <span class="badge bg-warning text-dark"><?php echo t('Waiting'); ?></span>
                            <?php } ?>
                        </td>
                        <td><?php echo (int) $item->attempts; ?></td>
                        <td><?php echo $item->status === 'pending' ? h($dateHelper->formatDateTime($item->nextAttemptAt)) : '—'; ?></td>
                        <td class="text-end">
                            <?php if ($item->status === 'failed') { ?>
                                <form method="post" action="<?php echo $view->action('discard', $item->id); ?>"
                                    onsubmit="return confirm(<?php echo h(json_encode(t('Discard request %s? It will not be sent.', $item->localReference))); ?>);">
                                    <?php echo $token->output('lgt_support_discard'); ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo t('Discard'); ?></button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

<?php } ?>
