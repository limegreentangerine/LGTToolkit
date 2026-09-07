<?php if (isset($file)) { ?>
    <div class="d-flex align-items-center">
        <?php if (in_array($file->getApprovedVersion()->getExtension(), $supported ?? [])) { ?>
            <div class="flex-shrink-0">
                <img class="img-fluid" src="<?php echo $file->getRelativePath(); ?>" alt="<?php echo $file->getTitle(); ?>" />
            </div>
        <?php } ?>

        <div class="flex-grow-1 ms-3">
            <h5><?php echo $file->getTitle(); ?></h5>
            <p class="m-0"><small><?php echo t('Last updated: %s', $file->getDateAdded()->format('d/m/Y')); ?></small></p>
            <p class="m-0"><small><?php echo $file->getSize(); ?></small></p>

            <a href="<?php echo $file->getDownloadURL(); ?>" class="btn btn-link px-0">
                <?php echo isset($adapter) ? $adapter->translate('Download') : 'Download'; ?>
            </a>
        </div>
    </div>
<?php } ?>
