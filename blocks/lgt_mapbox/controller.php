<?php
namespace Concrete\Package\LgtToolkit\Block\LgtMapbox;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Legacy\BlockRecord;
use Page;

class Controller extends BlockController
{
    protected $btTable = 'btLgtMapbox';
    protected $btDefaultSet = 'multimedia';
    protected $btExportTables = [
        'btLgtMapboxMarkers'
    ];
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 550;
    protected string $centerLatitude;
    protected string $centerLongitude;
    protected int $zoom;
    protected int $pitch;
    protected string $theme;
    protected int $interactive;
    protected int $show_controls;
    protected string $control_placement;
    protected int $showBuildings;
    protected string $extrusionColor;

    public function getBlockTypeName()
    {
        return t('Mapbox');
    }

    public function getBlockTypeDescription()
    {
        return t('Create mapbox map.');
    }

    public function on_start()
    {
        parent::on_start();

        $html = $this->app->make('helper/html');

        $this->addHeaderItem($html->css('https://api.mapbox.com/mapbox-gl-js/v2.0.0/mapbox-gl.css'));
        $this->addHeaderItem($html->javascript('https://api.mapbox.com/mapbox-gl-js/v2.0.0/mapbox-gl.js'));
    }

    public function getControlPlacementOptions(): array
    {
        return [
            'top-left'      => t('Top Left'),
            'top-right'     => t('Top Right'),
            'bottom-left'   => t('Bottom Left'),
            'bottom-right'  => t('Bottom Right')
        ];
    }

    public function add()
    {
        $this->set('rows', $this->getMarkers(true));
        $this->set('ch', $this->app->make('helper/form/color'));
    }

    public function edit()
    {
        $this->set('rows', $this->getMarkers(true));
        $this->set('ch', $this->app->make('helper/form/color'));
    }

    public function view()
    {
        $config = [
            'centerLatitude'    => $this->centerLatitude,
            'centerLongitude'   => $this->centerLongitude,
            'zoom'              => $this->zoom,
            'pitch'             => $this->pitch,
            'theme'             => $this->theme,
            'interactive'       => ($this->interactive > 0) ? true : false,
            'show_controls'     => ($this->show_controls > 0) ? true : false,
            'control_placement' => $this->control_placement,
            'showBuildings'     => ($this->showBuildings > 0) ? true : false,
            'extrusionColor'    => $this->extrusionColor,
            'markers'           => $this->getMarkers()
        ];

        $this->set('config', json_encode($config));
        $this->set('c', Page::getCurrentPage());
    }

    public function save($args)
    {
        $db = $this->app->make('database')->connection();

        // Clear old data for markers
        $q = 'DELETE FROM `btLgtMapboxMarkers` WHERE `bID` = ?';
        $v = [
            $this->bID
        ];
        $db->executeQuery($q, $v);

        // save primary block info
        parent::save([
            'centerLatitude'    => $args['centerLatitude'],
            'centerLongitude'   => $args['centerLongitude'],
            'zoom'              => $args['zoom'],
            'pitch'             => $args['pitch'],
            'theme'             => $args['theme'],
            'interactive'       => (isset($args['interactive'])) ? 1 : 0,
            'showBuildings'     => (isset($args['showBuildings'])) ? 1 : 0,
            'show_controls'     => (isset($args['show_controls'])) ? 1 : 0,
            'control_placement' => $args['control_placement'],
            'extrusionColor'    => $args['extrusionColor']
        ]);

        if (array_key_exists('sortOrder', $args)) {
            foreach ($args['sortOrder'] as $k => $v) {
                $markerArgs = [
                    $this->bID,
                    $args['latitude'][$k],
                    $args['longitude'][$k],
                    $args['markerColor'][$k],
                    $args['sortOrder'][$k]
                ];

                $q = 'INSERT INTO `btLgtMapboxMarkers` (`bID`, `latitude`, `longitude`, `markerColor`, `sortOrder`) VALUES (?, ?, ?, ?, ?)';
                $db->executeQuery($q, $markerArgs);
            }
        }
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

        // Clear old data for markers
        $q = 'DELETE FROM `btLgtMapboxMarkers` WHERE `bID` = ?';
        $v = [
            $this->bID
        ];
        $db->executeQuery($q, $v);

        $q = 'SELECT * FROM `btLgtMapboxMarkers` WHERE `bID` = ?';
        $v = [
            $this->bID
        ];
        $r = $db->executeQuery($q, $v);

        foreach ($r as $row) {
            $markerArgs = [
                $newBlockID,
                $row['latitude'],
                $row['longitude'],
                $row['customMarker'],
                $row['markerColor'],
                $row['markerTitle'],
                $row['markerContent'],
                $row['sortOrder']
            ];

            $q = 'INSERT INTO `btLgtMapboxMarkers` (`bID`, `latitude`, `longitude`, `customMarker`, `markerColor`, `markerTitle`, `markerContent`, `sortOrder`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $db->executeQuery($q, $markerArgs);
        }

        parent::duplicate($newBlockID);
    }

    public function delete()
    {
        $db = $this->app->make('database')->connection();
        $db->executeQuery('DELETE FROM `btLgtMapboxMarkers` WHERE bID = ?', [ $this->bID ]);
        parent::delete();
    }

    protected function getMarkers($form = false)
    {
        $db = $this->app->make('database')->connection();

        $q = 'SELECT * FROM `btLgtMapboxMarkers` mbm WHERE `bID` = ?';
        $v = [
            $this->bID
        ];

        $rows = $db->fetchAll($q, $v);

        if (count($rows) > 0) {
            if ($form) {
                return $rows;
            }

            $markers = [];

            foreach ($rows as $row) {
                $markers[] = [
                    'latitude'      => $row['latitude'],
                    'longitude'     => $row['longitude'],
                    'markerColor'   => $row['markerColor']
                ];
            }

            return $markers;
        }

        return false;
    }
}
