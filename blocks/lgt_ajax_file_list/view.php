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
            <div class="row lgt__ajax-file-list--items"></div>

            <div class="row">
                <div class="col-12 text-center">
                    <button
                        class="btn btn-outline-primary mt-5 lgt__ajax-file-list--load-more"
                        data-page="1"
                    >
                        <?php echo t('Load more'); ?>
                    </button>
                </div>
            </div>
        </div>
    </section>
</component-ajax-file-list>
