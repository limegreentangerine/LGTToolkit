<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($view)) { ?>
    <div class="ccm-lgt-manual-nav-block-container">
        <fieldset>
            <legend><?php echo t('Nav Title'); ?></legend>

            <div class="form-group">
                <?php
                    echo $form->label('nav_title', t('Nav Title'));
                    echo $form->text('nav_title', $title ?? null);
                ?>
            </div>

        </fieldset>

        <fieldset>
            <legend><?php echo t('Manual Navigation'); ?></legend>

            <div class="ccm-lgt-manual-nav-entries ccm-lgt-manual-nav-entries-<?php echo $bID ?? 0; ?>"></div>
            <div>
                <button type="button" class="btn btn-success ccm-add-lgt-manual-nav-entry ccm-add-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>"><?php echo t('Add Link'); ?></button>
            </div>
        </fieldset>
    </div>

    <script>
        $(document).ready(function() {
            var lgtManualNavBlock = $('.ccm-lgt-manual-nav-entries-<?php echo $bID ?? 0; ?>');
            var _templateLink = _.template($('#lgt-manual-nav-<?php echo $bID ?? 0; ?>').html());

            var attachDelete = function($obj) {
                $obj.click(function() {
                    var deleteIt = confirm('<?php echo t('Are you sure?'); ?>');
                    if (deleteIt === true) {
                        var slideID = $(this).closest('.ccm-lgt-manual-nav-entry').find('.editor-content').attr('id');
                        $(this).closest('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>').remove();
                        doSortCount();
                    }
                });
            };

            var attachCopyTitle = function($obj) {
                $obj.on('focus blur', function() {
                    $(this).closest('.form-group').find('span').html($(this).val());
                });
            };

            var doSortCount = function() {
                $('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>').each(function(index) {
                    $(this).find('.ccm-lgt-manual-nav-entry-sort').val(index);
                });
            };

            lgtManualNavBlock.on('change', 'select[data-field=entry-link-select]', function() {
                var container = $(this).closest('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>');
                switch (parseInt($(this).val())) {
                    case 1:
                        container.find('div[data-field=entry-link-page-selector]').removeClass('link-type-show');
                        container.find('div[data-field=entry-link-url]').addClass('link-type-show');
                        break;
                    default:
                        container.find('div[data-field=entry-link-url]').removeClass('link-type-show');
                        container.find('div[data-field=entry-link-page-selector]').addClass('link-type-show');
                        break;
                }
            });

            <?php if (isset($rows)) { ?>

                <?php foreach ($rows as $row) { ?>
                    lgtManualNavBlock.append(_templateLink({
                        title: '<?php echo addslashes(h($row['title'])); ?>',
                        is_external: '<?php echo $row['is_external']; ?>',
                        external_url: '<?php echo $row['external_url']; ?>',
                        sort_order: '<?php echo $row['sort_order']; ?>'
                    }));

                    lgtManualNavBlock.find('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>:last-child div[data-field=entry-link-page-selector]').concretePageSelector({
                        'inputName': '<?php echo $view->field('page_cID'); ?>[]', 'cID': <?php echo (0 == $row['is_external']) ? intval($row['page_cID']) : 'false'; ?>
                    });
                <?php } ?>

            <?php } ?>

            doSortCount();
            lgtManualNavBlock.find('select[data-field=entry-link-select]').trigger('change');

            $('.ccm-add-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>').click(function() {
                var thisModal = $(this).closest('.ui-dialog-content');
                lgtManualNavBlock.append(_templateLink({
                    title: '',
                    is_external: 0,
                    page_cID: '',
                    external_url: '',
                    sort_order: ''
                }));

                $('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>').not('.link-closed').each(function() {
                    $(this).addClass('link-closed');
                    var thisEditButton = $(this).closest('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>').find('.btn.ccm-edit-link');
                    thisEditButton.text(thisEditButton.data('linkEditText'));
                });
                var newLink = $('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>').last();
                var closeText = newLink.find('.btn.ccm-edit-link').data('linkCloseText');
                newLink.removeClass('link-closed').find('.btn.ccm-edit-link').text(closeText);

                thisModal.scrollTop(newLink.offset().top);
                attachDelete(newLink.find('.ccm-delete-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>'));
                attachCopyTitle(newLink.find('.title-field-<?php echo $bID ?? 0; ?>'));
                newLink.find('div[data-field=entry-link-page-selector-select]').concretePageSelector({
                    'inputName': '<?php echo $view->field('page_cID'); ?>[]'
                });
                doSortCount();
            });

            $('.ccm-lgt-manual-nav-entries-<?php echo $bID ?? 0; ?>').on('click','.ccm-edit-link', function() {
                $(this).closest('.ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>').toggleClass('link-closed');
                var thisEditButton = $(this);
                if (thisEditButton.data('linkEditText') === thisEditButton.text()) {
                    thisEditButton.text(thisEditButton.data('linkCloseText'));
                } else if (thisEditButton.data('linkCloseText') === thisEditButton.text()) {
                    thisEditButton.text(thisEditButton.data('linkEditText'));
                }
            });

            $('.ccm-lgt-manual-nav-entries-<?php echo $bID ?? 0; ?>').sortable({
                placeholder: 'ui-state-highlight',
                axis: 'y',
                handle: 'i.fa-arrows',
                cursor: 'move',
                update: function() {
                    doSortCount();
                }
            });

            attachDelete($('.ccm-delete-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>'));
            attachCopyTitle($('.title-field-<?php echo $bID ?? 0; ?>'));
        });
    </script>

    <script type="text/template" id="lgt-manual-nav-<?php echo $bID ?? 0; ?>">
        <div class="ccm-lgt-manual-nav-entry ccm-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?> well link-closed">

            <div class="form-group" >
                <label class="control-label"><?php echo t('Title'); ?></label>
                <input class="form-control ccm-input-text title-field-<?php echo $bID ?? 0; ?>" type="text" name="<?php echo $view->field('title'); ?>[]" value="<%=title%>" />
                <span class=""><%=title%></span>
            </div>

            <div class="form-group" >
                <label class="control-label"><?php echo t('Link'); ?></label>
                <select data-field="entry-link-select" name="is_external[]" class="form-control" style="width: 60%">
                    <option value="0" <% if (is_external == 0) { %>selected<% } %>><?php echo t('Another Page'); ?></option>
                    <option value="1" <% if (is_external == 1) { %>selected<% } %>><?php echo t('External URL'); ?></option>
                </select>
            </div>

            <div data-field="entry-link-page-selector" class="form-group link-type link-type-show">
                <label class="control-label"><?php echo t('Choose Page:'); ?></label>
                <div data-field="entry-link-page-selector-select"></div>
            </div>

            <div data-field="entry-link-url" class="form-group link-type">
                <label class="control-label"><?php echo t('URL:'); ?></label>
                <input class="form-control ccm-input-text" type="text" name="<?php echo $view->field('external_url'); ?>[]" value="<%=external_url%>" />
            </div>

            <button type="button" class="btn btn-sm btn-default ccm-edit-link ccm-edit-link-<?php echo $bID ?? 0; ?>" data-link-close-text="<?php echo t('Collapse'); ?>" data-link-edit-text="<?php echo t('Edit'); ?>"><?php echo t('Edit'); ?></button>
            <button type="button" class="btn btn-sm btn-danger ccm-delete-lgt-manual-nav-entry ccm-delete-lgt-manual-nav-entry-<?php echo $bID ?? 0; ?>"><?php echo t('Remove'); ?></button>
            <i class="fa fa-arrows"></i>

            <input class="ccm-lgt-manual-nav-entry-sort" type="hidden" name="<?php echo $view->field('sort_order'); ?>[]" value="<%=sort_order%>"/>
        </div>
    </script>
<?php } ?>
