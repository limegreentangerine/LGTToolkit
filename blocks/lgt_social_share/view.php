<?php defined('C5_EXECUTE') or die('Access Denied.');
//TODO: change this to use navigator share on mobile
?>

<?php if (empty($shareLinks) && (isset($c) && is_object($c) && $c->isEditMode())) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Empty Social Share.'); ?></div>
<?php } else { ?>
    <section id="<?php echo $bID; ?>" class="block__lgt-social-share">
        <div class="container">
            <?php if (isset($title) && strlen($title) > 0) { ?>
                <div class="row">
                    <div class="col-12">
                        <h4 class="block__lgt-social-share--title"><?php echo $title; ?></h4>
                    </div>
                </div>
            <?php } ?>

            <?php if (!empty($shareLinks) && (isset($c) && is_object($c))) { ?>
                <ul class="block__lgt-social-share--list">
                    <?php foreach ($shareLinks as $link) {
                        $service = $link->getSocialNetwork();
                        ?>
                        <?php if ($service) { ?>
                            <li class="block__lgt-social-share--item">
                                <a href="<?php echo $service->getSharerLink($c->getCollectionLink(), $c->getCollectionName()); ?>" class="block__lgt-social-share--link" target="_blank">
                                    <?php echo $service->getServiceIconHTML(); ?>
                                    <span><?php echo t('Share on %s', $service->getName()); ?></span>
                                </a>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </section>
<?php } ?>
