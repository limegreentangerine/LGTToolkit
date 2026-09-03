<?php defined('C5_EXECUTE') or die('Access Denied.');
if (count($locales) > 1 || $expressObjects !== false) {
?>
    <fieldset>
        <p><?php echo t('Process Express Objects for %s Languages', count($locales)); ?></p>

        <div class="form-group">
            <a href="<?php echo URL::to('/duplicate/express'); ?>" target="_blank" class="btn btn-primary"><?php echo t('Process'); ?></a>
        </div>
    </fieldset>

<?php } else { ?>
    <?php if (count($locales) < 2) { ?>
        <div class="alert alert-danger"><?php echo t('You must have more than one language installed.'); ?></div>
    <?php } ?>

    <?php if (!$expressObjects) { ?>
        <div class="alert alert-danger"><?php echo t('You must have at least one public express object created.'); ?></div>
    <?php } ?>
<?php } ?>
