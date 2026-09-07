<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Page List'); ?></legend>

    <div class="form-group">
        <?php echo $form->label('parent_cID', t('Choose Parent Page')); ?>
        <?php echo $pl->selectPage('parent_cID', $parent_cID ?? null); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label('pageTemplateID', t('Filter by Page Template')); ?>
        <select id="pageTemplateID" name="pageTemplateID" class="form-control">
            <option value="0" readonly>Choose...</option>
            <?php foreach ($templates as $template) {
                $selected = (isset($pageTemplateID) && $template->getPageTemplateID() == $pageTemplateID) ? 'selected' : '';
                ?>
                <option value="<?php echo $template->getPageTemplateID(); ?>" <?php echo $selected; ?>><?php echo $template->getPageTemplateName(); ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <?php echo $form->label('sortOrder', t('Sorting Options')); ?>
        <select id="sortOrder" name="sortOrder" class="form-control">
            <option value="" readonly>Choose...</option>
            <?php foreach ($sorting as $key => $value) {
                $selected = (isset($sortOrder) && $key == $sortOrder) ? 'selected' : '';
                ?>
                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <?php echo $form->label('ajaxTemplateHandle', t('Ajax Template Handle')); ?>
        <select id="ajaxTemplateHandle" name="ajaxTemplateHandle" class="form-control">
            <option value="" readonly>Choose...</option>
            <?php foreach ($ajaxTemplates as $key => $value) {
                $selected = (isset($ajaxTemplateHandle) && $key == $ajaxTemplateHandle) ? 'selected' : '';
                ?>
                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
            <?php } ?>
        </select>
        <div class="help-block"><?php echo t('Templates for ajax responses can be found in <code>%s</code>', $ajaxTemplatePath); ?></div>
    </div>

    <div class="form-group">
        <?php echo $form->label('pageLength', t('Page Length')); ?>
        <?php echo $form->number('pageLength', $pageLength ?? null, ['min' => '1']); ?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Pagination'); ?></legend>

    <div class="form-group">
        <?php echo $form->label('buttonText', t('Button Text')); ?>
        <?php echo $form->text('buttonText', $buttonText ?? null); ?>
    </div>
</fieldset>
