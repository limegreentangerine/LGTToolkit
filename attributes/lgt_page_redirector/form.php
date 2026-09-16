<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<component-redirect-attr-form>
    <div class="form-group">
        <?php echo $form->label($this->controller->field('redirectMethod'), t('Redirect Method')) ?>
        <?php echo (string) $form->select($this->controller->field('redirectMethod'), $this->controller->getRedirectMethods(), $redirectMethod ?? false) ?>
    </div>

    <div class="form-group">
        <?php echo $form->label($this->controller->field('redirectType'), t('Redirect Type')) ?>
        <?php echo (string) $form->select($this->controller->field('redirectType'), [ '' => t('Choose type...') ] + $this->controller->getRedirectTypes(), $redirectType ?? false, [ 'data-redirect-type-select' => '' ]) ?>
    </div>

    <div class="form-group <?php echo (isset($redirectType) && $redirectType === 'external') ? '' : 'd-none'; ?>" data-redirect-external>
        <?php echo $form->label($this->controller->field('externalValue'), t('URL')) ?>
        <?php echo $form->url($this->controller->field('externalValue'), (isset($redirectType) && $redirectType === 'external') ? $value ?? '' : '') ?>
    </div>

    <?php if (isset($form_page_selector)) { ?>
        <div class="form-group <?php echo (isset($redirectType) && $redirectType === 'page') ? '' : 'd-none'; ?>" data-redirect-page>
            <?php echo $form->label($this->controller->field('pageValue'), t('Page')) ?>
            <?php echo $form_page_selector->selectPage($this->controller->field('pageValue'), (isset($redirectType) && $redirectType === 'page') ? $value ?? '' : '') ?>
        </div>
    <?php } ?>
</component-redirect-attr-form>
