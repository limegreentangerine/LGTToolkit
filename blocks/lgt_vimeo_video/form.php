<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
	<legend><?php echo t('Video Details'); ?></legend>

	<div class="form-group">
		<?php
            echo $form->label('vimeoVid', t('<a href="http://www.vimeo.com" target="_blank">Vimeo</a> Video ID'));
echo $form->text('vimeoVid', $vimeoVid ?? null);
?>
	</div>

	<div class="form-group">
		<?php
    echo $color->output('vvColor', (isset($vvColor)) ? $vvColor : '#00adef', [ 'preferredFormat' => 'hex' ]);
echo $form->label('vvColor', t('Color'));
?>
	</div>
</fieldset>

<fieldset>
	<legend><?php echo t('Sizing'); ?></legend>

	<div class="form-group">
		<?php  echo $form->label('vvWidth', t('Width'));?>
		<div class="input-group input-group-sm">
			<?php  echo $form->text('vvWidth', (isset($vvWidth)) ? $vvWidth : '500'); ?>
			<span class="input-group-text"><?php echo t('px')?></span>
		</div>
	</div>

	<div class="form-group">
		<?php echo $form->label('vvHeight', t('Height'));?>
		<div class="input-group input-group-sm">
			<?php echo $form->text('vvHeight', (isset($vvHeight)) ? $vvHeight : '280'); ?>
			<span class="input-group-text"><?php echo t('px')?></span>
		</div>
	</div>
</fieldset>

<fieldset>
	<legend><?php echo t('Settings'); ?></legend>

	<div class="form-group">
		<?php
    echo $form->label('vvTitle', t('Video Title'));
echo $form->text('vvTitle', $vvTitle ?? null);
?>
	</div>

	<div class="form-group">
		<?php echo $form->label('vvUser', t('Vimeo Username'));?>
		<div class="input-group input-group-sm">
			<span class="input-group-text">@</span>
			<?php echo $form->text('vvUser', $vvUser ?? null);?>
		</div>
	</div>

	<div class="form-group">
		<h4><?php echo t('Intro')?></h4>

		<div class="form-check">
			<input type="checkbox" id="introTitle" name="introTitle" value="1" <?php if (isset($introTitle) && $introTitle == 1) { ?> checked <?php } ?>  />
			<label for="introTitle" class="form-check-label"><?php echo t('Title'); ?></label>
		</div>

		<div class="form-check">
			<input type="checkbox" id="portrait" name="portrait" value="1" <?php if (isset($portrait) && $portrait == 1) { ?> checked <?php } ?>  />
			<label for="portrait" class="form-check-label"><?php echo t('Portrait'); ?></label>
		</div>

		<div class="form-check">
			<input type="checkbox" id="byline" name="byline" value="1" <?php if (isset($byline) && $byline == 1) { ?> checked <?php } ?>  />
			<label for="byline" class="form-check-label"><?php echo t('Byline'); ?></label>
		</div>
	</div>

	<div class="form-group">
		<h4><?php echo t('Other')?></h4>

		<div class="form-check">
			<input type="checkbox" id="autoplay" name="autoplay" value="1" <?php if (isset($autoplay) && $autoplay == 1) { ?> checked <?php } ?>  />
			<label for="autoplay" class="form-check-label"><?php echo t('Autoplay video'); ?></label>
		</div>

		<div class="form-check">
			<input type="checkbox" id="vvloop" name="vvloop" value="1" <?php if (isset($vvloop) && $vvloop == 1) { ?> checked <?php } ?>  />
			<label for="vvloop" class="form-check-label"><?php echo t('Loop video'); ?></label>
		</div>

		<div class="form-check">
			<input type="checkbox" id="showlink" name="showlink" value="1" <?php if (isset($showlink) && $showlink == 1) { ?> checked <?php } ?>  />
			<label for="showlink" class="form-check-label"><?php echo t('Display link under video'); ?></label>
		</div>
	</div>
</fieldset>
