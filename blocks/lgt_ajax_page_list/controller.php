<?php

namespace Concrete\Package\LgtToolkit\Block\LgtAjaxPageList;

defined('C5_EXECUTE') or die('Access Denied.');

use Core;
use Page;
use View;
use PageList;
use Exception;
use PageTemplate;
use FilesystemIterator;
use Concrete\Core\Block\Block;
use Concrete\Core\Block\BlockController;
use ClassKit\Page\TranslationAdaptorTrait;
use Concrete\Core\Error\UserMessageException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Controller extends BlockController
{
    use TranslationAdaptorTrait;

    protected $btTable = 'btLgtAjaxPageList';
    protected $btDefaultSet = 'navigation';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected $sortingOptions = [
        'chrono_desc' => 'Chronological (Latest First)',
        'chrono_asc' => 'Chronological (Earliest First)',
        'modified_desc' => 'Modified (Latest First)',
        'display_asc' => 'Sitemap Order (ASC)',
        'display_desc' => 'Sitemap Order (DESC)',
        'random' => 'Random',
        'alpha_asc' => 'Name (A-Z)',
        'alpha_desc' => 'Name (Z-A)',
    ];

    public function getAjaxTemplateHandles(): array
    {
        $templatePaths = [];

        $applicationPath = DIR_APPLICATION . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $this->btHandle;
        $applicationPaths = new FilesystemIterator($applicationPath, FilesystemIterator::SKIP_DOTS);

        foreach ($applicationPaths as $p) {
            $santizedPath = str_replace([DIR_APPLICATION, '.php','-','_'], ['application', '', ' ', ' '], $p->getFileName());
            $templatePaths['application'][$santizedPath] = ucwords($santizedPath);
        }

        $pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');
        $packagePath = $pkg->getPackagePath() . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $this->btHandle;
        $packagePaths = new FilesystemIterator($packagePath, FilesystemIterator::SKIP_DOTS);

        foreach ($packagePaths as $p) {
            $santizedPath = str_replace(['.php','-','_'], ['', ' ', ' '], $p->getFileName());
            $templatePaths[$pkg->getPackageHandle()][$santizedPath] = ucwords($santizedPath);
        }

        return $templatePaths;
    }

    protected function locateTemplate(string $handle): ?array
    {
        $btHandle = 'lgt_ajax_page_list';
        $pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');
        $packagePath = $pkg->getPackagePath() . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $btHandle;
        $testPath = $packagePath . DIRECTORY_SEPARATOR . $handle;

        if (file_exists($testPath . '.php')) {
            return [
                'path' => $btHandle . DIRECTORY_SEPARATOR . $handle,
                'pkg' => $pkg->getPackageHandle(),
            ];
        }

        $applicationPath = DIR_APPLICATION . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $btHandle;
        $testPath = $applicationPath . DIRECTORY_SEPARATOR . $handle;

        if (file_exists($testPath . '.php')) {
            return [
                'path' => $btHandle . DIRECTORY_SEPARATOR . $handle,
                'pkg' => null,
            ];
        }

        return null;
    }

    public function getBlockTypeName()
    {
        return t('Ajax Page List');
    }

    public function getBlockTypeDescription()
    {
        return t('Page List With Ajax Pagination.');
    }

    public function add()
    {
        $this->set('pl', $this->app->make('helper/form/page_selector'));
        $this->set('templates', PageTemplate::getList());
        $this->set('sorting', $this->sortingOptions);

        $pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');
        $this->set('ajaxTemplatePaths', [
            'application/elements' . DIRECTORY_SEPARATOR . $this->btHandle,
            $pkg->getPackageHandle() . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $this->btHandle,
        ]);
    }

    public function edit()
    {
        $this->set('pl', $this->app->make('helper/form/page_selector'));
        $this->set('templates', PageTemplate::getList());
        $this->set('sorting', $this->sortingOptions);

        $pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');
        $this->set('ajaxTemplatePaths', [
            '/application/elements' . DIRECTORY_SEPARATOR . $this->btHandle,
            '/packages' . DIRECTORY_SEPARATOR . $pkg->getPackageHandle() . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $this->btHandle,
        ]);
    }

    public function getNextPage(): Response
    {
        $bID = (int) $_REQUEST['bid'];
        $page = (int) $_REQUEST['page'];
        $template = $_REQUEST['template'];
        $response = [
            'request' => $_REQUEST,
        ];

        $bt = Block::getByID($bID);
        if (!is_object($bt)) {
            throw new Exception('Block ID ' . $bID . ': Not found', 404);
        }

        $instance = $bt->getController();

        $parent = Page::getByID($instance->get('parent_cID'));
        if (!is_object($parent) && $parent->isError()) {
            throw new Exception('Parent Page (CID: ' . $instance->get('parent_cID') . '): not found', 404);
        }

        $pl = new PageList();
        $pl->filterByParentID($parent->getCollectionID());

        if ($instance->get('pageTemplateID') > 0) {
            $template = PageTemplate::getByID($instance->get('pageTemplateID'));
            if (!is_object($template)) {
                throw new UserMessageException('Page Template ' . $instance->get('pageTemplateID') . ': Not found', 404);
            }
            $pl->filterByPageTemplate($template);
        }

        switch ($instance->get('sortOrder')) {
            case 'display_asc':
                $pl->sortByDisplayOrder();
                break;
            case 'display_desc':
                $pl->sortByDisplayOrderDescending();
                break;
            case 'chrono_asc':
                $pl->sortByPublicDate();
                break;
            case 'modified_desc':
                $pl->sortByDateModifiedDescending();
                break;
            case 'random':
                $pl->sortBy('RAND()');
                break;
            case 'alpha_asc':
                $pl->sortByName();
                break;
            case 'alpha_desc':
                $pl->sortByNameDescending();
                break;
            default:
                $pl->sortByPublicDateDescending();
                break;
        }

        $start = Core::make('helper/date')->toDB();
        $end = $start = null;
        if ($start) {
            $pl->filterByPublicDate($start, '>=');
        }
        if ($end) {
            $pl->filterByPublicDate($end, '<=');
        }

        $pl->filter('cvName', '', '!=');

        $pl->setItemsPerPage($instance->get('pageLength'));
        $pagination = $pl->getPagination();
        $pagination->setCurrentPage($page);


        if ($page < $pagination->getTotalPages()) {
            $response['hasNextPage'] = true;
            $response['nextPage'] = ++$page;
        } else {
            $response['hasNextPage'] = false;
        }

        $pages = $pagination->getCurrentPageResults();
        $response['pages'] = $pages;

        $template = ($instance->get('ajaxTemplateHandle') !== null) ? strtolower($instance->get('ajaxTemplateHandle')) : 'default';
        $templatePath = $this->locateTemplate($template);
        if (!$templatePath) {
            throw new UserMessageException(t('Template %s not found in paths', $template));
        }

        $adaptor = $this->getTranslationAdaptor($instance->get('parent_cID'));

        ob_start();
        $view = new View();
        foreach ($pages as $p) {
            if ($templatePath['pkg'] !== null) {
                $view->element($templatePath['path'], [
                    'page' => $p,
                    'adapter' => $adaptor,
                ], $templatePath['pkg']);
            } else {
                $view->element($templatePath['path'], [
                    'page' => $p,
                    'adapter' => $adaptor,
                ]);
            }
        }
        $html = ob_get_contents();
        ob_end_clean();

        $response['html'] = $html;

        return new JsonResponse($response);
    }
}
