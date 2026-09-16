<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <legend><?php echo t('Video File & Poster Image'); ?></legend>

    <div class="form-group">
        <?php echo $form->label('videofID', t('Video')); ?>
        <span class="text-muted small"><?php echo t('Required'); ?></span>
        <?php echo $al->video('videofID', 'videofID', t('Choose video (mp4)'), $videofID ?? null, $controller->getVideoFilters()); ?>
    </div>

    <div class="form-group">
        <?php echo $form->label('posterfID', t('Poster')); ?>
        <span class="help-block mt-0"><?php echo t('Usually the first frame of the video, used while video is loading.'); ?></span>
        <?php echo $al->image('posterfID', 'posterfID', t('Choose Image'), $posterfID ?? null); ?>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Video Settings'); ?></legend>

    <div class="form-group">
        <div class="form-check">
            <?php echo $form->checkbox('autoplay', 1, $autoplay ?? false); ?>
            <?php echo $form->label('autoplay', t('Autoplay'), ['class' => 'form-check-label']); ?>
        </div>

        <div class="form-check">
            <?php echo $form->checkbox('muted', 1, $muted ?? false); ?>
            <?php echo $form->label('muted', t('Mute'), ['class' => 'form-check-label']); ?>
        </div>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Display Settings'); ?></legend>

    <div class="form-group">
        <div class="form-check">
            <?php echo $form->checkbox('controls', 1, $controls ?? false); ?>
            <?php echo $form->label('controls', t('Show Controls'), ['class' => 'form-check-label']); ?>
        </div>

        <div class="form-check">
            <?php echo $form->checkbox('square', 1, $square ?? false); ?>
            <?php echo $form->label('square', t('Square Video'), ['class' => 'form-check-label']); ?>
        </div>
    </div>
</fieldset>
