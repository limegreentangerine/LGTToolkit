<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Display'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('share_title', t('Title'));
echo $form->text('share_title', $title ?? null);
?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Share Links'); ?></legend>

    <?php if (isset($services) && count($services) > 0) { ?>
        <div class="form-group">
            <?php foreach ($services as $handle => $name) {
                $checked = ((isset($selected) && is_array($selected)) && in_array($handle, $selected)) ? 'checked' : '';
                ?>
                <div class="input-group">
                    <div class="checkbox">
                        <label for="<?php echo $handle; ?>" >
                            <input type="checkbox" id="<?php echo $handle; ?>" name="socialHandles[]" class="ccm-input-checkbox" value="<?php echo $handle; ?>" <?php echo $checked; ?> />
                            <span><?php echo $name; ?></span>
                        </label>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-info"><?php echo t('No social media services'); ?></div>
    <?php } ?>
</fieldset>
