<?php

namespace Concrete\Package\LgtToolkit\Block\LgtAjaxFileList;

defined('C5_EXECUTE') or die('Access Denied.');

use Page;
use View;
use FileSet;
use FileList;
use Exception;
use FilesystemIterator;
use Concrete\Core\Block\Block;
use Concrete\Core\Tree\Node\Node;
use Concrete\Core\Tree\Node\Type\Topic;
use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Localization\Localization;
use Symfony\Component\HttpFoundation\Response;
use Concrete\Core\Tree\Type\Topic as TopicTree;
use Concrete\Core\File\Set\SetList as FileSetList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concrete\Core\Multilingual\Page\Section\Section;

class Controller extends BlockController
{
    protected $btTable = 'btLgtAjaxFileList';
    protected $btDefaultSet = 'default';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected int $topicTreeID;

    protected function getImageTypes(): array
    {
        return [
            'gif',
            'jpg',
            'jpeg',
            'png',
        ];
    }

    protected function getPageLocale($id = null): string
    {
        if ($id == null) {
            $id = Page::getCurrentPage()->getCollectionID();
        }

        $c = Page::getById($id); // the page

        $ml = Section::getList();
        foreach ($ml as $m) {
            $tid = $m->getTranslatedPageID($c);
            if ($tid == $id) {
                return $m->getLocale();
            }
        }

        // default to english and uk
        return 'en_GB';
    }

    protected function getAjaxTemplateHandles(): array
    {
        $pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt-toolkit');
        $path = $pkg->getPackagePath() . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $this->btHandle;
        $paths = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        $templatePaths = [];

        foreach ($paths as $path) {
            $santizedPath = str_replace('.php', '', $path->getFileName());
            $templatePaths[$santizedPath] = ucfirst($santizedPath);
        }

        $this->set('ajaxTemplatePath', $pkg->getPackageHandle() . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR . $this->btHandle);

        return $templatePaths;
    }

    public function getBlockTypeName()
    {
        return t('Ajax File Set');
    }

    public function getBlockTypeDescription()
    {
        return t('Add an ajax file set.');
    }

    public function getFileSets(): array
    {
        $sets = [
            '' => t('Choose a file set...'),
        ];

        $fsl = new FileSetList();
        $fsl->sortBy('fsID', 'desc');
        foreach ($fsl->get() as $fs) {
            $sets[$fs->getFileSetID()] = $fs->getFileSetName();
        }

        return $sets;
    }

    public function getTopicTrees(): array
    {
        $trees = [
            '0' => t('Choose a topic tree...'),
        ];

        foreach (TopicTree::getList() as $ctree) {
            if (is_object($ctree)) {
                $trees[$ctree->getTreeID()] = $ctree->getTreeDisplayName();
            }
        }

        return $trees;
    }

    public function getTopicTree(int $topicTreeID)
    {
        $categories = [];

        $tree = TopicTree::getByID($topicTreeID);
        if (is_object($tree)) {
            $node = $tree->getRootTreeNodeObject();
            $node->populateChildren();

            if (is_object($node)) {
                foreach ($node->getChildNodes() as $key => $category) {
                    if ($category instanceof Topic) {
                        $categories[$category->getTreeNodeID()] = $category->getTreeNodeDisplayName();
                    }
                }
            }
        }

        return $categories;
    }

    public function getFiles(): Response
    {
        $bID = (int) isset($_REQUEST['bid']) ? $_REQUEST['bid'] : 0;
        $page = (int) isset($_REQUEST['page']) ? $_REQUEST['page'] : 0;
        $cID = (int) isset($_REQUEST['cid']) ? $_REQUEST['cid'] : 0;
        $response = [
            'request' => $_REQUEST,
        ];

        $bt = Block::getByID($bID);
        if (!is_object($bt)) {
            throw new Exception('Block ID ' . $bID . ': Not found', 404);
        }

        $instance = $bt->getController();

        $fs = FileSet::getByID($instance->get('fsID'));
        if (!is_object($fs)) {
            throw new Exception('File Set ID ' . $instance->get('fsID') . ': Not found', 404);
        }

        $fl = new FileList();
        $fl->filterBySet($fs);

        if (isset($_REQUEST['topicId'])) {
            $ak = FileKey::getByHandle('file_categories');
            if (is_object($ak)) {
                $topic = Node::getByID($_REQUEST['topicId']);
                if ($topic) {
                    $ak->getController()->filterByAttribute($fl, $_REQUEST['topicId']);
                }
            }
        }

        $fl->sortByFileSetDisplayOrder();
        $fl->setItemsPerPage($instance->get('numberPerPage'));
        $pagination = $fl->getPagination();
        $pagination->setCurrentPage($page);

        if ($page < $pagination->getTotalPages()) {
            $response['hasNextPage'] = true;
            $response['nextPage'] = ++$page;
        } else {
            $response['hasNextPage'] = false;
        }

        $files = $pagination->getCurrentPageResults();
        $response['files'] = $files;

        $template = ($instance->get('ajaxTemplateHandle') !== null) ? strtolower($instance->get('ajaxTemplateHandle')) : 'gallery';
        $templatePath = $bt->getBlockTypeHandle() . DIRECTORY_SEPARATOR . $template;

        $lang = $this->getPageLocale($cID);
        $loc = Localization::getInstance();
        $loc->setLocale($lang);
        $adapter = $loc->getActiveTranslatorAdapter();

        ob_start();
        foreach ($files as $f) {
            $view = new View();
            $view->element($templatePath, [
                'file' => $f,
                'adapter' => $adapter,
                'supported' => $this->getImageTypes(),
            ], 'lgt-toolkit');
        }
        $html = ob_get_contents();
        ob_end_clean();

        $response['html'] = $html;

        return new JsonResponse($response);
    }

    public function add()
    {
        $this->set('ajaxTemplates', $this->getAjaxTemplateHandles());
    }

    public function edit()
    {
        $this->set('ajaxTemplates', $this->getAjaxTemplateHandles());
    }

    public function view()
    {
        $this->set('c', Page::getCurrentPage());
        $this->set('tree', $this->getTopicTree($this->topicTreeID));
    }
}
