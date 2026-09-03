<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<form method="post" action="<?php echo $view->action('save'); ?>">
    <?php echo $token->output('submit') ?>

    <fieldset>
        <legend><?php echo t('Status'); ?></legend>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" id="activate" name="activate" class="form-check-input" value="1" <?php echo (isset($formContent) && isset($formContent['activate'])) ? 'checked' : (($pkg && $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.activate') == true) ? 'checked' : ''); ?> />
                <label for="activate" class="form-check-label"><?php echo t('Activate'); ?></label>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?php echo t('Styling'); ?></legend>

        <div class="form-group">
            <?php
                echo $form->label('bgColor', t('Background Colour'));
                echo $form->select('styles[bgColor]', $controller->getColourOptions(), (isset($formContent)) ? $formContent['styles']['bgColor'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.bgColor') : false));
            ?>
        </div>

        <div class="form-group">
            <?php
                echo $form->label('fgColor', t('Foreground Colour'));
                echo $form->select('styles[fgColor]', $controller->getColourOptions(), (isset($formContent)) ? $formContent['styles']['fgColor'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.fgColor') : false));
            ?>
        </div>

        <div class="form-group">
            <?php
                echo $form->label('acceptAllStyle', t('Accept All Button'));
                echo $form->select('styles[acceptAllStyle]', $controller->getColourOptions(), (isset($formContent)) ? $formContent['styles']['acceptAllStyle'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.acceptAllStyle') : false));
            ?>
        </div>

        <div class="form-group">
            <?php
                echo $form->label('acceptRequiredStyle', t('Accept Required Button'));
                echo $form->select('styles[acceptRequiredStyle]', $controller->getColourOptions(), (isset($formContent)) ? $formContent['styles']['acceptRequiredStyle'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.acceptRequiredStyle') : false));
            ?>
        </div>

        <div class="form-group">
            <?php echo $form->label('roundedCorners', t('Rounded Corners')); ?>
            <div class="input-group">
                <div class="checkbox">
                    <label for="styles[roundedCorners]">
                        <?php echo $form->checkbox('styles[roundedCorners]', 1, (isset($formContent)) ? $formContent['styles']['roundedCorners'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.roundedCorners') : false)); ?>
                        <span><?php echo t('Yes'); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <?php echo $form->label('shadow', t('Shadow')); ?>
            <div class="input-group">
                <div class="checkbox">
                    <label for="styles[shadow]">
                        <?php echo $form->checkbox('styles[shadow]', 1, (isset($formContent)) ? $formContent['styles']['shadow'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.shadow') : false)); ?>
                        <span><?php echo t('Yes'); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <?php
                echo $form->label('padding', t('Padding'));
                echo $form->number('styles[padding]', (isset($formContent)) ? $formContent['styles']['padding'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.padding') : 0), [ 'min' => 0, 'max' => 100 ]);
            ?>
        </div>

        <div class="form-group">
            <?php
                echo $form->label('margin', t('Margin'));
                echo $form->number('styles[margin]', (isset($formContent)) ? $formContent['styles']['margin'] : (($pkg) ? $pkg->getFileConfig()->get('lgt_toolkit.cookie_popup.styles.margin') : 0), [ 'min' => 0, 'max' => 100 ]);
            ?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?php echo t('Content'); ?></legend>

        <?php foreach ($locales as $locale) { ?>
            <div class="form-group">
                <label for="data[<?php echo $locale->getLanguage(); ?>]['title']" class="form-label"><?php echo t('Title (' . $locale->getLanguageText() . ')'); ?></label>
                <div class="float-end">
                    <span class="text-muted small"><?php echo t('Required'); ?></span>
                </div>
                <?php echo $form->text('data[' . $locale->getLanguage() . '][title]', (isset($formContent)) ? $formContent['data'][$locale->getLanguage()]['title'] : (($pkg) ? $pkg->getFileConfig()->get(sprintf('lgt_toolkit.cookie_popup.%s.title', $locale->getLanguage())) : false)); ?>
            </div>

            <div class="form-group">
                <label for="data[<?php echo $locale->getLanguage(); ?>]['content']" class="form-label"><?php echo t('Content (' . $locale->getLanguageText() . ')'); ?></label>
                <div class="float-end">
                    <span class="text-muted small"><?php echo t('Required'); ?></span>
                </div>
                <?php echo $form->textarea('data[' . $locale->getLanguage() . '][content]', (isset($formContent)) ? $formContent['data'][$locale->getLanguage()]['content'] : (($pkg) ? $pkg->getFileConfig()->get(sprintf('lgt_toolkit.cookie_popup.%s.content', $locale->getLanguage())) : false), ['rows' => '8']); ?>
            </div>
        <?php } ?>
    </fieldset>

    <fieldset>
        <legend><?php echo t('Links'); ?></legend>

        <?php foreach ($locales as $locale) { ?>
            <div class="form-group">
                <?php echo $form->label('data[' . $locale->getLanguage() . '][linkCID]', t('Full Policy (' . $locale->getLanguageText() . ')')); ?>
                <div class="float-end">
                    <span class="text-muted small"><?php echo t('Required'); ?></span>
                </div>
                <?php echo $form_page_selector->selectPage('data[' . $locale->getLanguage() . '][linkCID]', (isset($formContent)) ? $formContent['data'][$locale->getLanguage()]['linkCID'] : (($pkg) ? $pkg->getFileConfig()->get(sprintf('lgt_toolkit.cookie_popup.%s.linkCID', $locale->getLanguage())) : false)); ?>
            </div>

            <div class="form-group">
                <?php echo $form->label('data[' . $locale->getLanguage() . '][linkText]', t('Full Policy Link Text (' . $locale->getLanguageText() . ')')); ?>
                <?php echo $form->text('data[' . $locale->getLanguage() . '][linkText]', (isset($formContent)) ? $formContent['data'][$locale->getLanguage()]['linkText'] : (($pkg) ? $pkg->getFileConfig()->get(sprintf('lgt_toolkit.cookie_popup.%s.linkText', $locale->getLanguage())) : false)); ?>
            </div>
        <?php } ?>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <?php echo $form->submit('save', t('Save Settings'), array('class' => 'btn btn-primary float-end')); ?>
        </div>
    </div>
</form>
