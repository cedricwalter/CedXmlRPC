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


require JPATH_LIBRARIES . '/cedxmlrpc/vendor/autoload.php';

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

class xmlRpcMetaweblog
{

	protected $helper = null;
	private $overwrite;
	private $useAbsoluteLinks;
	private $useFolder;
	const USER_CODE = 800;

	/**
	 * xmlRpcMetaweblog constructor.
	 *
	 * @param $helper
	 * @param $overwrite
	 * @param $useAbsoluteLinks
	 * @param $useFolder
	 * @param $maxSize
	 * @param $allowable
	 * @param $ignored
	 * @param $images
	 * @param $image_path
	 * @param $file_path
	 *
	 * @since
	 */
	public function __construct(
		$helper,
		$overwrite,
		$useAbsoluteLinks,
		$useFolder,
		$maxSize,
		&$allowable,
		&$ignored,
		$images,
		$image_path,
		$file_path
	) {
		$this->helper = $helper;

		$this->overwrite        = $overwrite;
		$this->useAbsoluteLinks = $useAbsoluteLinks;
		$this->useFolder        = $useFolder;
		$this->maxSize          = $maxSize;

		$this->allowable = $allowable;
		$this->ignored   = $ignored;
		$this->images    = $images;

		$this->image_path = $image_path;
		$this->file_path  = $file_path;
	}

	/**
	 * @return new PhpXmlRpc\Response
	 * @throws Exception
	 * @since
	 */
	public function getPost()
	{
		$args = func_get_args();

		if (func_num_args() < 3)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$username = strval($args[1]);
		$password = strval($args[2]);
		$user     = $this->helper->authenticateUser($username, $password);
		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		$postId = (int) $args[0];
		JFactory::getApplication()->input->set('id', $postId);
		$model = $this->helper->getModel('Article');
		$model->set('option', 'com_content');

		$data       = array();
		$data['id'] = $postId;

		if ($model->allowEdit($data) !== true)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		$row = $model->getItem($postId);
		if (empty($row))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ITEM_WAS_NOT_FOUND'));
		}

		$ret = $this->helper->buildStruct($row);

		if (!$ret[0])
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $ret[1]);
		}

		return new PhpXmlRpc\Response($ret[1]);
	}

	/**
	 * @return new PhpXmlRpc\Response
	 * @since
	 */
	public function newMediaObject()
	{
		$args = func_get_args();

		if (func_num_args() < 4)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		//$blogId = (int)$args[0];
		$username = strval($args[1]);
		$password = strval($args[2]);
		$user     = $this->helper->authenticateUser($username, $password);
		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		$file_struct = $args[3];
		/**
		 * It seems that WLW may be uploaded first. (The article is not registered.)
		 */
//		JFactory::getApplication()->input->set('id', $blogid);
//		$model  = $this->helper->getModel('Article');
//		$model->set('option', 'com_content');
//
//		$row = $model->getTable();
//		$result = $row->load($blogid);
//		if ($result)
//		{
//			if (!$user->authorise('core.manage', 'com_checkin') && $row->checked_out > 0 && $row->checked_out != $user->get('id'))
//			{
//				return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::sprintf('PLG_XMLRPC_JOOMLA_EDITING_OTHER_USER', $row->title));
//			}
//
//			$data = array();
//			$data['id'] = $row->id;
//			$data['created_by'] = $row->created_by;
//			if ($model->allowEdit($data) !== true)
//			{
//				return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
//			}
//		}
//		else
//		{
//			//no check
//		}

		$file = $file_struct['bits'];

		if (empty($file))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_FILE_EMPTY'));
		}
		if ($this->maxSize && strlen($file) > $this->maxSize)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_NOT_ALLOWED_FILE_SIZE'));
		}
		if (empty($file_struct['name']))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_FILE_EMPTY'));
		}

		//filename check
		$temp      = pathinfo($file_struct['name']);
		$file_name = strtolower(JFile::makeSafe(str_replace(' ', '_', trim($temp['basename']))));
		if (empty($file_name))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_FILENAME_EMPTY'));
		}

		$ext          = JFile::getExt($file_name);
		$isNotAllowed = !in_array($ext, $this->allowable);
		$isNotIgnored = !in_array($ext, $this->ignored);
		if ($isNotAllowed && $isNotIgnored)
		{
			error_log($ext . " " . $isNotAllowed . " is " . $isNotIgnored . JText::_('PLG_XMLRPC_JOOMLA_NOT_ALLOWED_FILETYPE'));

			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_NOT_ALLOWED_FILETYPE'));
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_media/helpers/media.php';
		if (in_array($ext, $this->images))
		{
			$destination = str_replace(array('/', '\\'), '/', JPATH_ROOT . '/' . $this->image_path);
		}
		else
		{
			$destination = str_replace(array('/', '\\'), '/', JPATH_ROOT . '/' . $this->file_path);;
		}

		$destination .= '/';

		if ($this->useFolder)
		{
			$userFolder = JFile::makeSafe($username);
			if (!empty($userFolder))
			{
				$destination .= $userFolder;
				if (!JFolder::exists($destination))
				{
					if (!JFolder::create($destination))
					{
						error_log(JText::_('PLG_XMLRPC_JOOMLA_NOT_ABLE_TO_CREATE_FOLDER'));

						return new PhpXmlRpc\Response(0, self::USER_CODE + 1,
							JText::_('PLG_XMLRPC_JOOMLA_NOT_ABLE_TO_CREATE_FOLDER'));
					}
				}

				// security add index.html
				if (!JFile::exists($destination . '/' . 'index.html'))
				{
					$html = '<html><body></body></html>';
					JFile::write($destination . '/' . 'index.html', $html);
				}

				$destination .= '/';
			}
		}

		$absoluteFileName = $destination . $file_name;
		if (file_exists($absoluteFileName) && (/* !isset($file_struct['overwrite']) || !$file_struct['overwrite'] || */
			!$this->overwrite)
		)
		{
			//TODO what is this code
			$nameOnly = str_replace(strrchr($file_name, '.'), '', $file_name); //for 1.5.10 or under
			$nameOnly .= '_' . JApplicationHelper::getHash(microtime() * 1000000);
			//$file_name = JFile::makeSafe($nameOnly . '.' . $ext);
		}

		if (!JFile::write($absoluteFileName, $file))
		{
			error_log(JText::_('PLG_XMLRPC_JOOMLA_NOT_ABLE_TO_WRITE_FILE'));

			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_NOT_ABLE_TO_WRITE_FILE'));
		}

		if (!file_exists($absoluteFileName))
		{
			error_log(JText::_('PLG_XMLRPC_JOOMLA_NOT_ABLE_TO_UPLOAD_FILE'));

			return new PhpXmlRpc\Response(0, self::USER_CODE + 1,
				JText::sprintf('PLG_XMLRPC_JOOMLA_NOT_ABLE_TO_UPLOAD_FILE'));
		}

		if ($this->useAbsoluteLinks === "1")
		{
			$url = JUri::root(true);
		}
		else
		{
			$url = rtrim(JUri::root(), '/');
		}


		$root_path = str_replace(DIRECTORY_SEPARATOR, '/', JPATH_ROOT);
		$url .= str_replace(array($root_path, '/'), array('', '/'), $absoluteFileName);

		$response = array('url' => new PhpXmlRpc\Value($url, 'string'));

		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value($response, 'struct')));
	}

}