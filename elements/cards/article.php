<?php if (isset($page)) { ?>
    <div class="card">
        <?php
            if ($image = $page->getAttribute('page_banner')) {
                \View::element('picture', [
                    'f' => $image,
                    'classes' => [
                        'card-img-top',
                    ],
                    'altText' => $image->getTitle(),
                ]);
            }
    ?>
        <div class="card-body">
            <h5 class="card-title"><?php echo $page->getCollectionName(); ?></h5>

            <p class="card-text small"><?php echo $page->getCollectionDatePublicObject()->format('M j, Y'); ?></p>

            <p class="card-text"><?php echo $page->getCollectionDescription(); ?></p>
            <a href="#" class="btn btn-primary">
                <?php echo (isset($ta) && $ta) ? $ta->translate('Read more') : t('Read more'); ?>
            </a>
        </div>
    </div>
<?php } ?>
