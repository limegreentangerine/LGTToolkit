<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<section id="<?php echo $bID; ?>" class="block__lgt-blockquote" style="<?php echo $background; ?>">
    <div class="container">
        <blockquote class="blockquote" style="<?php echo $colour ?? ''; ?>">
            <p class="mb-0"><?php echo isset($quote) ? nl2br($quote) : ''; ?></p>
            <?php if (isset($author)) { ?>
                <footer class="blockquote-footer"><cite title="<?php echo $author; ?>"><?php echo $author; ?></cite></footer>
            <?php } ?>
        </blockquote>
    </div>
</section>
