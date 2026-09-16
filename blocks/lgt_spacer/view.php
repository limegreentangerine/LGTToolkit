<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($c) && is_object($c) && $c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Spacer Block.'); ?></div>
<?php } else { ?>
    <div class="d-block d-md-none" style="height:<?php echo (isset($height_mobile)) ? $height_mobile : 40; ?>px;"></div>
    <div class="d-none d-md-block d-xl-none" style="height:<?php echo (isset($height_tablet)) ? $height_tablet : 40; ?>px;"></div>
    <div class="d-none d-xl-block d-hd-none" style="height:<?php echo (isset($height_desktop)) ? $height_desktop : 100; ?>px;"></div>
    <div class="d-none d-hd-block" style="height:<?php echo (isset($height_hd)) ? $height_hd : 100; ?>px;"></div>
    <div class="d-none d-4k-block d-4k-none" style="height:<?php echo (isset($height_4k)) ? $height_4k : 120; ?>px;"></div>
<?php } ?>
