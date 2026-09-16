<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Colours'); ?></legend>

    <div class="form-group">
        <?php $color->output('hex_text', $hex_text ?? null, ['preferredFormat' => 'hex']); ?>
        <?php echo $form->label('hex_text', t('Font Colour')); ?>
    </div>

    <div class="form-group">
        <?php $color->output('hex_background', $hex_background ?? null, ['preferredFormat' => 'hex']); ?>
        <?php echo $form->label('hex_background', t('Background Colour')); ?>
    </div>

</fieldset>

<fieldset>
    <legend><?php echo t('Blockquote'); ?></legend>

    <div class="form-group">
        <?php echo $form->label('author', t('Author')); ?>
        <?php echo $form->text('author', $author ?? null); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label('quote', t('Quote')); ?>
        <?php echo $form->textarea('quote', $quote ?? null); ?>
    </div>

</fieldset>
