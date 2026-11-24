<?php

namespace Concrete\Package\LgtToolkit\MenuItem\LgtSupport;

use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use LgtToolkit\Support\SupportSettings;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Application\UserInterface\Menu\Item\Controller as MenuItemController;

/**
 * "Report a problem" in the page toolbar, linking to the support form with the current page prefilled.
 */
class Controller extends MenuItemController
{
    public function displayItem()
    {
        if (!Application::getFacadeApplication()->make(SupportSettings::class)->isEnabled()) {
            return false;
        }

        $support = Page::getByPath('/dashboard/support');

        return $support && !$support->isError() && (new Checker($support))->canViewPage();
    }

    public function getMenuItemLinkElement()
    {
        $page = Page::getCurrentPage();
        $query = [];
        if ($page && !$page->isError()) {
            $query = ['page_id' => $page->getCollectionID(), 'page_version_id' => $page->getVersionID()];
        }

        $this->menuItem->setLink((string) \URL::to('/dashboard/support') . ($query ? '?' . http_build_query($query) : ''));

        return parent::getMenuItemLinkElement();
    }
}
