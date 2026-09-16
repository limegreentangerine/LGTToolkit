<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($video)) { ?>
    <?php if (isset($c) && is_object($c) && $c->isEditMode()) { ?>
        <div class="ccm-edit-mode-disabled-item"><?php echo t('LGT Video Unavailable in Edit Mode. (%s)', $video->getTitle()); ?></div>
    <?php } else { ?>
        <div class="container">
            <section id="<?php echo $bID; ?>" class="block__lgt-video <?php echo (isset($square) && $square === true) ? 'lgt-video__square' : ''; ?>">
                <video
                    class="lgt-video <?php echo (isset($autoplay) && $autoplay === true) ? 'lgt-video-autoplay' : ''; ?>"
                    playsinline
                    loop
                    preload="auto"
                    <?php echo ($poster) ? 'poster="' . $poster->getRelativePath() . '"' : ''; ?>
                    <?php echo ($muted) ? 'muted' : ''; ?>
                    <?php echo ($controls) ? 'controls' : ''; ?>
                >
                    <source src="<?php echo $video->getRelativePath(); ?>" type="<?php echo $video->getMimeType(); ?>" />
                </video>
            </section>
        </div>
    <?php } ?>
<?php } ?>
