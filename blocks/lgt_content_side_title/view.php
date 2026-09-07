<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<section id="<?php echo $bID; ?>" class="block__lgt-content-side-title" <?php echo $background; ?>>
    <div class="container">
        <div class="row <?php echo isset($gutter_size) ? $gutter_size : ''; ?> <?php echo isset($content_spacing) ? $content_spacing : ''; ?> <?php echo isset($content_arrangement) ? $content_arrangement : ''; ?> <?php echo isset($content_alignment) ? $content_alignment : ''; ?>">
            <div class="col-12 col-lg-<?php echo isset($size_title) ? $size_title : 12; ?>">
                <h3><?php echo isset($title) ? $title : ''; ?></h3>
            </div>

            <div class="col-12 col-lg-<?php echo isset($size_content) ? $size_content : 12; ?>">
                <?php echo isset($content) ? $content : ''; ?>

                <?php if (isset($page) && is_object($page)) { ?>
                    <a href="<?php echo $page->getCollectionLink(); ?>" class="btn btn-primary"><?php echo isset($buttonText) ? $buttonText : t('Learn more'); ?></a>
                <?php } ?>
            </div>
        </div>
    </div>
</section>
