<?php

namespace LgtToolkit\Events;

use URL;
use Core;
use DebugBar\DebugBar;
use Page as ConcretePage;
use Illuminate\Support\Str;
use Concrete\Core\Http\Request;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Package\LgtToolkit\Entity\Attribute\Value\Value\RedirectValue;

/**
 * Handles page-level event hooks for redirect and cookie policy processing.
 */
class Page
{
    /**
     * Redirects the current request when a page-level redirect attribute is configured.
     */
    public static function redirector(): void
    {
        $page = ConcretePage::getCurrentPage();
        $attrKey = CollectionKey::getByHandle('page_redirector');

        if (is_object($attrKey) && is_object($page) && !$page->isError()) {
            $attributeValue = $page->getAttributeValueObject($attrKey);

            if ($attributeValue !== null) {
                $redirectValue = $attributeValue->getValueObject();

                if ($redirectValue instanceof RedirectValue) {
                    $type = $redirectValue->getRedirectType();
                    $method = $redirectValue->getRedirectMethod();
                    $value = $redirectValue->getValue();


                    if ($type === 'external') {
                        $destination = $value;
                    } elseif ($type === 'page') {
                        $targetPage = ConcretePage::getByID($value);

                        if (is_object($targetPage) && !$targetPage->isError()) {
                            $destination = $targetPage->getCollectionLink();
                        }
                    }

                    if (isset($destination) && filter_var($destination, FILTER_VALIDATE_URL)) {
                        http_response_code($method);
                        header('Location: ' . $destination);
                        exit;
                    }
                }
            }
        }
    }

    public static function startDebugBar(?DebugBar $debugbar)
    {
        $request = Core::make(Request::class);
        if ($request->isXmlHttpRequest() || Str::contains($request->headers->get('Accept', ''), 'application/json')) {
            return;
        }

        if (!$debugbar) {
            return;
        }

        $page = ConcretePage::getCurrentPage();
        if (!is_object($page) || $page->isError() || $page->isAdminArea()) {
            return;
        }

        $debugbarRenderer = $debugbar->getJavascriptRenderer();
        $debugbarRenderer->setAssetHandlerUrl(
            URL::to('/debugbar/assets'),
        );
        $page->getPageController()->addHeaderItem($debugbarRenderer->renderHead());
        $page->getPageController()->addFooterItem($debugbarRenderer->render());
    }
}
