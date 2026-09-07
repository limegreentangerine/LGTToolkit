<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * Responsive Image Element
 * Lee Jones - 03/09/2026
 *
 * @param object $f       (required) - Concrete File object
 * @param string $altText (required) - Alt text for image, also sets title if title property not set
 *
 * @param array  $classes       (optional) - Array of class name strings
 * @param string $title         (optional) - Image title, will be used for figcaption is $useCaption set tot TRUE
 * @param int    $width         (optional) - Max width of image
 * @param int    $height        (optional) - Max height of image
 * @param bool   $cropImage     (optional) - When resizing crop the image
 * @param bool   $forceSize     (optional) - Forces the size set in width/height params
 * @param bool   $lazyLoad      (optional) - Turn on browser based "lazy" property, set to TRUE by default
 * @param bool   $useCaption    (optional) - If set to TRUE wraps picture element in figure element with figcaption
 * @param bool   $useFocalPoint (optional) - If set to TRUE sets the focal point value as the image centre
 */

$ih = \Core::make('helper/image');
$fph = \Core::make('focal_point');
$thumbs = [];
$sizes = [];
$forceSize = (isset($forceSize)) ? $forceSize : false;
$useCaption = ((isset($f) && isset($title)) && $title !== $f->getFileName()) ? true : false;
$cropImage = (isset($cropImage)) ? $cropImage : false;
$lazyLoad = (isset($lazyLoad)) ? $lazyLoad : true;
$useFocalPoint = (isset($useFocalPoint)) ? $useFocalPoint : true;

if (isset($f)) {
    $thumbType = \Core::make(Concrete\Core\File\Image\Thumbnail\Type\Type::class);
    $theme = \PageTheme::getSiteTheme();
    $imageMap = $theme->getThemeResponsiveImageMap();
    $previousSize = 0;

    if ((isset($width) && $width > 0) || (isset($height) && $height > 0)) {
        foreach ($imageMap as $handle => $rawSize) {
            $type = $thumbType->getByHandle($handle);
            if (!is_object($type)) {
                continue;
            }
            $pxSize = $rawSize;
            $rawSize = str_replace('px', '', $rawSize);
            if ($rawSize > $width) {
                continue;
            }

            $generatedThumbnail = $ih->getThumbnail($f, $width, $height, $cropImage);
            if ($generatedThumbnail->src !== '') {
                $thumbs[] = $generatedThumbnail->src . ' ' . $width . 'w';
                $sizes[] = '(min-width: ' . $width . 'px)';
            }

            if ($rawSize > 0) {
                $imgSize = ($rawSize > 0) ? $rawSize . 'w' : '100vw';
                $thumbs[] = ($rawSize > 0) ? $f->getThumbnailURL($type->getBaseVersion()) . ' ' . $imgSize : $f->getRelativePath() . ' 100vw';
                $sizeString = '(min-width: ' . $pxSize . ')';
                if ($previousSize > 0) {
                    $sizeString = $sizeString . ' ' . $previousSize;
                }
                $sizes[] = $sizeString;
                $previousSize = $imgSize;
            }
        }
    } else {
        foreach ($imageMap as $handle => $rawSize) {
            $type = $thumbType->getByHandle($handle);
            if (!is_object($type)) {
                continue;
            }
            $pxSize = $rawSize;
            $rawSize = str_replace('px', '', $rawSize);
            if ($rawSize > 0) {
                $imgSize = ($rawSize > 0) ? $rawSize . 'w' : '100vw';
                $thumbs[] = ($rawSize > 0) ? $f->getThumbnailURL($type->getBaseVersion()) . ' ' . $imgSize : $f->getRelativePath() . ' 100vw';
                $sizeString = '(min-width: ' . $pxSize . ')';
                if ($previousSize > 0) {
                    $sizeString = $sizeString . ' ' . $previousSize;
                }
                $sizes[] = $sizeString;
                $previousSize = $imgSize;
            }
        }
    }

    if ($useFocalPoint) {
        $focalPoint = $fph->getFocalPoint($f);
    }
}
?>

<?php if ($useCaption && (isset($title) && strlen($title) > 0)) { ?>
    <figure>
<?php } ?>

<?php if (isset($f)) { ?>
    <picture>
        <img
            <?php if ($lazyLoad) { ?>
                loading="lazy"
            <?php } ?>
            <?php if (isset($classes) && is_array($classes)) { ?>
                class="<?php echo implode(' ', $classes); ?>"
            <?php } ?>
            <?php if ($forceSize) { ?>
                width="<?php echo $f->getAttribute('width'); ?>px"
                height="<?php echo $f->getAttribute('height'); ?>px"
            <?php } ?>
            src="<?php echo $f->getRelativePath(); ?>"
            <?php if (is_array($thumbs) && count($thumbs) > 0) { ?>
                srcset="<?php echo implode(', ', $thumbs); ?>"
            <?php } ?>
            <?php if (is_array($sizes) && count($sizes) > 0) { ?>
                sizes="<?php echo implode(', ', array_reverse($sizes)); ?> 100vw"
            <?php } ?>
            <?php if (isset($defaultSrc)) { ?>data-default-src="<?php echo $defaultSrc; ?>" <?php } ?>
            <?php if (isset($hoverSrc)) { ?>data-hover-src="<?php echo $hoverSrc; ?>" <?php } ?>
            alt="<?php echo $altText ?? null; ?>"
            title="<?php echo $title ?? $altText ?? null; ?>"
            <?php if ($focalPoint) { ?>
                style="object-position: <?php echo $focalPoint; ?>;"
            <?php } ?>
            <?php echo $attributes ?? null; ?> />
    </picture>
<?php } ?>

<?php if ($useCaption && (isset($title) && strlen($title) > 0)) { ?>
    <figcaption><?php echo $title; ?></figcaption>
<?php } ?>

<?php if ($useCaption && (isset($title) && strlen($title) > 0)) { ?>
    </figure>
<?php } ?>
