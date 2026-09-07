<?php
namespace Concrete\Package\LgtToolkit\Block\LgtManualNav;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Legacy\BlockRecord;
use Doctrine\Common\Collections\ArrayCollection;
use LgtToolkit\ManualLink\Link as NavLink;
use Page;

class Controller extends BlockController
{
    protected $btTable = 'btLgtManualNav';
    protected $btExportTables = array('btLgtManualNav', 'btLgtManualNavLinks');
    protected $btDefaultSet = 'navigation';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;

    public function getBlockTypeName()
    {
        return t('Manual Nav');
    }

    public function getBlockTypeDescription()
    {
        return t('Create and add a manual nav to a page.');
    }

    public function add()
    {
        $this->loadFormData();
    }

    public function edit()
    {
        $this->loadFormData();
        $this->set('rows', $this->getEntries());
    }

    /**
     * Automatically run when a block is duplicated. This most likely happens when a block is edited: a block is first duplicated, and then presented to the user to make changes.
     *
     * @param int $newBlockID
     *
     * @return BlockRecord | null $newInstance
     */
    public function duplicate($newBlockID)
    {
        $db = $this->app->make('database')->connection();
        $v = [$this->bID];
        $q = 'SELECT * FROM `btLgtManualNavLinks` WHERE bID = ?';
        $r = $db->executeQuery($q, $v);
        foreach ($r as $row) {
            $db->executeQuery(
                'INSERT INTO `btLgtManualNavLinks` (`bID`, `title`, `page_cID`, `is_external`, `external_url`, `sort_order`) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $newBlockID,
                    $row['title'],
                    $row['page_cID'],
                    $row['is_external'],
                    $row['external_url'],
                    $row['sort_order'],
                ]
            );
        }
        parent::duplicate($newBlockID);
    }

    public function delete()
    {
        $db = $this->app->make('database')->connection();
        $db->executeQuery('DELETE FROM `btLgtManualNavLinks` WHERE bID = ?', [$this->bID]);
        parent::delete();
    }

    public function loadFormData()
    {
        $this->requireAsset('core/sitemap');

        $token = $this->app->make('token');
        $identifier = $this->app->make('helper/validation/identifier');

        $this->set('token', $token);
        $this->set('get_string', $identifier->getString(18));
    }

    public function save($args)
    {
        $db = $this->app->make('database')->connection();
        $nh = $this->app->make('helper/navigation');

        // Clear old data for individual links
        $q = 'DELETE FROM `btLgtManualNavLinks` WHERE `bID` = ?';
        $v = array($this->bID);

        $db->executeQuery($q, $v);

        // Save main block information
        parent::save(array('title' => $args['nav_title']));

        if (array_key_exists('sort_order', $args)) {
            foreach ($args['sort_order'] as $k => $v) {
                if ($args['page_cID'][$k] > 0) {
                    $page = Page::getByID($args['page_cID'][$k]);
                } else {
                    $page = false;
                }

                if ($args['is_external'][$k] == 1) {
                    $page_cID = 0;
                    $needles = ['http://', 'https://'];
                    $found = false;

                    foreach ($needles as $needle) {
                        if (stripos($args['external_url'][$k], $needle) !== false) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $external_url = 'http://' . $args['external_url'][$k];
                    } else {
                        $external_url = $args['external_url'][$k];
                    }
                } else {
                    $page_cID = $args['page_cID'][$k];
                    $external_url = '';
                }

                if (strlen($args['title'][$k]) > 0) {
                    $title = $args['title'][$k];
                } else {
                    if ($page) {
                        $title = $page->getCollectionName();
                    } elseif ($args['is_external'][$k] == 1) {
                        $title = $external_url;
                    } else {
                        $title = 'Link';
                    }
                }

                $temp_args = array(
                    $this->bID,
                    $title,
                    $page_cID,
                    $args['is_external'][$k],
                    $external_url,
                    $args['sort_order'][$k],
                );

                $q = 'INSERT INTO `btLgtManualNavLinks` (`bID`, `title`, `page_cID`, `is_external`, `external_url`, `sort_order`) VALUES (?, ?, ?, ?, ?, ?)';
                $db->executeQuery($q, $temp_args);
            }
        }
    }

    public function getEntries()
    {
        $db = $this->app->make('database')->connection();

        $q = 'SELECT * FROM `btLgtManualNavLinks` WHERE `bID` = ? ORDER BY `sort_order` ASC';
        $v = array($this->bID);

        $rows = $db->fetchAll($q, $v);

        return $rows;
    }

    public function view()
    {
        $rows = $this->getEntries();
        $links = new ArrayCollection();

        if (is_array($rows) && count($rows) > 0) {
            foreach ($rows as $row) {
                $link = new NavLink();
                $link->setId($row['id']);
                $link->setBlockId($row['bID']);
                $link->setTitle($row['title']);
                $link->setLinkCID($row['page_cID']);
                $link->setIsExternal($row['is_external']);
                $link->setExternalURL($row['external_url']);
                $link->setSortOrder($row['sort_order']);

                $links->add($link);
            }
        }

        $this->set('links', $links);
        $this->set('c', Page::getCurrentPage());
    }
}
