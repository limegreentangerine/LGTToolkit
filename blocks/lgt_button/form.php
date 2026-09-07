<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Button Styles'); ?></legend>

    <div class="form-group">
        <?php echo $form->label('button_text', t('Button Text')); ?>
        <input name="button_text" class="form-control" placeholder="<?php echo h(t('Button Text')); ?>" value="<?php echo h($button_text ?? null); ?>" type="text" required="required" />
    </div>

    <div class="form-group override-button-colors">
        <label class="control-label"><?php echo t('Button Style'); ?></label>

        <table>
            <?php
            foreach ($this->controller->getStyles() as $k => $v) {
                echo '<tr>';
                echo '<td width="50%">';
                echo '<div class="radio">';
                echo '<label>';
                echo '<input type="radio" name="style" value="' . $k . '"' . (isset($style) && $style == $k ? ' checked="checked"' : '') . ' />';
                echo t($v);
                echo '</label>';
                echo '</div>';
                echo '</td><td vertical-align="center" align="center" class="td-' . $k . '">';
                echo '<button class="btn btn-' . $k . '" type="button">Example</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </table>
    </div>

    <div class="form-group">
        <label class="form-label"><?php echo t('Size') ?></label>
        <select name="button_size"class="form-control">
            <option value="" <?php echo (isset($button_size) && $button_size == '' ? 'selected="selected"' : '')?>><?php echo t('Normal'); ?></option>
            <option value="btn-sm" <?php echo (isset($button_size) && $button_size == 'btn-sm' ? 'selected="selected"' : '')?>><?php echo t('Small'); ?></option>
            <option value="btn-lg" <?php echo (isset($button_size) && $button_size == 'btn-lg' ? 'selected="selected"' : '')?>><?php echo t('Large'); ?></option>
        </select>
    </div>

    <div class="form-group">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="full_width" id="full_width" value="1" <?php echo (isset($full_width) && $full_width == 1) ? 'checked="checked"' : '' ?> />
            <label for="full_width" class="form-check-label"><?php echo t('Make button full width to containing element?'); ?></label>
        </div>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Link'); ?></legend>

    <div class="form-group">
        <select name="link_type" data-select="feature-link-type" class="form-control">
            <?php foreach ($this->controller->getLinkTypes() as $value => $name) { ?>
                <option value="<?php echo $value; ?>" <?php echo ((isset($link_type) && $link_type == $value) ? 'selected="selected"' : '')?>><?php echo $name; ?></option>
            <?php } ?>
        </select>
    </div>

    <?php if (isset($ps)) { ?>
        <div data-select-contents="feature-link-type-internal" class="form-group" <?php echo (isset($link_type) && $link_type == 'internal' ? '' : 'style="display:none;"'); ?>>
            <?php
                echo $form->label('page_cID', t('Choose Page:'));
                echo $ps->selectPage('page_cID', isset($page_cID) && $page_cID > 0 ? $page_cID : false);
            ?>
        </div>
    <?php } ?>

    <div data-select-contents="feature-link-type-external" class="form-group" <?php echo (isset($link_type) && $link_type == 'external' ? '' : 'style="display:none;"'); ?>>
        <?php echo $form->label('external_url', t('URL')); ?>
        <?php echo $form->text('external_url', $external_url ?? null); ?>
    </div>

    <div data-select-contents="feature-link-type-file" class="form-group" <?php echo (isset($link_type) && $link_type == 'file' ? '' : 'style="display:none;"'); ?>>
        <?php echo $form->label('fID', t('File')); ?>
        <?php echo $al->file('fID', 'fID', t('Choose File'), $fID ?? null); ?>
    </div>

    <?php if (count($this->controller->getAnchors()) > 0) { ?>
        <div data-select-contents="feature-link-type-anchor" class="form-group" <?php echo (isset($link_type) && $link_type == 'anchor' ? '' : 'style="display:none;"'); ?>>
            <?php echo $form->label('anchor', t('Anchor')); ?>
            <?php
                $anchors = array_merge(['' => 'Choose anchor...' ], $this->controller->getAnchors());
                echo (string) $form->select('anchor', $anchors, $anchor ?? null);
            ?>
        </div>
    <?php } ?>

    <div class="form-group">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="new_window" id="new_window" value="1" <?php echo (isset($new_window) && $new_window) ? 'checked="checked"' : '' ?> />
            <label for="new_window" class="form-check-label"><?php echo t('Open in new Window'); ?></label>
        </div>
    </div>

</fieldset>



<script type="text/javascript">
$(function() {
    $('select[data-select=feature-link-type]').on('change', function() {
        if ($(this).val() == 'external') {
            $('div[data-select-contents=feature-link-type-internal]').hide();
            $('div[data-select-contents=feature-link-type-external]').show();
            $('div[data-select-contents=feature-link-type-file]').hide();
            $('div[data-select-contents=feature-link-type-anchor]').hide();
        } else if ($(this).val() == 'file') {
            $('div[data-select-contents=feature-link-type-internal]').hide();
            $('div[data-select-contents=feature-link-type-external]').hide();
            $('div[data-select-contents=feature-link-type-file]').show();
            $('div[data-select-contents=feature-link-type-anchor]').hide();
        } else if ($(this).val() == 'anchor') {
            $('div[data-select-contents=feature-link-type-internal]').hide();
            $('div[data-select-contents=feature-link-type-external]').hide();
            $('div[data-select-contents=feature-link-type-file]').hide();
            $('div[data-select-contents=feature-link-type-anchor]').show();
        } else {
            $('div[data-select-contents=feature-link-type-internal]').show();
            $('div[data-select-contents=feature-link-type-external]').hide();
            $('div[data-select-contents=feature-link-type-file]').hide();
            $('div[data-select-contents=feature-link-type-anchor]').hide();
        }
    }).trigger('change');
});
</script>
