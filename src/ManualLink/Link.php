<?php
namespace LgtToolkit\ManualLink;

use Page;
use Localization;
use Concrete\Core\Multilingual\Page\Section\Section;

class Link
{
    protected int $id;
    protected int $bID;
    protected string $title;
    protected int $linkCID;
    protected bool $isExternal;
    protected string $externalUrl;
    protected int $sortOrder;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of bID
     */
    public function getBlockId()
    {
        return $this->bID;
    }

    /**
     * Set the value of bID
     *
     * @return  self
     */
    public function setBlockId(int $bID)
    {
        $this->bID = $bID;

        return $this;
    }

    /**
     * Get the value of title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of linkCID
     */
    public function getLinkCID()
    {
        return $this->linkCID;
    }

    /**
     * Set the value of linkCID
     *
     * @return  self
     */
    public function setLinkCID(int $linkCID)
    {
        $this->linkCID = $linkCID;

        return $this;
    }

    /**
     * Get the value of isExternal
     */
    public function getIsExternal($as_bool = false)
    {
        if ($as_bool) {
            return ($this->isExternal > 0) ? true : false;
        }

        return $this->isExternal;
    }

    /**
     * Set the value of isExternal
     *
     * @return  self
     */
    public function setIsExternal(int $isExternal)
    {
        $this->isExternal = $isExternal;

        return $this;
    }

    /**
     * Get the value of externalUrl
     */
    public function getExternalURL()
    {
        return $this->externalUrl;
    }

    /**
     * Set the value of externalUrl
     *
     * @return  self
     */
    public function setExternalURL(string $externalUrl)
    {
        $this->externalUrl = $externalUrl;

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
     * @return  self
     */
    public function setSortOrder(int $sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get Page Link
     *
     * @return string
     */
    public function getPageLink(): string
    {
        $page = Page::getByID($this->getLinkCID());

        if (is_object($page)) {
            $availableLanguages = Localization::getAvailableInterfaceLanguages();
            if (count($availableLanguages) > 1) {
                $language = Localization::activeLocale();

                if ($language) {
                    $section = Section::getByLocale($language);

                    if (is_object($section)) {
                        $langPageId = $section->getTranslatedPageID($page);

                        if ($langPageId) {
                            $langPage = Page::getByID($langPageId);

                            if (is_object($langPage)) {
                                return $langPage->getCollectionLink();
                            }
                        }
                    }
                }
            }

            return $page->getCollectionLink();
        }

        return '';
    }

    /**
     * Get link path
     *
     * @return string
     */
    public function getPath(): string
    {
        if ($this->getIsExternal(true)) {
            return $this->getExternalURL();
        } else {
            return $this->getPageLink();
        }
    }

    /**
     * Get Link Target
     *
     * @return string
     */
    public function getTarget(): string
    {
        return ($this->getIsExternal(true)) ? '_blank' : '_self';
    }
}
