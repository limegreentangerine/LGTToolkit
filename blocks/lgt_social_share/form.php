<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Display'); ?></legend>

    <div class="form-group">
        <?php echo $form->label('share_title', t('Title')); ?>
        <?php echo $form->text('share_title', $title ?? null); ?>
    </div>

    <div class="form-group">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="mobileOnlySharesheet" id="mobileOnlySharesheet" value="1" <?php echo (isset($mobileOnlySharesheet) && $mobileOnlySharesheet) ? 'checked="checked"' : '' ?> />
            <label for="mobileOnlySharesheet" class="form-check-label"><?php echo t('Mobile only Share sheet'); ?></label>
        </div>

        <div class="help-text"><?php echo t('This block will use the share sheet if the operating system allows, check this field to only use it on mobile devices.'); ?></div>
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
