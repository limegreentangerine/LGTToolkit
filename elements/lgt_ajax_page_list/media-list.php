<?php if (isset($page)) { ?>
    <article class="d-flex align-items-center">
        <div class="flex-shrink-0">
            <?php if ($image = $page->getAttribute('page_banner')) { ?>
                <img src="<?php echo $image->getRelativePath(); ?>" alt="<?php echo $image->getTitle(); ?>" />
            <?php } ?>
        </div>

        <div class="flex-grow-1 ms-3">
            <h5><?php echo $page->getCollectionName(); ?></h5>

            <?php if (strlen($page->getCollectionDescription()) > 0) { ?>
                <p><?php echo $page->getCollectionDescription(); ?></p>
            <?php } ?>

            <a href="<?php echo $page->getCollectionLink(); ?>" class="btn btn-link">
                <?php echo (isset($adapter)) ? $adapter->translate('Read more') : 'Read more'; ?>
            </a>
        </div>
    </article>
<?php } ?>
