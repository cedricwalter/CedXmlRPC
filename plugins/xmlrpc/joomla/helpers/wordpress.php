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

class xmlRpcWordpress
{
	var $helper = null;
	var $common = null;
	const USER_CODE = 800;

	/**
	 * xmlRpcWordpress constructor.
	 *
	 * @param $language
	 * @param $helper
	 * @param $common
	 * @since
	 */
	public function __construct($language, $helper, $common)
	{
		$this->helper   = $helper;
		$this->common   = $common;
		$this->language = $language;
	}

	/**
	 * @return mixed
	 * @since
	 */
	public function getUserBlogs()
	{
		$args = func_get_args();

		if (func_num_args() != 2)
		{
			return $this->response(JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		return $this->common->getUserBlogs('wp', $args[0], $args[1]);
	}

	/**
	 * @return xmlrpcresp
	 * @since
	 */
	public function getTags()
	{
		$args = func_get_args();
		if (func_num_args() != 3)
		{
			return $this->response(JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$article_id = (int) $args[0];
		$username   = strval($args[1]);
		$password   = strval($args[2]);

		$user = $this->helper->authenticateUser($username, $password);
		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('a.id, a.title AS name, a.hits AS count');
		$query->select('CONCAT(a.id, ":", a.alias) AS slug');
		$query->from('#__tags AS a');
		$query->join('LEFT', '#__contentitem_tag_map AS b ON b.tag_id = a.id');
		//WLW sent blogid always
		if ($article_id > 1)
		{
			$query->where('b.content_item_id = ' . $article_id);
		}

		$query->where('a.id > 1');//no root

		$query->order('a.title');

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$array = array();
		if (count($rows))
		{
			JLoader::register('TagsHelperRoute', JPATH_ROOT . '/components/com_tags/helpers/route.php');

			foreach ($rows as $row)
			{
				$struct             = array();
				$struct['tag_id']   = new PhpXmlRpc\Value($row->id, 'int');
				$struct['name']     = new PhpXmlRpc\Value($row->name, 'string');
				$struct['count']    = new PhpXmlRpc\Value($row->count, 'int');
				$struct['html_url'] = new PhpXmlRpc\Value(JRoute::_(TagsHelperRoute::getTagRoute($row->slug)), 'string');
				$struct['rss_url']  = new PhpXmlRpc\Value(JRoute::_(TagsHelperRoute::getTagRoute($row->slug) . '&format=feed'),
					'string');
				$array[]            = new PhpXmlRpc\Value($struct, 'struct');
			}
		}

		return new PhpXmlRpc\Response(new PhpXmlRpc\Value($array, 'array'), 'array');
	}

	public function newCategory()
	{
		$args = func_get_args();

		if (func_num_args() < 4)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$username = strval($args[1]);
		$password = strval($args[2]);
		$category = $args[3];

		$user = $this->helper->authenticateUser($username, $password);

		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		if (!$user->authorise('core.create', 'com_content'))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_DO_NOT_HAVE_AUTH'));
		}

		if (empty($category['name']))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1,
				JText::_('PLG_XMLRPC_JOOMLA_CATEGORY_MUST_HAVE_TITLE'));
		}

		$category['title'] = $category['name'];
		unset($category['name']);

		$category['extension'] = 'com_content';
		$category['published'] = 1;
		$category['language']  = $this->language;

		$model = $this->helper->getModel('Category');
		if (!$model->save($category))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $model->getError());
		}

		return (new PhpXmlRpc\Response(new PhpXmlRpc\Value($model->getState('category.id'), 'string')));
	}


	public function getCategories()
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

		$structarray = array();

		JFactory::getApplication()->input->set('limit', 0);
		$model      = $this->helper->getModel('Categories');
		$categories = $model->getItems();

		if (empty($categories))
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_CATEGORY_WAS_NOT_FOUND'));
		}

		$array = array();

		//Featured
		$array['categoryId']          = new PhpXmlRpc\Value('-1', 'string');
		$array['parentId']            = new PhpXmlRpc\Value('0', 'string');
		$array['description']         = new PhpXmlRpc\Value(JText::_('PLG_XMLRPC_JOOMLA_FEATURED_DESCRIPTION'), 'string');
		$array['categoryDescription'] = new PhpXmlRpc\Value('PLG_XMLRPC_JOOMLA_FEATURED_DESCRIPTION', 'string');
		$array['categoryName']        = new PhpXmlRpc\Value($this->helper->buildCategoryTitle(JText::_('PLG_XMLRPC_JOOMLA_FEATURED_TITLE'),
			0, true), 'string');
//		$array['categoryName'] = new PhpXmlRpc\Value( JText::_('PLG_XMLRPC_JOOMLA_FEATURED_TITLE'), 'string' );
		$array['htmlUrl'] = new PhpXmlRpc\Value(JUri::root(), 'string');
		$array['rssUrl']  = new PhpXmlRpc\Value(JUri::root() . '/index.php?format=feed', 'string');
		$structarray[]    = new PhpXmlRpc\Value($array, 'struct');

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

			$array = array();

			if (!isset($row->description))
			{
				$row->description = '';
			}
			$array['categoryId']          = new PhpXmlRpc\Value($row->id, 'string');
			$array['parentId']            = new PhpXmlRpc\Value($row->parent_id, 'string');
			$array['description']         = new PhpXmlRpc\Value($row->description, 'string');
			$array['categoryDescription'] = new PhpXmlRpc\Value($row->description, 'string');
//			$array['categoryName'] = new PhpXmlRpc\Value( $row->title, 'string' );
			$array['categoryName'] = new PhpXmlRpc\Value($this->helper->buildCategoryTitle($row->title, $row->id), 'string');
			$array['htmlUrl']      = new PhpXmlRpc\Value(JRoute::_(ContentHelperRoute::getCategoryRoute($row->id)), 'string');
			$array['rssUrl']       = new PhpXmlRpc\Value(JRoute::_(ContentHelperRoute::getCategoryRoute($row->id) . '&format=feed'),
				'string');

			$structarray[] = new PhpXmlRpc\Value($array, 'struct');
		}

		return new PhpXmlRpc\Response(new PhpXmlRpc\Value($structarray, 'array'));
	}

	public function getAuthors()
	{
		$args = func_get_args();

		if (func_num_args() != 3)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
		}

		$username = $args[1];
		$password = $args[2];

		$user = $this->helper->authenticateUser($username, $password);

		if (!$user)
		{
			return new PhpXmlRpc\Response(0, self::USER_CODE + 1, JText::_('PLG_XMLRPC_JOOMLA_LOGIN_WAS_NOT_ABLE'));
		}

		//Check permission
		if (!$user->authorise('com_xmlrpc', 'core.edit'))
		{
			return new PhpXmlRpc\Response(new PhpXmlRpc\Value(array(), 'array'));
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('id');
		$query->from('#__usergroups');
		$query->order('id');
		$db->setQuery($query);
		$groups = $db->loadColumn();

		$gids = array();
		foreach ($groups as $gid)
		{
			if (JAccess::checkGroup($gid, 'core.edit', 'com_content') || JAccess::checkGroup($gid, 'core.admin'))
			{
				$gids[] = $gid;
			}
		}

		if (count($gids) < 1)
		{
			return new PhpXmlRpc\Response(new PhpXmlRpc\Value(array(), 'array'));
		}

		$query = $db->getQuery(true);

		$query->select('a.id, a.name, a.username');
		$query->from('#__users AS a');
		$query->innerJoin('#__user_usergroup_map AS b ON b.user_id = a.id');
		$query->innerJoin('#__usergroups AS c ON c.id = b.group_id');
		$query->where('c.id IN(' . implode(', ', $gids) . ')');

		$db->setQuery($query);

		$users = $db->loadObjectList();
		if (count($users) < 1)
		{
			return new PhpXmlRpc\Response(new PhpXmlRpc\Value(array(), 'array'));
		}

		$structs = array();
		$array   = array();
		foreach ($users as $u)
		{
			//Own
			if ($user->id == $u->id)
			{
				continue;
			}

			$array['user_id']      = new PhpXmlRpc\Value($u->id, 'string');
			$array['user_login']   = new PhpXmlRpc\Value(0, 'string');//ignore
			$array['display_name'] = new PhpXmlRpc\Value($u->name, 'string');
			$array['meta_value']   = new PhpXmlRpc\Value('', 'string');
			$structs[]             = new PhpXmlRpc\Value($array, 'struct');
		}

		return new PhpXmlRpc\Response(new PhpXmlRpc\Value($structs, 'array'));
	}

	private function response($msg)
	{
		global $xmlrpcerruser;

		return new PhpXmlRpc\Response(0, $xmlrpcerruser + 1, $msg);
	}

}