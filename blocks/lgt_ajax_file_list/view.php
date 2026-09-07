<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<component-ajax-file-list>
    <section id="<?php echo $bID ?? null; ?>" class="lgt__ajax-file-list">

    </section>
</component-ajax-file-list>

<?php /* <section class="ajax-file-list">
    <?php if ($topicTreeID > 0) {
        $tree = $controller->getTopicTree($topicTreeID);
    ?>
        <div class="container mb-5">
            <ul class="list-unstyled list-inline resource-filters">
                <li class="list-inline-item">
                    <button data-topic-id="0" data-bid="<?php echo $bID; ?>" class="active">
                        <?php echo t('View all'); ?>
                    </button>
                </li>

                <?php foreach ($tree as $value => $name) { ?>
                    <li class="list-inline-item">
                        <button data-topic-id="<?php echo $value; ?>" data-bid="<?php echo $bID; ?>">
                            <?php echo t($name); ?>
                        </button>
                    </li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

    <div class="container">
        <div class="row" id="ajax-files__<?php echo $bID; ?>"></div>

        <div class="row">
            <div class="col-12 text-center">
                <button id="ajax-file-list__load-more--<?php echo $bID; ?>"
                    class="btn btn-outline-primary mt-5 ajax-file-list__load-more"
                    data-bid="<?php echo $bID; ?>"
                    data-page="1"
                    data-target="#ajax-files__<?php echo $bID; ?>"
                    data-cID="<?php echo $c->getCollectionID(); ?>">
                    <?php echo t('Load more'); ?>
                </button>
            </div>
        </div>
    </div>
</section> */ ?>
