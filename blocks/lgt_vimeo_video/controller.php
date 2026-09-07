<?php namespace Concrete\Package\LgtToolkit\Block\LgtVimeoVideo;

defined('C5_EXECUTE') or die("Access Denied.");

use Page;
use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
	var $pobj;

	protected $btTable = 'btVimeoVid';
    protected $btDefaultSet = 'multimedia';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;

	public function getBlockTypeName()
	{
		return t('Vimeo Video');
	}

	public function getBlockTypeDescription()
	{
		return t('Easily add a Vimeo video to your page.');
	}

	public function add()
	{
		$this->set('color', $this->app->make('helper/form/color'));
	}

	public function edit()
	{
		$this->set('color', $this->app->make('helper/form/color'));
	}

	public function view()
	{
		$this->set('c', Page::getCurrentPage());
		$this->set('vimeoColor', ltrim($this->vvColor, '#'));
	}

	function save($data)
	{
		if (isset($data['vimeoVid'])) {
			$videoId = (int) substr(parse_url($data['vimeoVid'], PHP_URL_PATH), 1);
			$args['vimeoVid'] = $videoId;
		} else {
			$args['vimeoVid'] = null;
		}

		$args['vvTitle'] 	= $data['vvTitle'] ?? null;
		$args['vvUser'] 	= $data['vvUser'] ?? null;
		$args['vvHeight'] 	= is_numeric($data['vvHeight']) ? intval($data['vvHeight']) : '280';
		$args['vvWidth'] 	= is_numeric($data['vvWidth']) ? intval($data['vvWidth']) : '500';
		$args['vvColor'] 	= isset($data['vvColor']) ? $data['vvColor'] : '#00adef';

		$args['autoplay'] 	= (isset($data['autoplay'])) ? '1' : '0';
		$args['vvloop'] 	= (isset($data['vvloop'])) ? '1' : '0';
		$args['showlink'] 	= (isset($data['showlink'])) ? '1' : '0';

		$args['introTitle'] = (isset($data['introTitle'])) ? '1' : '0';
		$args['portrait'] 	= (isset($data['portrait'])) ? '1' : '0';
		$args['byline'] 	= (isset($data['byline'])) ? '1' : '0';

		parent::save($args);
	}

}
