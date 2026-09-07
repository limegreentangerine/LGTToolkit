<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($c) && is_object($c) && $c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Anchor Menu.'); ?></div>
<?php } elseif (isset($navigation) && count($navigation) > 0) { ?>
    <section id="<?php echo $bID; ?>" class="block__anchor-nav">
        <div class="container">
            <ul class="anchor-nav__list">
                <?php foreach ($navigation as $link) { ?>
                    <li class="anchor-nav__item">
                        <a href="#<?php echo $link['target']; ?>" class="anchor-nav__link">
                            <?php echo $link['name']; ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </section>
<?php } ?>
