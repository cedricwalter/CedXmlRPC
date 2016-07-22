<?php
/**
 * XMLRPC
 * @version      3.4.8
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

class xmlRpcCommon
{

	protected $helper = null;
	private $featured;

	const USER_CODE = 800;

	/**
	 * xmlRpcCommon constructor.
	 *
	 * @param $helper
	 * @param $featured
	 * @since
	 */
	public function __construct($helper, $featured)
	{
		$this->helper   = $helper;
		$this->featured = $featured;
	}


	/**
	 * @return \PhpXmlRpc\Response
	 * @since
	 */
	public function getUserBlogs()
	{
		$args = func_get_args();

		if (func_num_args() < 3)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$username = $args[1];
		$password = $args[2];
		$user     = $this->helper->authenticateUser($username, $password);
		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		$app = JFactory::getApplication();

		$structArray = array();

		$mt = false;
		if (isset($args[3]))
		{
			$mt = (boolean) $args[3];
		}

		if (!$mt)
		{
			$site_name     = $app->get('sitename');
			$structArray[] = new PhpXmlRpc\Value(
				array(
					'url'      => new PhpXmlRpc\Value(JUri::root(), 'string'),
					'blogid'   => new PhpXmlRpc\Value(0, 'string'),
					'blogName' => new PhpXmlRpc\Value($site_name, 'string')
				)
				, 'struct');

			return new PhpXmlRpc\Response(new PhpXmlRpc\Value($structArray, 'array'));
		}

		$model      = $this->helper->getModel('Categories');
		$categories = $model->getItems();

		if (empty($categories))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_CATEGORY_WAS_NOT_FOUND'));
		}

		$structArray[] = new PhpXmlRpc\Value(
			array(
				'categoryId'   => new PhpXmlRpc\Value('-1', 'string'),
				'categoryName' => new PhpXmlRpc\Value(
					$this->helper->buildCategoryTitle(JText::_('PLG_XMLRPC_JOOMLA_FEATURED_TITLE'), 0, true), 'string')
			),
			'struct'
		);

		foreach ($categories as $row)
		{
			if ($row->published < 1)
			{
				if (!$user->authorise('core.edit.state', 'com_content.category.' . $row->id))
				{
					continue;
				}

				if (!$user->authorise('core.admin',
						'com_checkin') && $row->checked_out > 0 && $row->checked_out != $user->get('id')
				)
				{
					continue;
				}
			}

			$row->title    = str_repeat(' ...', $row->level - 1) . $row->title;
			$structArray[] = new PhpXmlRpc\Value(
				array(
					'categoryId'   => new PhpXmlRpc\Value($row->id, 'string'),
					'categoryName' => new PhpXmlRpc\Value($this->helper->buildCategoryTitle($row->title, $row->id), 'string')
					//				'categoryName' => new PhpXmlRpc\Value($row->title, 'string')
				),
				'struct');
		}

		if (empty($structArray))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_CATEGORY_WAS_NOT_FOUND'));
		}

		return new PhpXmlRpc\Response(new PhpXmlRpc\Value($structArray, 'array'));
	}

	/**
	 * @return PhpXmlRpc\Response
	 * @throws Exception
	 * @since
	 */
	public function getRecentPosts()
	{
		global $xmlrpcArray;

		$args = func_get_args();
		if (func_num_args() < 3)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$username = $args[1];
		$password = $args[2];
		$user     = $this->helper->authenticateUser($username, $password);
		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		$blogId = (int) $args[0];
		if ($blogId > 0)
		{
			JFactory::getApplication()->input->set('filter_category_id', $blogId);
		}

		$limit = 0;
		if (isset($args[3]))
		{
			$limit = (int) $args[3];
		}
		JFactory::getApplication()->input->set('limit', $limit);
		JFactory::getApplication()->input->set('filter_order', 'a.created');
		JFactory::getApplication()->input->set('filter_order_Dir', 'desc');
		$model = $this->helper->getModel('Articles');
//		$model->setState('list.limit', $limit);

		$userId = (int) $user->get('id');

		$temp     = $model->getItems();
		$articles = array();
		if (count($temp))
		{
			foreach ($temp as $row)
			{
				$canEdit    = $user->authorise('core.edit', 'com_content.article.' . $row->id);
				$canCheckin = $user->authorise('core.manage',
						'com_checkin') || $row->checked_out == $userId || $row->checked_out == 0;
				$canEditOwn = $user->authorise('core.edit.own',
						'com_content.article.' . $row->id) && $row->created_by == $userId;

				if (($canEdit || $canEditOwn) && $canCheckin)
				{
					$mt = false;
					if (isset($args[5]))
					{
						$mt = (boolean) $args[5];
					}
					$res = $this->helper->buildStruct($row, $mt);

					if ($res[0])
					{
						$articles[] = $res[1];
					}
				}
			}
		}

		return new PhpXmlRpc\Response(new PhpXmlRpc\Value($articles, $xmlrpcArray));
	}

	/**
	 * @return PhpXmlRpc\Response
	 * @throws Exception
	 * @since
	 */
	public function newPost()
	{
		$args = func_get_args();

		if (func_num_args() < 4)
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

		$blogId           = (int) $args[0];
		$content          = $args[3];
		$content['catid'] = (int) $blogId;

		if (isset($args[4]))
		{
			$publish = $args[4];
		}
		else
		{
			$publish = false;
		}

		$blogger = false;
		if (isset($args[5]))
		{
			$blogger = $args[5];
		}
		$data = $this->helper->buildData($content, $publish, $blogger);

		if ($this->featured)
		{
			$data['featured'] = 1;
		}

		$this->helper->assignCategory($data);

		JFactory::getApplication()->input->set('id', 0);
		$model = $this->helper->getModel('Article');
		$model->set('option', 'com_content');

		if ($model->allowAdd($data) !== true)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		if (!$model->save($data))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $model->getError());
		}

		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value($model->getState('article.id'), 'string')));
	}

	public function editPost()
	{
		$args = func_get_args();

		if (func_num_args() < 4)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$username = $args[1];
		$password = $args[2];
		$user     = $this->helper->authenticateUser($username, $password);
		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		$postId = (int) $args[0];

		$content       = $args[3];
		$content['id'] = $postId;
		JFactory::getApplication()->input->set('id', $postId);
		JFactory::getApplication()->input->set('id', $postId);

		$publish = (int) $args[4];
		$blogger = false;

		if (isset($args[5]))
		{
			$blogger = $args[5];
		}
		$data = $this->helper->buildData($content, $publish, $blogger);

		$this->helper->assignCategory($data);

		JFactory::getApplication()->input->set('id', $postId);
		$model = $this->helper->getModel('Article');
		$model->set('option', 'com_content');

		if ($model->allowEdit($data) !== true)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		if ($model->getItem()->featured && (!isset($data['featured']) || !$data['featured']))
		{
			$data['featured'] = 0;
		}

		if (!$model->save($data))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $model->getError());
		}

		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value('1', 'boolean')));
	}

	public function getUserInfo()
	{
		global $xmlrpcStruct;

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

		$name = $user->name;
		if (function_exists('mb_convert_kana'))
		{
			$name = mb_convert_kana($user->name, 's');
		}

		$names     = explode(' ', $name);
		$firstName = $names[0];
		$lastname  = trim(str_replace($firstName, '', $name));

		$struct = new PhpXmlRpc\Value(
			array(
				'nickname'  => new PhpXmlRpc\Value($user->username),
				'userid'    => new PhpXmlRpc\Value($user->id),
				'url'       => new PhpXmlRpc\Value(JUri::root()),
				'email'     => new PhpXmlRpc\Value($user->email),
				'lastname'  => new PhpXmlRpc\Value($lastname),
				'firstName' => new PhpXmlRpc\Value($firstName)
			), $xmlrpcStruct);

		return new PhpXmlRpc\Response($struct);
	}

	public function deletePost()
	{
		global $xmlrpcBoolean;

		$args = func_get_args();
		if (func_num_args() < 5)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}
		$postId   = (int) $args[1];
		$username = $args[2];
		$password = $args[3];
		$user     = $this->helper->authenticateUser($username, $password);
		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}
//		$publish = (int)$args[4];

		$userId = intval($user->get('id'));

		JFactory::getApplication()->input->set('id', $postId);
		$model = $this->helper->getModel('Article');
		$model->set('option', 'com_content');

		$row    = $model->getTable();
		$result = $row->load($postId);
		if (!$result)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ITEM_WAS_NOT_FOUND'));
		}

		if (!$model->canEditState($row))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		if (!$user->authorise('core.manage', 'com_checkin') && $row->checked_out > 0 && $row->checked_out != $userId)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1,
				JText::sprintf('PLG_XMLRPC_JOOMLA_EDITING_OTHER_USER', $row->title));
		}

		$model->checkout();

		$row->ordering = 0;
		$row->state    = -2; //to trash

		if (!$row->check())
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $row->getError());
		}

		if (!$row->store())
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $row->getError());
		}

		$model->checkin();

		return new PhpXmlRpc\Response(new PhpXmlRpc\Value('true', $xmlrpcBoolean));
	}

}