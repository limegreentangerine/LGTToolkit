<?php
namespace LgtToolkit\Block\SocialShare;

use Core;
use Package;
use Concrete\Core\Sharing\SocialNetwork\Service as SocialService;

class Service extends SocialService
{

    public function __construct(SocialService $service)
    {
        parent::__construct($service->getHandle(), $service->getName(), $service->getIcon());

        $this->customHTML = $service->getHandle();
    }

    public function getServiceIconHTML($pkg = false): string
    {
        if ($this->customHTML && $pkg instanceof Package) {
            return '<img src="' . $pkg->getRelativePath() . '/images/' . $this->customHTML . '.svg" alt="' . $this->getName() . '" />';
        }

        return '<i class="fa fa-' . $this->getIcon() . '" aria-hidden="true" title="' . $this->getDisplayName() . '"></i>';
    }

    public function getSharerLink(string $url, string $text): string
    {
        $link = '';

        switch($this->getHandle()) {
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
