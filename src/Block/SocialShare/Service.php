<?php

namespace LgtToolkit\Block\SocialShare;

use Core;
use Package;
use Concrete\Core\Sharing\SocialNetwork\Service as SocialService;

/**
 * Wraps a Concrete social service so it can render package-specific icons and links.
 */
class Service extends SocialService
{
    /**
     * Create a new social share service wrapper.
     *
     * @param SocialService $service The original Concrete social service instance.
     */
    public function __construct(SocialService $service)
    {
        parent::__construct($service->getHandle(), $service->getName(), $service->getIcon());

        $this->customHTML = $service->getHandle();
    }

    /**
     * Returns the icon markup for the wrapped social service.
     *
     * @param mixed $pkg Optional package instance used for package-specific icons.
     *
     * @return string HTML markup for the service icon.
     */
    public function getServiceIconHTML($pkg = false): string
    {
        if ($this->customHTML && $pkg instanceof Package) {
            return '<img src="' . $pkg->getRelativePath() . '/images/' . $this->customHTML . '.svg" alt="' . $this->getName() . '" />';
        }

        return '<i class="fa fa-' . $this->getIcon() . '" aria-hidden="true" title="' . $this->getDisplayName() . '"></i>';
    }

    /**
     * Builds a share URL for the current social network.
     *
     * @param string $url  The target URL to share.
     * @param string $text The text to accompany the share.
     *
     * @return string Share URL for the configured social network.
     */
    public function getSharerLink(string $url, string $text): string
    {
        $link = '';

        switch ($this->getHandle()) {
            case 'facebook':
                $link = 'https://www.facebook.com/share.php?u=' . urlencode($url) . '&quote=' . urlencode($text);
                break;
            case 'twitter':
                $link = 'http://twitter.com/share?text=' . urlencode($text) . '&url=' . urlencode($url) . '&hashtags=newportlive';
                break;
            case 'linkedin':
                $link = 'http://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($url) . '&title=' . urlencode($text) . '&source=' . urlencode(Core::make('site')->getSite()->getSiteName());
                break;
            default:
                $link = '';
                break;
        }

        return $link;
    }
}
