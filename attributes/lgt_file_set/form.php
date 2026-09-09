<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="form-group">
    <select
        name="<?php echo h($this->controller->field('value')) ?>"
        id="<?php echo h($this->controller->getControlID()) ?>"
        class="form-select"
    >
        <option value="">
            <?php echo h(t('Select a file set')) ?>
        </option>

        <?php foreach ($filesets ?? [] as $id => $set) { ?>
            <option
                value="<?php echo h($id) ?>"
                <?php echo (isset($value) && $value === $id) ? 'selected' : '' ?>
            >
                <?php echo h($set->getFileSetName()) ?>
            </option>
        <?php } ?>
    </select>
</div>
