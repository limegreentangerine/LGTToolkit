<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<section id="<?php echo $bID; ?>" class="block__lgt-button">
    <div class="container">
        <a href="<?php echo $link ?? ''; ?>" class="<?php echo isset($button_style) ? implode(' ', $button_style) : ''; ?>" <?php echo isset($new_window) ? 'target="_blank"' : ''; ?>><?php echo $button_text ?? t('Click here'); ?></a>
    </div>
</section>
