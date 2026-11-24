<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<p class="text-muted">
    <?php echo t('These settings are read from the site\'s %s file and cannot be changed here.', '<code>.env</code>'); ?>
</p>

<?php if ($state === 'enabled') { ?>
    <div class="alert alert-success"><strong><?php echo t('Support tickets are enabled.'); ?></strong></div>
<?php } elseif ($state === 'non_production') { ?>
    <div class="alert alert-info">
        <strong><?php echo t('Support tickets are disabled on this non-production site.'); ?></strong>
        <?php echo t('Set %s to send tickets from here; their subjects will be prefixed.', '<code>' . h(\LgtToolkit\Support\SupportEnv::ENABLED_NON_PRODUCTION) . '=true</code>'); ?>
    </div>
<?php } else { ?>
    <div class="alert alert-warning"><strong><?php echo t('Support tickets are not configured.'); ?></strong></div>
<?php } ?>

<?php foreach ($settings as $setting) { ?>
    <?php if ($setting['populated']) { ?>
        <div class="alert alert-success">
            <strong><?php echo h($setting['label']); ?></strong>
            <code><?php echo h($setting['env']); ?></code>:
            <?php echo h($setting['display']); ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-warning">
            <strong><?php echo h($setting['label']); ?></strong>
            <?php echo t('has not been populated (or is not valid). Add %s to your .env file.', '<code>' . h($setting['env']) . '</code>'); ?>
            <?php if ($setting['default'] !== null) { ?>
                <?php echo t('Using the default: %s.', '<code>' . h($setting['default']) . '</code>'); ?>
            <?php } ?>
        </div>
    <?php } ?>
<?php } ?>

<hr>
<h4><?php echo t('Retry Queue'); ?></h4>
<p class="text-muted"><?php echo t('The site key cannot be tested from here: every request to the helpdesk creates a real ticket.'); ?></p>

<?php if ($schedules === []) { ?>
    <div class="alert alert-warning">
        <?php echo t('The task "Send queued support tickets" has no schedule, so queued tickets are never sent. Schedule it (every 5 minutes) under Dashboard > System & Settings > Automation > Tasks.'); ?>
    </div>
<?php } else { ?>
    <div class="alert alert-success">
        <?php echo t('Scheduled: %s', '<code>' . h(implode(', ', $schedules)) . '</code>'); ?>
    </div>
<?php } ?>
<?php if (!$schedulingEnabled) { ?>
    <div class="alert alert-danger">
        <?php echo t('Task scheduling is turned off (%s), so scheduled tasks never run.', '<code>concrete.jobs.enable_scheduling</code>'); ?>
    </div>
<?php } ?>

<dl class="row">
    <dt class="col-sm-3"><?php echo t('Waiting to send'); ?></dt>
    <dd class="col-sm-9"><?php echo (int) $stats['pending']; ?></dd>
    <dt class="col-sm-3"><?php echo t('Failed'); ?></dt>
    <dd class="col-sm-9"><?php echo (int) $stats['failed']; ?></dd>
    <?php if ($overdue !== null) { ?>
        <dt class="col-sm-3"><?php echo t('Oldest overdue'); ?></dt>
        <dd class="col-sm-9">
            <?php echo h($overdue->localReference); ?>,
            <?php echo t('due %s', h($dateHelper->formatDateTime($overdue->nextAttemptAt))); ?>
            <div class="text-danger"><?php echo t('The scheduled task may not be running — check its schedule and the server\'s scheduler cron job.'); ?></div>
        </dd>
    <?php } ?>
    <?php if ($stats['lastError'] !== null) { ?>
        <dt class="col-sm-3"><?php echo t('Last delivery error'); ?></dt>
        <dd class="col-sm-9">
            <?php if ($stats['lastError']->lastStatusCode !== null) { ?>
                <code><?php echo (int) $stats['lastError']->lastStatusCode; ?></code>
            <?php } ?>
            <?php echo h((string) $stats['lastError']->lastError); ?>
        </dd>
    <?php } ?>
</dl>

<p><a href="<?php echo h((string) \URL::to('/dashboard/support')); ?>"><?php echo t('Open the support page'); ?></a></p>
