<?php defined('C5_EXECUTE') or die('Access Denied.');
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

if (is_object($f) && $f->getFileID()) {
    $imageWidth = 0;
    $imageHeight = 0;
    $imageAltText = '';
    $imageTitle = '';
    $classes = [
        'ccm-image-block',
        'img-responsive',
        'bID-' . $bID,
    ];
    $defaultSrc = false;
    $hoverSrc = false;

    if ($f->getTypeObject()->isSVG()) {
        $classes[] = 'ccm-svg';
    }

    if ($maxWidth > 0) {
        $imageWidth = $maxWidth;
    }

    if ($maxHeight > 0) {
        $imageHeight = $maxHeight;
    }

    if ($altText) {
        $imageAltText = h($altText);
    } else {
        $imageAltText = $f->getTitle();
    }

    if ($title) {
        $imageTitle = h($title);
    } else {
        $imageTitle = $f->getTitle();
    }

    if (is_object($foS) && !$f->getTypeObject()->isSVG() && !$foS->getTypeObject()->isSVG()) {
        $classes[] = 'ccm-image-block-hover';
        $defaultSrc = $imgPaths['default'];
        $hoverSrc = $imgPaths['hover'];
    }

    if ($linkURL) {
        echo '<a href="' . $linkURL . '" ' . ($openLinkInNewWindow ? 'target="_blank" rel="noopener noreferrer"' : '') . '>';
    }

    if ($f->getTypeObject()->isSVG()) {
        $imageTagHTML = '<img ';
        $imageTagHTML .= 'src="' . $f->getRelativePath() . '" ';
        if (count($classes) > 0) {
            $imageTagHTML .= 'class="' . implode(' ', $classes) . '" ';
        }
        if ($imageWidth > 0) {
            $imageTagHTML .= 'width="' . $imageWidth . '" ';
        }
        if ($imageHeight > 0) {
            $imageTagHTML .= 'height="' . $imageHeight . '" ';
        }
        $imageTagHTML .= 'title="' . $imageTitle . '" ';
        $imageTagHTML .= 'alt="' . $imageAltText . '" ';
        $imageTagHTML .= ' />';
        echo $imageTagHTML;
    } else {
        \View::element('picture', [
            'f' => $f,
            'classes' => $classes,
            'width' => $imageWidth,
            'height' => $imageHeight,
            'altText' => $imageAltText,
            'title' => $imageTitle,
            'defaultSrc' => $defaultSrc,
            'hoverSrc' => $hoverSrc,
            'cropImage' => $cropImage,
        ]);
    }

    if ($linkURL) {
        echo '</a>';
    }

} elseif ($c->isEditMode()) {
    echo '<div class="ccm-edit-mode-disabled-item">' . t('Empty Image Block.') . '</div>';
}
