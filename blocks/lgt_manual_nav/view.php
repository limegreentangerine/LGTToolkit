<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($c) && is_object($c) && $c->isEditMode() && empty($links)) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Empty Manual Nav Block.') ?></div>
<?php } elseif (isset($links)) { ?>
    <aside id="<?php echo $bID ?? null; ?>" class="block__lgt-manual-nav">
        <?php if (isset($title) && strlen($title) > 0) { ?>
            <h3 class="block__lgt-manual-nav--title"><?php echo $title; ?></h3>
        <?php } ?>

        <ul class="block__lgt-manual-nav--list">
            <?php foreach ($links as $link) { ?>
                <li class="block__lgt-manual-nav--item">
                    <a href="<?php echo $link->getPath(); ?>" class="block__lgt-manual-nav--link" target="<?php echo $link->getTarget(); ?>">
                        <?php echo $link->getTitle(); ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </aside>
<?php } ?>
