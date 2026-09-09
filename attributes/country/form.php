<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="form-group">
    <select
        name="<?php echo h($this->controller->field('value')) ?>"
        id="<?php echo h($this->controller->getControlID()) ?>"
        class="form-select"
    >
        <option value="">
            <?php echo h(t('Select a country')) ?>
        </option>

        <?php foreach ($countries ?? [] as $code => $name) { ?>
            <option
                value="<?php echo h($code) ?>"
                <?php echo (isset($value) && $value === $code) ? 'selected' : '' ?>
            >
                <?php echo h($name) ?>
            </option>
        <?php } ?>
    </select>
</div>
