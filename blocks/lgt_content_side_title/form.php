<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Display'); ?></legend>

    <div class="form-group">
        <?php $color->output('hex_background', $hex_background ?? null, array('preferredFormat' => 'hex')); ?>
        <?php echo $form->label('hex_background', t('Background Colour')); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label('title', t('Title')); ?>
        <?php echo $form->text('title', $title ?? null); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label('content', t('Content:'));?>
        <?php
            $editor = $this->app->make('editor');
            echo $editor->outputBlockEditModeEditor('content', $content ?? null);
        ?>
    </div>

</fieldset>

<fieldset>
    <legend><?php echo t('Link'); ?></legend>

    <?php if (isset($ps)) { ?>
        <div class="form-group">
            <?php
                echo $form->label('link_cID', t('Page'));
                echo $ps->selectPage('link_cID', $link_cID ?? null);
            ?>
        </div>
    <?php } ?>

    <div class="form-group">
        <?php
            echo $form->label('buttonText', t('Button Text'));
            echo $form->text('buttonText', $buttonText ?? null);
        ?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Column Size'); ?></legend>

    <div class="form-group">
        <label class="control-label"><?php echo t('Title Column Size') ?></label>
        <select name="size_title" class="form-control">
            <?php
            for ($i = 1; $i < 13; $i++) {
                $selected = '';
                if (isset($size_title)) {
                    if ($size_title == $i) {
                        $selected = ' selected="selected"';
                    }
                } elseif ($i == 4) {
                    $selected = ' selected="selected"';
                }

                echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label class="control-label"><?php echo t('Content Column Size') ?></label>
        <select name="size_content" class="form-control">
            <?php
            for ($i = 1; $i < 13; $i++) {
                $selected = '';
                if (isset($size_content)) {
                    if ($size_content == $i) {
                        $selected = ' selected="selected"';
                    }
                } elseif ($i == 8) {
                    $selected = ' selected="selected"';
                }

                echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('content_spacing', t('Column Spacing'));
            echo (string) $form->select('content_spacing', $this->controller->getColumnSpacingOptions(), $content_spacing ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('content_arrangement', t('Column Arrangement'));
            echo (string) $form->select('content_arrangement', $this->controller->getColumnArrangementOptions(), $content_arrangement ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('content_alignment', t('Column Alignment'));
            echo (string) $form->select('content_alignment', $this->controller->getColumnAlignmentOptions(), $content_alignment ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('gutter_size', t('Gutter Size'));
            echo (string) $form->select('gutter_size', $this->controller->getColumnGutterSizeOptions(), $gutter_size ?? null);
        ?>
    </div>

</fieldset>
