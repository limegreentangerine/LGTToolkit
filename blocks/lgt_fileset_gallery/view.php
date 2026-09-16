<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<section id="<?php echo $bID ?? null; ?>" class="block__lgt-file-set-gallery">
    <div class="container">
        <?php if ((isset($title) && strlen($title) > 0) || (isset($content) && strlen($content) > 0)) { ?>
            <div class="row mb-5">
                <div class="col-12">
                    <?php if (isset($title) && strlen($title) > 0) { ?>
                        <h4><?php echo $title; ?></h4>
                    <?php } ?>
                    <?php echo $content ?? ''; ?>
                </div>
            </div>
        <?php } ?>

        <?php if (!empty($files)) { ?>
            <div class="row">
                <?php foreach ($files as $file) {
                    $filePath = (is_array($file) && isset($file['path'])) ? $file['path'] : $file->getRelativePath();
                    $fileTitle = (is_array($file) && isset($file['title'])) ? $file['title'] : $file->getTitle();
                    ?>
                    <div class="col-12 col-lg-4">
                        <img src="<?php echo $filePath; ?>" class="img-fluid" alt="<?php echo $fileTitle; ?>" />
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="row">
                <div class="col-12">
                    <h5><?php echo t('File Set Empty'); ?></h5>
                </div>
            </div>
        <?php } ?>
    </div>
</section>
