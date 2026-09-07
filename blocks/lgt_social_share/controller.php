<?php

namespace Concrete\Package\LgtToolkit\Block\LgtSocialShare;

defined('C5_EXECUTE') or die('Access Denied.');

use Page;
use Concrete\Core\Block\BlockController;
use LgtToolkit\ShareLink\Link as ShareLink;
use Doctrine\Common\Collections\ArrayCollection;
use Concrete\Core\Sharing\SocialNetwork\ServiceList;

class Controller extends BlockController
{
    protected $btTable = 'btLgtSocialShare';
    protected $btExportTables = ['btLgtSocialShare', 'btLgtSocialShareNetworks'];
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 550;
    protected $shareableNetworks = [
        'facebook',
        'twitter',
        'linkedin',
    ];


    protected function getSocialNetworks()
    {
        $list = ServiceList::get();
        foreach ($list as $service) {
            if (in_array($service->getHandle(), $this->shareableNetworks)) {
                $services[$service->getHandle()] = $service->getName();
            }
        }

        return $services;
    }

    protected function getShareLinks()
    {
        $db = $this->app->make('database')->connection();

        $q = 'SELECT * FROM `btLgtSocialShareNetworks` WHERE `bID` = ? ORDER BY `id` ASC';
        $v = [
            $this->bID,
        ];

        $rows = $db->fetchAll($q, $v);

        return $rows;
    }

    public function getBlockTypeName()
    {
        return t('Social Share');
    }

    public function getBlockTypeDescription()
    {
        return t('Add a share block to a page.');
    }

    public function add()
    {
        $this->set('services', []);
        $this->set('services', $this->getSocialNetworks());
    }

    public function edit()
    {
        $selected = [];
        $links = $this->getShareLinks();
        foreach ($links as $link) {
            $selected[] = $link['serviceHandle'];
        }

        $this->set('services', $this->getSocialNetworks());
        $this->set('selected', $selected);
    }

    public function view()
    {
        $rows = $this->getShareLinks();
        $shareLinks = new ArrayCollection();

        if (is_array($rows) && count($rows) > 0) {
            foreach ($rows as $row) {
                $link = new ShareLink();
                $link->setId($row['id']);
                $link->setBID($row['bID']);
                $link->setServiceHandle($row['serviceHandle']);

                $shareLinks->add($link);
            }
        }

        $this->set('shareLinks', $shareLinks);
        $this->set('c', Page::getCurrentPage());
    }

    public function duplicate($newBID)
    {
        $db = $this->app->make('database')->connection();
        $v = [$this->bID];
        $q = 'SELECT * FROM `btLgtSocialShareNetworks` WHERE bID = ?';
        $r = $db->executeQuery($q, $v);
        foreach ($r as $row) {
            $db->executeQuery(
                'INSERT INTO `btLgtSocialShareNetworks` (`bID`, `serviceHandle`) VALUES (?, ?)',
                [
                    $newBID,
                    $row['serviceHandle'],
                ],
            );
        }
    }

    public function delete()
    {
        $db = $this->app->make('database')->connection();
        $db->executeQuery('DELETE FROM `btLgtSocialShareNetworks` WHERE bID = ?', [$this->bID]);
        parent::delete();
    }

    public function save($args)
    {
        $db = $this->app->make('database')->connection();

        // Clear old data for individual links
        $q = 'DELETE FROM `btLgtSocialShareNetworks` WHERE `bID` = ?';
        $v = [
            $this->bID,
        ];
        $db->executeQuery($q, $v);

        parent::save([ 'title' => $args['share_title'] ]);


        if (array_key_exists('socialHandles', $args)) {
            foreach ($args['socialHandles'] as $k => $v) {
                $temp_args = [
                    $this->bID,
                    $args['socialHandles'][$k],
                ];

                $q = 'INSERT INTO `btLgtSocialShareNetworks` (`bID`, `serviceHandle`) VALUES (?, ?)';
                $db->executeQuery($q, $temp_args);
            }
        }
    }

    public function isBlockEmpty()
    {
        if (count($this->getShareLinks()) > 0) {
            return false;
        }
        return true;

    }
}
