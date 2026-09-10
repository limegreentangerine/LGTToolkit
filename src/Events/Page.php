<?php

namespace LgtToolkit\Events;

use Core;
use View;
use Page as ConcretePage;
use Concrete\Core\Localization\Localization;
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

    /**
     * Adds the site cookie policy output to the current page when enabled.
     */
    public static function processCookiePolicy(): void
    {
        $page = ConcretePage::getCurrentPage();
        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');

        if (is_object($page) && is_object($pkg) && !$page->isAdminArea() && $page->getCollectionHandle() !== 'login') {
            $config = $pkg->getFileConfig();
            if ($config->get('lgt_toolkit.cookie_popup.activate') && $config->get('lgt_toolkit.cookie_popup.activate') == true) {
                $locale = Localization::activeLanguage();

                $args = [
                    'activate' => $config->get('lgt_toolkit.cookie_popup.activate'),
                    'title' => $config->get(sprintf('lgt_toolkit.cookie_popup.%s.title', $locale)),
                    'content' => $config->get(sprintf('lgt_toolkit.cookie_popup.%s.content', $locale)),
                ];

                if ($config->get(sprintf('lgt_toolkit.cookie_popup.%s.linkCID', $locale)) > 0) {
                    $policyPage = ConcretePage::getByID($config->get(sprintf('lgt_toolkit.cookie_popup.%s.linkCID', $locale)));
                    if (is_object($page)) {
                        $args['policyLink'] = $policyPage->getCollectionLink();
                        $args['linkText'] = $config->get(sprintf('lgt_toolkit.cookie_popup.%s.linkText', $locale));
                    }
                }

                $args['styles'] = $config->get('lgt_toolkit.cookie_popup.styles');

                ob_start();
                View::element('cookie_popup/cookie_popup', $args, 'lgt_toolkit');
                $policy = ob_get_contents();
                ob_end_clean();

                $html = Core::make('helper/html');
                $controller = $page->getPageController();
                $controller->addHeaderItem($html->css('cookie-popup.css', 'lgt_toolkit'));
                $controller->addFooterItem($policy);
                $controller->addFooterItem($html->javascript('cookie-popup.js', 'lgt_toolkit'));

                if (!isset($session) || $session == null) {
                    $session = Core::make('session');
                    if ($session->get('site-cookies') !== null) {
                        $controller->set('allowCookies', $session->get('site-cookies'));
                    } else {
                        $controller->set('allowCookies', false);
                    }
                }
            }
        }
    }
}
