<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<component-ajax-file-list>
    <section id="<?php echo $bID ?? null; ?>" class="lgt__ajax-file-list">
        <?php if (isset($tree)) { ?>
            <div class="container mb-3">
                <ul class="list-unstyled list-inline lgt__ajax-file-list--filters">
                    <li class="list-inline-item">
                        <button data-topic-id="0" class="active">
                            <?php echo t('View all'); ?>
                        </button>
                    </li>

                    <?php foreach ($tree as $value => $name) { ?>
                        <li class="list-inline-item">
                            <button data-topic-id="<?php echo $value; ?>">
                                <?php echo t($name); ?>
                            </button>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <div class="container">
            <div class="row align-items-stretch row-cols-1 row-cols-lg-3 row-cols-hd-4 lgt__ajax-file-list--items"></div>

            <div class="d-flex justify-content-center mt-3">
                <button
                    class="btn btn-primary lgt__ajax-file-list--load-more"
                    data-page="1"
                    data-bid="<?php echo $bID ?? null; ?>"
                    data-cid="<?php echo (isset($c) ? $c->getCollectionID() : \Page::getHomePageID()); ?>"
                >
                    <?php echo t('Load more'); ?>
                </button>
            </div>
        </div>
    </section>
</component-ajax-file-list>
