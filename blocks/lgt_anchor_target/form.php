<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Content'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('target', t('Target'));
echo $form->text('target', $target ?? null);
?>
    </div>

    <div class="form-group">
        <?php
    echo $form->label('linkText', t('Link Text'));
echo $form->text('linkText', $linkText ?? null);
?>
    </div>
</fieldset>
