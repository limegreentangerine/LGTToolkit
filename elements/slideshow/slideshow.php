<?php

use LgtToolkit\Slideshow\Slideshow;
use Concrete\Core\Error\UserMessageException;

$html = \Core::make('helper/html');
$slideshow = new Slideshow($slides ?? [], $options ?? []);

if (!$slideshow) {
    throw new UserMessageException(t('Error creating slideshow'));
}

$slideshow->getView()->addHeaderItem($html->css($slideshow->stylesheet(), 'lgt_toolkit'));
$slideshow->getView()->addFooterItem($html->javascript($slideshow->javascript(), 'lgt_toolkit'));
if ($slideshow->getOption('useTheme') === true) {
    $slideshow->getView()->addHeaderItem($html->css($slideshow->theme_stylesheet(), 'lgt_toolkit'));
}
?>

<component-slideshow>
    <section class="component-slideshow"
        data-options='<?php echo $slideshow->getOptions(true); ?>'
    >
        <?php if ($slideshow->getArrayType() == 'string') { ?>
            <div class="component-slideshow__track">
                <?php foreach ($slideshow->getSlides() as $index => $slide) { ?>
                    <div class="component-slideshow__slide"><?php echo $slide; ?></div>
                <?php } ?>
            </div>
        <?php } elseif ($slideshow->getArrayType() == 'object') { ?>
            <?php if ($slideshow->getOption('template')) { ?>
                <div class="component-slideshow__track">
                    <?php foreach ($slideshow->getSlides() as $index => $slide) { ?>
                        <div class="component-slideshow__slide">
                            <?php
                                echo $slideshow->getView()->element($slideshow->getOption('template')['path'], [
                                    'index' => $index,
                                    'data' => $slide,
                                ], $slideshow->getOption('template')['pkgHandle']);
                        ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="alert alert-danger w-100"><?php echo t('Arrays of type object[] require a template to be set. See LGT Toolkit developer documentation.'); ?></div>
            <?php } ?>
        <?php } ?>

        <div class="component-slideshow__pagination"></div>

        <div class="component-slideshow__buttons">
            <button type="button" class="component-slideshow__buttons--prev" title="Prev"><i class="bi <?php echo $slideshow->getOption('prevIcon'); ?>"></i></button>
            <button type="button" class="component-slideshow__buttons--next" title="Next"><i class="bi <?php echo $slideshow->getOption('nextIcon'); ?>"></i></button>
        </div>
    </section>
</component-slideshow>
