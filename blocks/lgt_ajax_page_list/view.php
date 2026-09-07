<div id="<?php echo $bID; ?>" class="ajax-page-list">
    <div class="container">
        <div id="ajax-page-list__pages--<?php echo $bID; ?>" class="row"></div>

        <div class="row">
            <div class="col-12 text-center">
                <button id="ajax-page-list__load-more--<?php echo $bID; ?>"
                    class="btn btn-secondary mt-5 ajax-page-list__load-more"
                    data-bid="<?php echo $bID; ?>"
                    data-page="1"
                    data-cid="<?php echo $parent_cID; ?>"
                    data-template="<?php echo $ajaxTemplateHandle; ?>"
                >
                    <?php echo $buttonText; ?>
                </button>
            </div>
        </div>
    </div>
</div>
