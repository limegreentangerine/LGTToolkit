<?php defined('C5_EXECUTE') or die('Access Denied.');
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

if (isset($f) && (is_object($f) && $f->getFileID())) {
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

    if (isset($maxWidth) && $maxWidth > 0) {
        $imageWidth = $maxWidth;
    }

    if (isset($maxHeight) && $maxHeight > 0) {
        $imageHeight = $maxHeight;
    }

    if (isset($altText) && $altText) {
        $imageAltText = h($altText);
    } else {
        $imageAltText = $f->getTitle();
    }

    if (isset($title) && $title) {
        $imageTitle = h($title);
    } else {
        $imageTitle = $f->getTitle();
    }

    if (isset($foS) && (is_object($foS) && !$f->getTypeObject()->isSVG() && !$foS->getTypeObject()->isSVG())) {
        if (!isset($imgPaths)) {
            $imgPaths = [];
        }

        $classes[] = 'ccm-image-block-hover';
        $defaultSrc = $imgPaths['default'];
        $hoverSrc = $imgPaths['hover'];
    }

    if (isset($linkURL) && $linkURL) {
        echo '<a href="' . $linkURL . '" ' . ((isset($openLinkInNewWindow) && $openLinkInNewWindow) ? 'target="_blank" rel="noopener noreferrer"' : '') . '>';
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
            'cropImage' => $cropImage ?? false,
        ]);
    }

    if (isset($linkURL) && $linkURL) {
        echo '</a>';
    }

} elseif (isset($c) && is_object($c) && $c->isEditMode()) {
    echo '<div class="ccm-edit-mode-disabled-item">' . t('Empty Image Block.') . '</div>';
}
