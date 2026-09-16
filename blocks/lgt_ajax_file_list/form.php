<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('File Set'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('fsID', t('File Set'));
echo (string) $form->select('fsID', $this->controller->getFileSets(), $fsID ?? null);
?>
    </div>

    <?php if (isset($ajaxTemplates) && isset($ajaxTemplatePath)) { ?>
        <div class="form-group">
            <?php echo $form->label('ajaxTemplateHandle', t('Ajax Template Handle')); ?>
            <select id="ajaxTemplateHandle" name="ajaxTemplateHandle" class="form-control">
                <option value="" readonly><?php echo t('Choose...'); ?></option>
                <?php foreach ($ajaxTemplates as $key => $value) {
                    $selected = (isset($ajaxTemplateHandle) && $key == $ajaxTemplateHandle) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
                <?php } ?>
            </select>
            <div class="help-block"><?php echo t('Templates for ajax responses can be found in <code>%s</code>', $ajaxTemplatePath); ?></div>
        </div>
    <?php } ?>
</fieldset>

<fieldset>
    <legend><?php echo t('Pagination & Filtering'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('numberPerPage', t('Number per Page'));
echo $form->number('numberPerPage', $numberPerPage ?? null);
?>
    </div>

    <div class="form-group">
        <?php
    echo $form->label('topicTreeID', t('Topic Tree'));
echo (string) $form->select('topicTreeID', $this->controller->getTopicTrees(), $topicTreeID ?? null);
?>
    </div>
</fieldset>
