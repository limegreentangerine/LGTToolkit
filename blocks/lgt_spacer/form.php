<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Space (in pixels)'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('height_mobile', t('Spacing (Mobile)'));
echo $form->number('height_mobile', (isset($height_mobile)) ? $height_mobile : 40);
?>
    </div>


    <div class="form-group">
        <?php
    echo $form->label('height_tablet', t('Spacing (Tablet)'));
echo $form->number('height_tablet', (isset($height_tablet)) ? $height_tablet : 40);
?>
    </div>

    <div class="form-group">
        <?php
    echo $form->label('height_desktop', t('Spacing (Desktop)'));
echo $form->number('height_desktop', (isset($height_desktop)) ? $height_desktop : 100);
?>
    </div>

    <div class="form-group">
        <?php
    echo $form->label('height_hd', t('Spacing (HD)'));
echo $form->number('height_hd', (isset($height_hd)) ? $height_hd : 100);
?>
    </div>

    <div class="form-group">
        <?php
    echo $form->label('height_4k', t('Spacing (4K)'));
echo $form->number('height_4k', (isset($height_4k)) ? $height_4k : 120);
?>
    </div>
</fieldset>
