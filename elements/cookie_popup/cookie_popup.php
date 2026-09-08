<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<component-cookie-popup>
    <div class="lgt-cookie-popup <?php echo (isset($activate) && $activate) ? 'activate' : ''; ?>" style="--lgtBgColor:<?php echo sprintf('var(--bs-%s)', (isset($styles)) ? $styles['bgColor'] : 'primary'); ?>;--lgtFgColor:<?php echo sprintf('var(--bs-%s)', (isset($styles)) ? $styles['fgColor'] : 'secondary'); ?>;--lgtRounded:<?php echo ((isset($styles)) && $styles['roundedCorners']) ? 'var(--bs-border-radius)' : '0px'; ?>;--lgtShadow:<?php echo ((isset($styles)) && $styles['shadow']) ? '0.35' : '0'; ?>;">
        <?php if (isset($title)) { ?>
            <h4><?php echo $title; ?></h4>
        <?php } ?>

        <?php if (isset($content)) { ?>
            <p><?php echo $content; ?></p>
        <?php } ?>

        <?php if (isset($policyLink) && $policyLink) { ?>
            <p><a href="<?php echo $policyLink; ?>"><?php echo $linkText ?? t('Click here'); ?></a></p>
        <?php } ?>

        <div class="d-flex justify-content-start">
            <button class="lgt-cookie-popup__button--allow btn btn-<?php echo (isset($styles)) ? $styles['acceptAllStyle'] : ''; ?> me-2"><?php echo t('Allow all'); ?></button>
            <button class="lgt-cookie-popup__button--disallow btn btn-<?php echo (isset($styles)) ? $styles['acceptRequiredStyle'] : ''; ?> me-2"><?php echo t('Allow required'); ?></button>
        </div>
    </div>
</component-cookie-popup>
