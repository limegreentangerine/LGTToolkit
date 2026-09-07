
<?php if (isset($page)) { ?>
    <article class="card">
        <?php if ($image = $page->getAttribute('page_banner')) { ?>
            <img class="card-img-top" src="<?php echo $image->getRelativePath(); ?>" alt="<?php echo $image->getTitle(); ?>" />
        <?php } ?>

        <div class="card-body">
            <h5 class="card-title"><?php echo $page->getCollectionName(); ?></h5>

            <?php if (strlen($page->getCollectionDescription()) > 0) { ?>
                <p class="card-text"><?php echo $page->getCollectionDescription(); ?></p>
            <?php } ?>

            <a href="<?php echo $page->getCollectionLink(); ?>" class="btn btn-primary">
                <?php echo (isset($adapter)) ? $adapter->translate('Read more') : 'Read more'; ?>
            </a>
        </div>
    </article>
<?php } ?>
