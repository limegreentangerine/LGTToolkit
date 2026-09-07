<?php defined('C5_EXECUTE') or die('Access Denied.') ?>

<?php if (isset($c) && is_object($c) && $c->isEditMode()) { ?>
    <div id="<?php echo $target; ?>" class="ccm-edit-mode-disabled-item"><?php echo t('Anchor Target: #%s.', $target); ?></div>
<?php } else { ?>
    <div id="<?php echo $target; ?>" class="d-block"></div>
<?php } ?>
