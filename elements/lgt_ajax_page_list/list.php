<?php if (isset($page)) { ?>
    <article class="border-bottom border-2">
        <h5><?php echo $page->getCollectionName(); ?></h5>

        <?php if (strlen($page->getCollectionDescription()) > 0) { ?>
            <p><?php echo $page->getCollectionDescription(); ?></p>
        <?php } ?>

        <a href="<?php echo $page->getCollectionLink(); ?>" class="btn btn-link">
            <?php echo (isset($adapter)) ? $adapter->translate('Read more') : 'Read more'; ?>
        </a>
    </article>
<?php } ?>
