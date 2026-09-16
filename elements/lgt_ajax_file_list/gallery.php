
<?php if (isset($file)) { ?>
    <article class="card">
        <img class="card-img-top" src="<?php echo $file->getRelativePath(); ?>" alt="<?php echo $file->getTitle(); ?>" />
    </article>
<?php } ?>
