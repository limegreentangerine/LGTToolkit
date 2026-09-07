<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($view) && isset($token)) { ?>
    <form method="post" action="<?php echo $view->action('save'); ?>">
        <?php echo $token->output('submit') ?>

        <fieldset>
            <div class="form-group">
                <?php
                    echo $form->label('code', t('Code'));
                    echo $form->textarea('code', (isset($pkg)) ? $pkg->getFileConfig()->get('lgt_toolkit.uaccess.code') : false, [ 'resize' => 'none', 'style' => 'height:200px;' ]);
                ?>
            </div>

            <div class="form-group">
                <?php
                    echo $form->label('placement', t('Placement'));
                    echo (string) $form->select('placement', (isset($codePlacement) ? $codePlacement : []), (isset($pkg)) ? $pkg->getFileConfig()->get('lgt_toolkit.uaccess.placement') : false);
                ?>
            </div>
        </fieldset>

        <div class="ccm-dashboard-form-actions-wrapper">
            <div class="ccm-dashboard-form-actions">
                <?php echo $form->submit('save', t('Save Settings'), array('class' => 'btn btn-primary float-end')); ?>
            </div>
        </div>
    </form>
<?php } ?>
