<component-ajax-page-list>
    <section id="<?php echo $bID; ?>" class="lgt__ajax-page-list">
        <div class="container">
            <div class="row align-items-stretch row-cols-1 row-cols-lg-3 row-cols-hd-4 lgt__ajax-page-list--items"></div>

            <div class="d-flex justify-content-center mt-3">
                <button
                    class="btn btn-primary lgt__ajax-page-list--load-more"
                    data-bid="<?php echo $bID; ?>"
                    data-page="1"
                    data-cid="<?php echo $parent_cID ?? ((isset($c)) ? $c->getCollectionID() : \Page::getHomePageID()); ?>"
                    data-template="<?php echo $ajaxTemplateHandle ?? 'default'; ?>"
                >
                    <?php echo $buttonText ?? t('Load more'); ?>
                </button>
            </div>
        </div>
    </section>
</component-ajax-page-list>
