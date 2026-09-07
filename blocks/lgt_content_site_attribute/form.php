<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<?php if (isset($as) && isset($form_data) && isset($token)) { ?>
    <fieldset>
        <p><?php echo t('This content block allows for Site Attribute Substitution. Any attributes that are in the <strong>' . $as->getAttributeSetName() . '</strong> set can be used'); ?></p>
        <p><?php echo t('The following attributes are available for substitution, and how to call them in the content after the dash'); ?>
        <ul>
            <?php
            foreach ($as->getAttributeKeys() as $ak) {
                echo '<li>' . $ak->getAttributeKeyName() . ' - <code>{' . $ak->getAttributeKeyHandle() . '}</code></li>';
            }
            ?>
        </ul>

        <div class="form-group">
            <label for="content" class="control-label"><?php echo t('Would you like to load in template text?'); ?></label>
            <p class="help-block"><?php echo t('This will replace the all the content in this block.'); ?></p>
            <div class="btn-group-sm">
                <?php
                foreach ($form_data['content_options'] as $k => $v) {
                    echo '<button class="content-option btn btn btn-link" type="button" data-content="' . $k . '">' . $v . '</button>';
                }
                ?>
            </div>
        </div>

        <div class="form-group substitution-content-editor">
            <?php echo $form->label('content', t('Content:'));?>
            <?php
                $editor = $this->app->make('editor');
                echo $editor->outputBlockEditModeEditor('content', $content ?? null);
            ?>
        </div>
    </fieldset>

    <script type="text/javascript">
        $(function() {
            $('.content-option').click(function(e) {
                e.preventDefault();
                var $this = $(this);
                $.ajax({
                    url: '<?php echo URL::to('/ajax/lgt_toolkit/blocks/content_site_attribute/get_dummy_text'); ?>',
                    cache: false,
                    data: {
                        'dummy': $this.data('content'),
                        'ccm_token': '<?php echo $token->generate('lgt_content_site_attribute'); ?>'
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        return confirm("Doing this will replace all the content in this block. Are you sure?");
                    },
                    success: function(data) {
                        if (data.error) {
                            console.log(data);
                        } else {
                            $('.substitution-content-editor textarea').val(data.content);
                        }
                    }
                });
            });
        });
    </script>
<?php } ?>
