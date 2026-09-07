<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($c) && is_object($c) && $c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('LGT Video Unavailable in Edit Mode.%s', (isset($video)) ? '(' . $video->getTitle() . ')' : ''); ?></div>
<?php } elseif (isset($video)) { ?>
    <component-video>
        <section id="<?php echo $bID ?? null; ?>" class="block__lgt-video <?php echo ($this->controller->isSquare()) ? 'lgt-video__square' : ''; ?>">
            <video
                class="lgt-video <?php echo ($this->controller->showControls()) ? 'lgt-video-controls' : ''; ?> <?php echo ($this->controller->autoplay()) ? 'lgt-video-autoplay' : ''; ?>"
                playsinline
                loop
                preload="auto"
                <?php echo ($this->controller->muted()) ? 'muted' : ''; ?>
                <?php echo (isset($poster)) ? 'poster="' . $poster->getRelativePath() . '"' : ''; ?>
            >
                <source src="<?php echo $video->getRelativePath(); ?>" type="<?php echo $video->getMimeType(); ?>" />
            </video>

            <?php if ($this->controller->showControls()) { ?>
                <div class="block__lgt-video--controls" style="--pageThemeColor: <?php echo (isset($c) && $c->getAttribute('case_study_header_colour')) ? $c->getPageController()->getRGBA($c->getAttribute('case_study_header_colour')) : '0,0,0'; ?>">
                    <div class="block__lgt-video--controls--play">
                        <i class="bi bi-play-fill"></i>
                    </div>

                    <div class="block__lgt-video--controls--pause">
                        <i class="bi bi-pause-fill"></i>
                    </div>

                    <div class="block__lgt-video--controls--progress">
                        <div class="progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar"></div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </section>
    </component-video>
<?php } ?>
