<section class="lgt-section" style="<?php echo (isset($colors)) ? $colors : ''; ?>">
    <div class="container">
        <div class="row <?php echo (isset($gutter)) ? $gutter : ''; ?> <?php echo (isset($columnArrangement)) ? $columnArrangement : ''; ?> <?php echo (isset($horizontalAlignment)) ? $horizontalAlignment : ''; ?> <?php echo (isset($verticalAlignment)) ? $verticalAlignment : ''; ?>">
            <div class="col-12 col-lg-<?php echo (isset($imageColumnSize)) ? $imageColumnSize : 12; ?>">
                <?php if (isset($mediaFile) && $mediaFile) {
                    $approvedVersion = $mediaFile->getApprovedVersion();
                    $mime = $approvedVersion->getMimeType();
                ?>
                    <div class="lgt-section__media--container">
                        <?php
                            if (str_contains($mime, 'image')) {
                                \View::element('picture', [
                                    'f' => $mediaFile,
                                    'classes' => [
                                        'lgt-section__media'
                                    ],
                                    'altText' => $mediaFile->getTitle()
                                ]);
                            } else {
                        ?>
                            <video class="lgt-section__media" playsinline autoplay loop muted preload>
                                <source src="<?php echo $mediaFile->getRelativePath(); ?>" type="<?php echo $mime; ?>" />
                            </video>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <div class="col-12 col-lg-<?php echo (isset($contentColumnSize)) ? $contentColumnSize : 12; ?>">
                <?php if (isset($title) && strlen($title) > 0) { ?>
                    <<?php echo (isset($headingClass)) ? $headingClass : 'h3'; ?> class="lgt-section__title"><?php echo $title; ?></<?php echo (isset($headingClass)) ? $headingClass : 'h3'; ?>>
                <?php } ?>

                <?php if (isset($content) && strlen($content) > 0) { ?>
                    <div class="lgt-section__content <?php echo (isset($contentSize)) ? $contentSize : ''; ?>"><?php echo $content; ?></div>
                <?php } ?>

                <?php if (isset($linkUrl) && strlen($linkUrl) > 0) { ?>
                    <div class="lgt-section__link">
                        <a href="<?php echo $linkUrl; ?>" class="<?php echo (isset($buttonSize)) ? $buttonSize : ''; ?> <?php echo (isset($buttonStyle)) ? $buttonStyle : 'btn btn-primary'; ?>" target="<?php echo $target; ?>">
                            <?php echo (isset($linkText)) ? $linkText : t('Learn more'); ?>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>
