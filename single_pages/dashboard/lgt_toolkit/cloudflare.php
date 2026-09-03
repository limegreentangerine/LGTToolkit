<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if ($pkg->getFileConfig()->get('lgt_toolkit.cloudflare.zone_id') !== null && $pkg->getFileConfig()->get('lgt_toolkit.cloudflare.zone_id') !== '') { ?>
    <div class="ccm-dashboard-header-buttons">
        <button data-launch-dialog="delete-dialog" class="btn btn-danger">
            <?php echo t('Clear Settings'); ?>
        </button>

        <div style="display: none" data-dialog="delete-dialog" class="ccm-ui">
            <form data-dialog-form="delete-form" method="POST" action="<?php echo $view->action('clear'); ?>">
                <?php echo $token->output('delete-cloudflare-settings'); ?>
                <p><?php echo t('Are you sure you want to permanently delete these settings?'); ?></p>
                <p><strong><?php echo t('WARNING: this operation can not be undone!'); ?></strong></p>
            </form>
            <div class="dialog-buttons">
                <button class="btn btn-default pull-left" data-dialog-action="cancel"><?php echo t('Cancel'); ?></button>
                <button class="btn btn-danger pull-right" data-dialog-action="submit"><?php echo t('Delete'); ?></button>
            </div>
        </div>

        <script>
            $(function() {
                var $dialog = $('div[data-dialog="delete-dialog"]');
                $('[data-launch-dialog="delete-dialog"]').on('click', function(e) {
                    e.preventDefault();
                    jQuery.fn.dialog.open({
                        element: $dialog,
                        modal: true,
                        width: 420,
                        title: <?php echo json_encode(t('Confirm Delete')); ?>,
                        height: 'auto'
                    });
                });

                ConcreteEvent.subscribe('AjaxFormSubmitSuccess', function(e, data) {
                    if (data.form === 'delete-form') {
                        window.location.href = <?php echo json_encode((string) URL::to('/dashboard/lgt_toolkit/cloudflare')); ?>;
                    }
                });
            });
        </script>
    </div>
<?php } ?>

<form method="post" action="<?php echo $view->action('save'); ?>">
    <?php echo $token->output('submit') ?>

    <fieldset>
        <legend><?php echo t('Setup'); ?></legend>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" id="activate" name="activate" class="form-check-input" value="1" <?php echo (isset($formContent) && isset($formContent['activate'])) ? 'checked' : (($pkg && $pkg->getFileConfig()->get('lgt_toolkit.cloudflare.activate') == true) ? 'checked' : ''); ?> />
                <label for="activate" class="form-check-label"><?php echo t('Activate'); ?></label>
            </div>
        </div>

        <div class="form-group">
            <label for="base_url" class="form-label"><?php echo t('API Base URL'); ?></label>
            <div class="float-end">
                <span class="text-muted small"><?php echo t('Required'); ?></span>
            </div>
            <?php echo $form->text('base_url', (isset($formContent)) ? $formContent['base_url'] : (($pkg && $pkg->getFileConfig()->get('lgt_toolkit.cloudflare.base_url') !== null) ? $pkg->getFileConfig()->get('lgt_toolkit.cloudflare.base_url') : 'https://api.cloudflare.com/client/v4')); ?>
        </div>

        <div class="form-group">
            <label for="zone_id" class="form-label"><?php echo t('Zone ID'); ?></label>
            <div class="float-end">
                <span class="text-muted small"><?php echo t('Required'); ?></span>
            </div>
            <?php echo $form->text('zone_id', (isset($formContent)) ? $formContent['zone_id'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cloudflare.zone_id') : false)); ?>
            <div class="help-block"><?php echo t('Found on Zone Overview under API'); ?></div>
        </div>

        <div class="form-group">
            <label for="token" class="form-label"><?php echo t('API Token'); ?></label>
            <div class="float-end">
                <span class="text-muted small"><?php echo t('Required'); ?></span>
            </div>
            <?php echo $form->text('token', (isset($formContent)) ? $formContent['token'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cloudflare.token') : false)); ?>
            <div class="help-block"><?php echo t('Permissions: <code>Zone.Zone Settings</code>'); ?></div>
        </div>
    </fieldset>

    <?php if ($pkg && $pkg->getFileConfig()->get('lgt_toolkit.cloudflare.activate') == true) { $controller->getCloudflareDevelopmentMode(); ?>
        <fieldset>
            <legend><?php echo t('Development Mode Status'); ?></legend>

            <div class="form-group">
                <p><?php echo t('Development mode is: <code>%s</code>', $controller->getCloudflareDevelopmentMode()); ?></p>
            </div>
        </fieldset>
    <?php } ?>


    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <?php echo $form->submit('save', t('Save Settings'), array('class' => 'btn btn-primary float-end')); ?>
        </div>
    </div>
</form>
