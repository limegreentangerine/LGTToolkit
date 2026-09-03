<?php

namespace LgtToolkit\Events;

use Core;
use View;
use Package;
use PageList;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Page\Page as ConcretePage;

class Page
{
    public static function redirector(): void
    {
        $page = ConcretePage::getCurrentPage();

        if (is_object($page)) {
            // Check to see if page has 'page_redirector' attribute
            $page_redirector = $page->getAttribute('page_redirector');
            if ($page_redirector) {
                $continue = false;

                if ($page_redirector['redirect_type'] == 'external') {
                    // External URL, just load in the stored value to $url
                    $url = $page_redirector['external_url'];
                    $continue = true;
                } elseif (in_array($page_redirector['redirect_type'], ['page-specific', 'page-parent'])) {
                    // Parent or Specific page. Grab the page needed and put in $target_page
                    switch ($page_redirector['redirect_type']) {
                        case 'page-specific':
                            $target_page = ConcretePage::getByID($page_redirector['page_cID']);
                            break;
                        case 'page-parent':
                            $target_page = ConcretePage::getByID($page->getCollectionParentID());
                            break;
                        default:
                            break;
                    }
                    if ((isset($target_page) && is_object($target_page)) && !$target_page->isError()) {
                        // Assign to $url
                        $url = $target_page->getCollectionLink();
                        $continue = true;
                    }
                } else {
                    // All other options are based on a needgin a pagelist, with specific sorting.
                    $l = new PageList();
                    $l->disableAutomaticSorting();
                    $l->filterByParentID($page->getCollectionID());
                    switch ($page_redirector['redirect_type']) {
                        case 'child-display-asc':
                            $l->sortByDisplayOrder();
                            break;
                        case 'child-display-desc':
                            $l->sortByDisplayOrderDescending();
                            break;
                        case 'newest-child':
                            $l->sortByPublicDateDescending();
                            break;
                        case 'oldest-child':
                            $l->sortByPublicDate();
                            break;
                        case 'child-name-asc':
                            $l->sortByName();
                            break;
                        case 'child-name-desc':
                            $l->sortByNameDescending();
                            break;
                        case 'child-random':
                            $l->sortBy('RAND()');
                            break;
                        default:
                            $continue = false;
                            break;
                    }

                    $l->setItemsPerPage(1);
                    $pagination = $l->getPagination();
                    $pages = $pagination->getCurrentPageResults();

                    if (count($pages) > 0) {
                        $target_page = $pages[0];

                        // Check to see if this is an object and has no errors
                        if (is_object($target_page) && !$target_page->isError()) {
                            // Assign to $url
                            $url = $target_page->getCollectionLink();
                            $continue = true;
                        }
                    }
                }

                // See if we are allowed to continue and if the $url is valid.
                if ($continue && (isset($url) && filter_var($url, FILTER_VALIDATE_URL))) {
                    // Set the HTTP Method
                    http_response_code($page_redirector['redirect_method']);
                    // Set the new location and redirect.
                    header('Location: ' . $url);
                    exit;
                }
            }
        }
    }

    public static function processCookiePolicy(): void
    {
        $page = ConcretePage::getCurrentPage();
        $pkg = Package::getByHandle('lgt_toolkit');

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
                $controller->addFooterItem('<script type="text/x-template" id="cookie-popup-code">' . $policy . '</script>');
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
