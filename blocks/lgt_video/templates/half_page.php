<?php defined('C5_EXECUTE') or die('Access Denied.');
    $c = Page::getCurrentPage();
?>

<?php if ($video) { ?>
    <?php if (is_object($c) && $c->isEditMode()) { ?>
        <div class="ccm-edit-mode-disabled-item"><?php echo t('LGT Video Unavailable in Edit Mode. (%s)', $video->getTitle()); ?></div>
    <?php } else { ?>
        <div class="container">
            <div id="<?php echo $bID; ?>" class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <section class="block__lgt-video <?php echo ($square) ? 'lgt-video__square' : ''; ?>">
                        <video
                            class="lgt-video <?php echo ($autoplay) ? 'lgt-video-autoplay' : ''; ?>"
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
            </div>
        </div>
    <?php } ?>
<?php } ?>
