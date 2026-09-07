<?php
defined('C5_EXECUTE') or die("Access Denied.");
/**
 * @var $file \Concrete\Core\Entity\File\Version
 */
?>
<form method="post" data-dialog-form="save-focal-point" action="<?php echo $controller->action('submit') ?>">
    <?php if ($focal_point) { ?>
        <?php echo $form->hidden('fpID', $focal_point->getID()); ?>
    <?php } ?>
    <?php echo $form->hidden('fpX', (($focal_point) ? $focal_point->getX() : '50')); ?>
    <?php echo $form->hidden('fpY', (($focal_point) ? $focal_point->getY() : '50')); ?>
    <?php echo $form->hidden('fpDelete', 0); ?>

    <div class="d-flex justify-content-center">
        <div class="focal-point__container">
            <img src="<?php echo $file->getRelativePath(); ?>" class="focal-point__image" />
            <div class="focal-point__register">
                <div class="focal-point__indicator <?php echo ($focal_point) ? 'active' : ''; ?>" style="top:<?php echo ($focal_point) ? $focal_point->getY() : '0'; ?>%;left: <?php echo ($focal_point) ? $focal_point->getX() : '0'; ?>%"></div>
            </div>
        </div>

    </div>

    <?php if ($focal_point) { ?>
        <p class="text-center mt-3">
            <a href="#" class="btn btn-sm btn-delete clear-focal-point"><?php echo t('Clear Focal Point'); ?></a>
        </p>
    <?php } ?>

    <div class="dialog-buttons">
        <button class="btn btn-secondary float-start" data-dialog-action="cancel">
            <?php echo t('Cancel') ?>
        </button>

        <button type="button" data-dialog-action="submit" class="btn btn-primary float-end">
            <?php echo t('Save') ?>
        </button>
    </div>
</form>

<script>
    $(function() {
        ConcreteEvent.subscribe('AjaxFormSubmitSuccess', function(e, data) {
            if (data.form === 'save-focal-point') {
                window.location.reload();
            }
        });

        $('.focal-point__register').on('click', function(e) {
            var $this = $(e.currentTarget);
            var parentOffset = $(this).parent().offset();
            var relX = e.pageX - parentOffset.left;
            var relY = e.pageY - parentOffset.top;
            var percentX = ((relX / $this.width()) * 100).toFixed(2);
            var percentY = ((relY / $this.height()) * 100).toFixed(2);

            $('.focal-point__indicator').addClass('active');
            $('.focal-point__indicator').css({'left': relX, 'top': relY});
            $('#fpX').val(percentX);
            $('#fpY').val(percentY);
            $('#fpDelete').val(0);
        });

        $('.clear-focal-point').on('click', function(e) {
            e.preventDefault();
            $('#fpX').val(50);
            $('#fpY').val(50);
            $('.focal-point__indicator').removeClass('active');
            $('.focal-point__indicator').css({'left': 0, 'top': 0});
            $('#fpDelete').val(1);
        });
    })
</script>
