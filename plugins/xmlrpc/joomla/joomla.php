<?php
/**
 * XMLRPC
 * @version      3.5.1
 * @package      XMLRPC for Joomla!
 * @copyright    Copyright (C) 2016 Galaxiis All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * @author       galaxiiscom@gmail.com
 * @link         http://www.galaxiis.com/
 */

/**
 * XMLRPC
 * @version          2.0.6
 * @package          XMLRPC for Joomla!
 * @copyright        Copyright (C) 2007 - 2013 Yoshiki Kozaki All rights reserved.
 * @license          http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * @author           Yoshiki Kozaki  info@joomler.net
 * @link             http://www.joomler.net/
 */

/**
 * @package          Joomla
 * @copyright        Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license          GNU/GPL
 */

/**
 * ABOUT jMT_API
 * @package   jMT_API
 * @version   1.0a
 * @copyright Copyright (C) 2006 dex_stern. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die();

require_once(dirname(__FILE__) . '/helpers/blogger.php');
require_once(dirname(__FILE__) . '/helpers/common.php');
require_once(dirname(__FILE__) . '/helpers/metaweblog.php');
require_once(dirname(__FILE__) . '/helpers/helper.php');
require_once(dirname(__FILE__) . '/helpers/movable.php');
require_once(dirname(__FILE__) . '/helpers/wordpress.php');

class plgXMLRPCJoomla extends JPlugin
{
	protected $wordpress = null;
	protected $blogger = null;
	protected $metaweblog = null;
	protected $movable = null;
	protected $common = null;

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('', JPATH_ADMINISTRATOR);

		$beforeWrapId     = ' ' . trim($this->params->get('beforewrapid', '('));
		$afterWrapId      = trim($this->params->get('afterwrapid', ')'));
		$featured         = $this->params->get('featured', 0);
		$overwrite        = $this->params->get('overwrite');
		$useAbsoluteLinks = $this->params->get('absolute_link', 0);
		$useFolder        = $this->params->get('userFolder');
		$language         = $this->params->get('language', '*');

		$params     = JComponentHelper::getParams('com_media');
		$maxSize    = (int) ($params->get('upload_maxsize', 0) * 1024 * 1024);
		$allowable  = explode(',', $params->get('upload_extensions'));
		$ignored    = explode(',', $params->get('ignore_extensions'));
		$images     = explode(',', $params->get('image_extensions'));
		$image_path = $params->get('image_path', 'images');
		$file_path  = $params->get('file_path', 'images');

		$readMore  = $this->params->get('readmore');
		$pageBreak = $this->params->get('pagebreak');

    	$helper = new xmlRpcFrontendHelper($language, $beforeWrapId, $afterWrapId, $readMore, $pageBreak);

		$this->common     = new xmlRpcCommon($helper, $featured);
		$this->wordpress  = new xmlRpcWordpress($language, $helper, $this->common);
		$this->blogger    = new xmlRpcBlogger($helper, $this->common);
		$this->metaweblog = new xmlRpcMetaweblog($helper, $overwrite, $useAbsoluteLinks, $useFolder, $maxSize,
			$allowable, $ignored, $images, $image_path, $file_path);
		$this->movable    = new xmlRpcMovable($helper, $this->common);
	}

	public function onGetWebServices()
	{
	    //$functions = array();
        //return array_merge($functions, $this->common->getRegisteredFunctions());

		return array
		(
			'blogger.getUsersBlogs'  => array('function' => array($this->common, 'getUserBlogs'), 'signature' => null),
			'blogger.getUserInfo'    => array('function' => array($this->common, 'getUserInfo'), 'signature' => null),
			'blogger.getRecentPosts' => array('function'  => array($this->common, 'getRecentPosts'),
			                                  'signature' => null
			),
			'blogger.newPost'        => array('function' => array($this->blogger, 'newPost'), 'signature' => null),
			'blogger.deletePost'     => array('function' => array($this->common, 'deletePost'), 'signature' => null),
			'blogger.editPost'       => array('function' => array($this->blogger, 'editPost'), 'signature' => null),
			'blogger.getTemplate'    => array('function' => array($this->blogger, 'editPost'), 'signature' => null),

			'metaWeblog.getUsersBlogs'  => array(
				'function'  => array($this->common, 'getUserBlogs'),
				'signature' => null
			),
			'metaWeblog.getUserInfo'    => array('function'  => array($this->common, 'getUserInfo'),
			                                     'signature' => null
			),
			'metaWeblog.deletePost'     => array('function' => array($this->common, 'deletePost'), 'signature' => null),
			'metaWeblog.newPost'        => array('function' => array($this->common, 'newPost'), 'signature' => null),
			'metaWeblog.editPost'       => array('function' => array($this->common, 'editPost'), 'signature' => null),
			'metaWeblog.getPost'        => array('function'  => array($this->metaweblog, 'getPost'),
			                                     'signature' => null
			),
			'metaWeblog.newMediaObject' => array('function'  => array($this->metaweblog, 'newMediaObject'),
			                                     'signature' => null
			),
			'metaWeblog.getRecentPosts' => array('function'  => array($this->common, 'getRecentPosts'),
			                                     'signature' => null
			),
			'metaWeblog.getCategories'  => array('function'  => array($this->wordpress, 'getCategories'),
			                                     'signature' => null
			),

			'mt.getCategoryList'      => array(
				'function'  => array($this->movable, 'getCategoryList'),
				'signature' => null
			),
			'mt.getPostCategories'    => array(
				'function'  => array($this->movable, 'getPostCategories'),
				'signature' => null
			),
			'mt.setPostCategories'    => array(
				'function'  => array($this->movable, 'setPostCategories'),
				'signature' => null
			),
			'mt.getRecentPostTitles'  => array(
				'function'  => array($this->movable, 'getRecentPostTitles'),
				'signature' => null
			),
			'mt.supportedTextFilters' => array('function'  => array($this->movable, 'supportedTextFilters'),
			                                   'signature' => null
			),
			'mt.publishPost'          => array('function' => array($this->movable, 'publishPost'), 'signature' => null),
			'mt.getTrackbackPings'    => array('function'  => array($this->movable, 'getTrackbackPings'),
			                                   'signature' => null
			),
			'mt.supportedMethods'     => array('function'  => array($this->movable, 'supportedMethods'),
			                                   'signature' => null
			),

			'wp.getUsersBlogs' => array('function' => array($this->wordpress, 'getUserBlogs'), 'signature' => null),
			'wp.getAuthors'    => array('function' => array($this->wordpress, 'getAuthors'), 'signature' => null),
			'wp.getCategories' => array('function' => array($this->wordpress, 'getCategories'), 'signature' => null),
			'wp.newCategory'   => array('function' => array($this->wordpress, 'newCategory'), 'signature' => null),
			'wp.getTags'       => array('function' => array($this->wordpress, 'getTags'), 'signature' => null)
		);
	}

}
