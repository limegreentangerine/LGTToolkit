<?php

namespace Application\Page;

use Page;
use View;
use Package;
use PageList;
use Exception;
use Concrete\Core\Tree\Node\Node;
use Concrete\Core\Attribute\Key\CollectionKey;

trait AjaxPageTrait
{
    use TranslationAdaptorTrait;

    protected $currentPage;
    protected $pageNumber;
    protected $pageLength;
    protected $sortOrder;
    protected $response = [];
    protected $debug = false;
    protected $packageHandle = false;
    protected $noResultsMessage = 'No articles found';

    public function __construct()
    {
        if ($this->hasParents()) {
            parent::__construct();
        }

        $this->pl = new PageList();

        if (count($_GET) < 1) {
            throw new Exception(t('GET variables missing from URL string.'), 400);
        }
    }

    protected function sanitizeResponse($response)
    {
        $return = [];

        foreach ($response as $key => $value) {
            $return[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $return;
    }

    /**
     * Check Errors and set variables
     */
    protected function checkAndSet()
    {
        $this->setCurrentPage(Page::getByID($this->getRequestKey('cid')));
        if (!is_object($this->currentPage)) {
            throw new Exception(t('Page not found (CID: %s)', $this->getRequestKey('cid')), 404);
        }

        if ($this->getPackageHandle() !== false) {
            $pkg = Package::getByHandle($this->getPackageHandle());
            if (!is_object($pkg) || $pkg === false) {
                throw new Exception(t('Package not found (HANDLE: ', $this->getPackageHandle()), 404);
            }
        }

        if (!$this->pl instanceof PageList) {
            throw new Exception(t('Page List not initialised.'), 400);
        }

        $this->setPageNumber($this->getRequestKey('page'));
    }

    /**
     * Generate Page List
     *
     * @param bool $includeExclusions - Whether to show excluded pages or not
     *
     * @return object PageList
     */
    protected function getPageList(bool $includeExclusions = false): PageList
    {
        $this->checkAndSet();

        if ($this->getDebug() === true) {
            $this->pl->debug();
        }
        $this->pl->filterByParentID($this->getCurrentPage()->getCollectionID());
        $this->pl->filter('cvName', '', '!=');
        $this->pl->filterByExcludePageList($includeExclusions);
        $this->pl->setItemsPerPage($this->getPageLength());

        switch ($this->getSortOrder()) {
            case 'sitemap_asc':
                $this->pl->sortByDisplayOrder();
                break;
            case 'sitemap_desc':
                $this->pl->sortByDisplayOrderDescending();
                break;
            case 'date_asc':
                $this->pl->sortByPublicDate();
                break;
            case 'modified_date_asc':
                $this->pl->sortByDateModified();
                break;
            case 'modified_date_desc':
                $this->pl->sortByDateModifiedDescending();
                break;
            case 'random':
                $this->pl->sortBy('RAND()');
                break;
            case 'name_asc':
                $this->pl->sortByName();
                break;
            case 'name_desc':
                $this->pl->sortByNameDescending();
                break;
            case 'date_desc':
            default:
                $this->pl->sortByPublicDateDescending();
                break;
        }

        return $this->pl;
    }

    /**
     * Filter Page List by Topic
     *
     * @param string $responseKey
     * @param string $attrHandle
     */
    protected function filterByTopic(string $responseKey, string $attrHandle)
    {
        if ($this->getRequestKey($responseKey)) {
            $ak = CollectionKey::getByHandle($attrHandle);
            if (is_object($ak)) {
                $topic = Node::getByID($this->getRequestKey($responseKey));
                if ($topic) {
                    $ak->getController()->filterByAttribute($this->pl, $this->getRequestKey($responseKey));
                }
            }
        }
    }

    /**
     * Filter by Multiple Topics
     *
     * @param array $topics - Array of attrHandle as key with Topic ID/Handle/Object as value
     */
    protected function filterByTopics(array $topics, string $comparison = 'AND')
    {
        $this->pl->filterByMultipleTopics($topics, $comparison);
    }

    /**
     * Generate HTML response for ajax request
     *
     * @param array       $pages               - Array of Concrete pages
     * @param string      $templateString      - Path to page result template
     * @param string|bool $emptyTemplateString - Path to empty result template, set to false for no empty results message
     * @param string      $pageKey             - Key to use for page in the result element
     */
    protected function getHTML(array $pages, string $templateString, string|bool $emptyTemplateString = false, string $pageKey = 'page')
    {
        $translationAdaptor = $this->getTranslationAdaptor($this->currentPage->getCollectionID());

        ob_start();
        $view = new View();
        if (count($pages) > 0) {
            foreach ($pages as $page) {
                if ($this->getPackageHandle() !== false) {
                    $view->element($templateString, [
                        $pageKey => $page,
                        'translationAdaptor' => $translationAdaptor,
                    ]);
                } else {
                    $view->element($templateString, [
                        $pageKey => $page,
                        'translationAdaptor' => $translationAdaptor,
                    ], $this->getPackageHandle());
                }
            }
        } elseif ($emptyTemplateString !== false) {
            if ($this->getPackageHandle() !== false) {
                $view->element($emptyTemplateString, [
                    'noResultsMessage' => $this->getNoResultsMessage(),
                    'translationAdaptor' => $translationAdaptor,
                ]);
            } else {
                $view->element($emptyTemplateString, [
                    'noResultsMessage' => $this->getNoResultsMessage(),
                    'translationAdaptor' => $translationAdaptor,
                ], $this->getPackageHandle());
            }
        }
        $this->addResponseKey('html', ob_get_contents());
        ob_end_clean();
    }

    /**
     * getNextPage abstract to force consistency in ajax controllers
     * Example code here: https://gist.github.com/prolificjones82/de53ad427ec379de38f0451bd0692a2c
     */
    abstract protected function getNextPage();

    /**
     * Check to see if class extends any others
     */
    public function hasParents()
    {
        return (bool) class_parents($this);
    }

    /**
     * Get the value of currentPage
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Set the value of currentPage
     *
     * @return self
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    /**
     * Get the value of pageNumber
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Set the value of pageNumber
     *
     * @return self
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    /**
     * Get the value of pageLength
     */
    public function getPageLength()
    {
        return $this->pageLength;
    }

    /**
     * Set the value of pageLength
     *
     * @return self
     */
    public function setPageLength($pageLength)
    {
        $this->pageLength = $pageLength;

        return $this;
    }

    /**
     * Get the value of sortOrder
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Set the value of sortOrder
     *
     * @return self
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get the value of response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get a value from request array by key
     *
     * @param string $key
     *
     * @return string|array|object|bool
     */
    public function getRequestKey($key)
    {
        return (isset($this->response['request'][$key])) ? $this->response['request'][$key] : false;
    }

    /**
     * Get a value from response array by key
     *
     * @param string $key
     *
     * @return string|array|object|bool
     */
    public function getResponseKey($key)
    {
        return (isset($this->response[$key])) ? $this->response[$key] : false;
    }

    /**
     * Add a key/value pair to response array
     *
     * @param string                   $key
     * @param string|array|object|bool $value
     */
    public function addResponseKey($key, $value)
    {
        $this->response[$key] = $value;
    }

    /**
     * Set the value of response
     *
     * @return self
     */
    public function setResponse($response)
    {
        $this->response['request'] = $this->sanitizeResponse($response);

        return $this;
    }

    /**
     * Get the value of debug
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set the value of debug
     *
     * @return self
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get the value of packageHandle
     */
    public function getPackageHandle()
    {
        return $this->packageHandle;
    }

    /**
     * Set the value of packageHandle
     *
     * @return self
     */
    public function setPackageHandle($packageHandle)
    {
        $this->packageHandle = $packageHandle;

        return $this;
    }

    /**
     * Get the value of noResultsMessage
     */
    public function getNoResultsMessage()
    {
        return $this->noResultsMessage;
    }

    /**
     * Set the value of noResultsMessage
     *
     * @return self
     */
    public function setNoResultsMessage($noResultsMessage)
    {
        $this->noResultsMessage = $noResultsMessage;

        return $this;
    }
}
