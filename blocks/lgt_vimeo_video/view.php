<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<?php if (is_object($c) && $c->isEditMode()) { ?>
	<div class="ccm-edit-mode-disabled-item"><?php echo t('Vimeo Video'); ?></div>
<?php } else { ?>
	<section id="<?php echo $bID; ?>" class="block__lgt-vimeo-video">
		<div class="block__lgt-vimeo-video--wrapper">
			<div class="block__lgt-vimeo-video--responsive">
				<iframe src="//player.vimeo.com/video/<?php echo $vimeoVid ?>?autoplay=<?php echo $autoplay ?>&loop=<?php echo $vvloop ?>&title=<?php echo $introTitle ?>&byline=<?php echo $byline ?>&portrait=<?php echo $portrait ?>&color=<?php echo $vimeoColor ?>"
					width="<?php echo $vvWidth ?>"
					height="<?php echo $vvHeight ?>"
					frameborder="0"
					webkitallowfullscreen
					mozallowfullscreen
					allowfullscreen>
				</iframe>
			</div>

			<?php if ($showlink == 1) { ?>
				<p>
					<?php echo t('<a href="http://vimeo.com/%s" target="blank">%s</a> from <a href="http://vimeo.com/%s" target="_blank">%s</a> on <a href="https://vimeo.com" target="_blank">Vimeo</a>.', $vimeoVid, $vvTitle, $vvUser, $vvUser); ?>
				</p>
			<?php } ?>
		</div>
	</section>
	<?php /*
<div class="vimeoVidWrap"><div class="vvResponsive">
	<iframe src="//player.vimeo.com/video/<?php echo $vimeoVid ?>?autoplay=<?php echo $autoplay ?>&loop=<?php echo $vvloop ?>&title=<?php echo $introTitle ?>&byline=<?php echo $byline ?>&portrait=<?php echo $portrait ?>&color=<?php echo $vimeoColor ?>" width="<?php echo $vvWidth ?>" height="<?php echo $vvHeight ?>" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
	<?php if ($showlink == 1) { ?><p><a href="http://vimeo.com/<?php echo $vimeoVid ?>" target="blank"><?php echo $vvTitle ?></a><?php echo t(' from ')?><a href="http://vimeo.com/<?php echo $vvUser ?>" target="_blank"><?php echo $vvUser ?></a><?php echo t(' on ')?><a href="https://vimeo.com" target="_blank"><?php echo t('Vimeo')?></a>.</p><?php } ?>
</div></div> */ ?>
<?php } ?>
