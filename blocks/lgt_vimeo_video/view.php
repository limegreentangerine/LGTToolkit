<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<?php if (isset($c) && is_object($c) && $c->isEditMode()) { ?>
	<div class="ccm-edit-mode-disabled-item"><?php echo t('Vimeo Video'); ?></div>
<?php } else if (isset($vimeoVid)) { ?>
	<section id="<?php echo $bID; ?>" class="block__lgt-vimeo-video">
		<div class="block__lgt-vimeo-video--wrapper">
			<div class="block__lgt-vimeo-video--responsive <?php echo $this->controller->portrait() ? 'portrait' : ''; ?>">
				<iframe src="//player.vimeo.com/video/<?php echo $vimeoVid ?>?autoplay=<?php echo $this->controller->autoplay(); ?>&loop=<?php echo $this->controller->loop(); ?>&title=<?php echo $this->controller->showIntroTitle(); ?>&byline=<?php echo $this->controller->showByline(); ?>&portrait=<?php echo $this->controller->portrait(); ?><?php echo (isset($vimeoColor)) ? '&color=' . $vimeoColor . '' : ''; ?>"
					width="<?php echo $vvWidth ?? 500; ?>"
					height="<?php echo $vvHeight ?? 200; ?>"
					frameborder="0"
					webkitallowfullscreen
					mozallowfullscreen
					allowfullscreen>
				</iframe>
			</div>

			<?php if ($this->controller->showLink()) { ?>
				<p class="block__lgt-vimeo-video--link">
					<?php echo t('<a href="http://vimeo.com/%s" target="blank">%s</a> from <a href="http://vimeo.com/%s" target="_blank">%s</a> on <a href="https://vimeo.com" target="_blank">Vimeo</a>.', $vimeoVid, $vvTitle ?? '', $vvUser ?? '', $vvUser ?? ''); ?>
				</p>
			<?php } ?>
		</div>
	</section>
<?php } ?>
