

<div class="row">
    <div class="col-md-4 ccm-dashboard-section-menu">
        <img src="<?php echo $this->controller->getPageThumbnail('debug_bar'); ?>" class="d-block mx-auto" width="100" height="100" alt="Debug Bar" />
        <h4 class="text-center my-4"><?php echo t('Debug Bar'); ?></h4>
        <div class="d-flex justify-content-center">
            <a href="<?php echo $this->action('toggle_debugbar'); ?>" class="btn <?php echo ($this->controller->getDebugBarStatus()) ? 'btn-danger' : 'btn-primary'; ?>">
                <?php echo ($this->controller->getDebugBarStatus()) ? t('Deactivate') : t('Activate'); ?>
            </a>
        </div>
    </div>

    <?php if (isset($pages)) { ?>
        <?php foreach ($pages as $page) { ?>
            <div class="col-md-4 ccm-dashboard-section-menu">
                <img src="<?php echo $this->controller->getPageThumbnail($page->getCollectionHandle()); ?>" class="d-block mx-auto" width="100" height="100" alt="<?php echo $page->getCollectionName(); ?>" />
                <h4 class="text-center my-4"><?php echo $page->getCollectionName(); ?></h4>
                <div class="d-flex justify-content-center">
                    <a href="<?php echo $page->getCollectionLink(); ?>" class="btn btn-primary"><?php echo t('View'); ?></a>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
</div>
