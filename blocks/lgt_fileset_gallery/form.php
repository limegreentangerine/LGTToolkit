<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Display'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('title', t('Title'));
echo $form->text('title', $title ?? null);
?>
    </div>

    <div class="form-group">
        <?php
    echo $form->label('content', t('Content'));
$editor = $this->app->make('editor');
echo $editor->outputBlockEditModeEditor('content', $content ?? null);
?>
    </div>

    <?php if (isset($filesets)) { ?>
        <div class="form-group">
            <?php
        echo $form->label('fsID', t('File Set'));
        echo (string) $form->select('fsID', $filesets, $fsID ?? null);
        ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-info"><?php echo t('No filesets in system'); ?></div>
    <?php } ?>
</fieldset>
