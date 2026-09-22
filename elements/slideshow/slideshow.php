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
?>

<component-slideshow>
    <section class="component-slideshow"
        data-options='<?php echo $slideshow->getOptions(true); ?>'
    >
        <div class="component-slideshow__track">
            <?php foreach ($slideshow->getSlides() as $slide) { ?>
                <div class="component-slideshow__slide">slide</div>
            <?php } ?>
        </div>

        <div class="component-slideshow__buttons">
            <button type="button" class="component-slideshow__buttons--prev"><i class="bi <?php echo $slideshow->getOption('prevIcon'); ?>"></i></button>
            <button type="button" class="component-slideshow__buttons--next"><i class="bi <?php echo $slideshow->getOption('nextIcon'); ?>"></i></button>
        </div>

        <div class="component-slideshow__pagination"></div>
    </section>
</component-slideshow>
