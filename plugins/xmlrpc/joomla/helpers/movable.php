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

class xmlRpcMovable
{

	protected $helper = null;
	protected $common = null;
	const USER_CODE = 800;

	/**
	 * xmlRpcMovable constructor.
	 *
	 * @param $helper
	 * @param $common
	 * @since
	 */
	public function __construct($helper, $common)
	{
		$this->helper = $helper;
		$this->common = $common;
	}

	public function getPostCategories()
	{
		$args = func_get_args();

		if (func_num_args() < 3)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$postid   = (int) $args[0];
		$username = strval($args[1]);
		$password = strval($args[2]);

		$user = $this->helper->authenticateUser($username, $password);

		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		JFactory::getApplication()->input->set('id', $postid);
		$model = $this->getModel('Article');
		$model->set('option', 'com_content');

		$row = $model->getItem($postid);
		if (!$row)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ITEM_WAS_NOT_FOUND'));
		}

		$data               = array();
		$data['id']         = $row->id;
		$data['created_by'] = $row->created_by;
		if ($model->allowEdit($data) !== true)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		if (empty($row->catid))
		{
			return (new PhpXmlRpc\Response(new PhpXmlRpc\Value(array(), 'array')));
		}
		else
		{
			$cmodel   = $this->getModel('Category');
			$category = $cmodel->getItem((int) $row->catid);
			if (empty($category))
			{
				return new PhpXmlRpc\Response(0, self::USER_CODE + 1,
					JText::_('PLG_XMLRPC_JOOMLA_CATEGORY_WAS_NOT_FOUND'));
			}

			if (!$cmodel->canEditState($category) && $category->published < 1)
			{
				return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
			}
		}

		$structArray = array();

		//featured article
		if ($row->featured)
		{
			$structArray[] = $this->helper->getFeatureStruct(true);
		}

		$structArray[] = new PhpXmlRpc\Value(
			array(
				'categoryName' => new PhpXmlRpc\Value($category->title, 'string'),
				'categoryId'   => new PhpXmlRpc\Value($category->id, 'string'),
				'isPrimary'    => new PhpXmlRpc\Value(1, 'boolean')
			),
			'struct');

		return new PhpXmlRpc\Response(new PhpXmlRpc\Value($structArray, 'array'));
	}

	public function setPostCategories()
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

		$blogId = (int) $args[0];
		JFactory::getApplication()->input->set('id', $blogId);
		$model = $this->getModel('Article');
		$model->set('option', 'com_content');

		$row    = $model->getTable();
		$result = $row->load($blogId);
		if (!$result)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ITEM_WAS_NOT_FOUND'));
		}

		if (!$user->authorise('core.manage',
				'com_checkin') && $row->checked_out > 0 && $row->checked_out != $user->get('id')
		)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1,
				JText::sprintf('PLG_XMLRPC_JOOMLA_EDITING_OTHER_USER', $row->title));
		}

		$data               = array();
		$data['id']         = $row->id;
		$data['created_by'] = $row->created_by;
		if ($model->allowEdit($data) !== true)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

//		$cmodel = $this->getModel('Category');
		$categories = $args[3];
		if ($blogId && is_array($categories) && count($categories))
		{
			$model->checkout();

			$categoryId    = 0;
			$primary_catid = 0;
			for ($i = 0; $i < count($categories); $i++)
			{
				if (!isset($categories[$i]['categoryId']))
				{
					continue;
				}

				if ((int) $categories[$i]['categoryId'] < 1)
				{
					continue;
				}

				$tempcatid = (int) $categories[$i]['categoryId'];

				if ($categoryId == 0)
				{
					$categoryId = $tempcatid;
				}

				if (isset($categories[$i]['isPrimary']) && $categories[$i]['isPrimary'])
				{
					$primary_catid = $tempcatid;
				}
			}

			if ($categoryId && $primary_catid && $primary_catid !== $categoryId)
			{
				$categoryId = $primary_catid;
			}

			if (!$categoryId)
			{
				return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_CORRECT_CATEGORY'));
			}

			$row->catid = $categoryId;

			$data = $row->getProperties(true);

			if (!$model->save($data))
			{
				return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $model->getError());
			}

			$model->checkin();
		}

		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value('1', 'boolean')));
	}

	public function getRecentPostTitles()
	{
		$args = func_get_args();

		if (func_num_args() < 4)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$blogid   = (int) $args[0];
		$username = strval($args[1]);
		$password = strval($args[2]);

		$limit = 0;
		if (isset($args[4]))
		{
			$limit = (int) $args[4];
		}

		return $this->common->getRecentPosts($blogid, $username, $password, $limit, true);
	}

	public function getCategoryList()
	{
		$args = func_get_args();

		if (func_num_args() < 3)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$blogid   = (int) $args[0];
		$username = strval($args[1]);
		$password = strval($args[2]);

		return $this->common->getUserBlogs($blogid, $username, $password, true);
	}

	public function supportedTextFilters()
	{
		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value(array(), 'array')));
	}

	public function publishPost()
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
		$model = $this->getModel('Article');
		$model->set('option', 'com_content');

		$row    = $model->getTable();
		$result = $row->load($postId);
		if (!$result)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ITEM_WAS_NOT_FOUND'));
		}

		if (!$user->authorise('core.edit.state', 'com_content.article.' . $row->id))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		if (!$user->authorise('core.manage',
				'com_checkin') && $row->checked_out > 0 && $row->checked_out != $user->get('id')
		)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1,
				JText::sprintf('PLG_XMLRPC_JOOMLA_EDITING_OTHER_USER', $row->title));
		}

		$data               = array();
		$data['id']         = $row->id;
		$data['created_by'] = $row->created_by;
		if ($model->allowEdit($data) !== true)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		$model->checkout();

		$row->state = 1;
		if (!$row->check())
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $row->getError());
		}

		$row->version++;

		if (!$row->store())
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $row->getError());
		}

		$model->checkin();

		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value('1', 'boolean')));
	}

	public function getTrackbackPings()
	{
//		$args = func_get_args();

		if (func_num_args() < 1)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

//		$blogid = (int)$args[0];
		//pingIP, pingURL, pingTitle
		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value(array(), 'array')));
	}

	public function supportedMethods()
	{
		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value(array(), 'array')));
	}

	public function getModel($type, $prefix = 'XMLRPCModel', $config = array())
	{
		return JModelLegacy::getInstance($type, $prefix, $config);
	}

}