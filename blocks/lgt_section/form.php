<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Image'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('media', t('Media'));
            echo $al->file('media', 'media', t('Choose file'), $media ?? null, [ 'filters' => $filters ?? [] ]);
        ?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Content'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('title', t('Title'));
            echo $form->text('title', $title ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('titleSize', t('Title Size'));
            echo (string) $form->select('titleSize', $this->controller->getHeaderSizes() ?? [], $titleSize ?? 3);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('content', t('Content'));
            $editor = $this->app->make('editor');
            echo $editor->outputBlockEditModeEditor('content', $content ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('contentSize', t('Content Size'));
            echo (string) $form->select('contentSize', $this->controller->getContentSizes(), $contentSize ?? 3);
        ?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Link'); ?></legend>

    <lgt-section-link-type>
        <div class="form-group">
            <?php
                echo $form->label('linkType', t('Link Type'));
                echo (string) $form->select('linkType', (isset($linkTypes)) ? $linkTypes : [], $linkType ?? null, [ 'class' => 'lgt-section-link-select' ]);
            ?>
        </div>

        <?php if (isset($linkTypes) && isset($ps)) { ?>
            <?php foreach ($linkTypes as $id => $name) {
                if (!is_int($id)) continue;
                $handle = $th->handle($name);
                $displayClass = (!isset($linkType) || $linkType !== $id) ? 'visually-hidden' : '';
            ?>
                <div id="LinkType-<?php echo $id; ?>" class="form-group <?php echo $displayClass; ?>" data-id="<?php echo $id; ?>">
                    <?php
                        echo $form->label($handle, t('%s Link', $name));
                        $globalVars = get_defined_vars();
                        switch($id) {
                            case 0:
                                echo $ps->selectPage($handle, (isset($globalVars[$handle]) ? $globalVars[$handle] : null));
                                break;
                            case 2:
                                echo $al->file($handle, $handle, t('Choose file'), (isset($globalVars[$handle]) ? $globalVars[$handle] : null));
                                break;
                            default:
                                echo $form->text($handle, (isset($globalVars[$handle]) ? $globalVars[$handle] : null));
                                break;
                        }
                    ?>
                </div>
            <?php } ?>
        <?php } ?>
    </lgt-section-link-type>

    <div class="form-group">
        <?php
            echo $form->label('linkText', t('Link Text'));
            echo $form->text('linkText', $linkText ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('buttonSize', t('Button Size'));
            echo (string) $form->select('buttonSize', $this->controller->getButtonSizes(), $buttonSize ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('buttonStyle', t('Button Style'));
            echo (string) $form->select('buttonStyle', $this->controller->getButtonStyles(), $buttonStyle ?? null);
        ?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Colours'); ?></legend>

    <div class="form-group">
        <?php $color->output('backgroundColor', $backgroundColor ?? null, [ 'preferredFormat' => 'hex' ]); ?>
        <?php echo $form->label('backgroundColor', t('Background Colour')); ?>
    </div>

    <div class="form-group">
        <?php $color->output('foregroundColor', $foregroundColor ?? null, [ 'preferredFormat' => 'hex' ]); ?>
        <?php echo $form->label('foregroundColor', t('Foreground Colour')); ?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Content Arrangement'); ?></legend>

    <div class="form-group">
        <?php
            echo $form->label('imageColumnSize', t('Image Column Size'));
            echo (string) $form->select('imageColumnSize', $this->controller->getColumns(), $imageColumnSize ?? 6);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('contentColumnSize', t('Content Column Size'));
            echo (string) $form->select('contentColumnSize', $this->controller->getColumns(), $contentColumnSize ?? 6);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('gutterSize', t('Gutter Size'));
            echo (string) $form->select('gutterSize', $this->controller->getGutterSizes(), $gutterSize ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('columnArrangement', t('Column Arrangement'));
            echo (string) $form->select('columnArrangement', $this->controller->getContentArrangementOptions(), $columnArrangement ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('horizontalAlignment', t('Horizontal Alignment'));
            echo (string) $form->select('horizontalAlignment', $this->controller->getHorizontalFlexClasses(), $horizontalAlignment ?? null);
        ?>
    </div>

    <div class="form-group">
        <?php
            echo $form->label('verticalAlignment', t('Vertical Alignment'));
            echo (string) $form->select('verticalAlignment', $this->controller->getVerticalFlexClasses(), $verticalAlignment ?? null);
        ?>
    </div>
</fieldset>
