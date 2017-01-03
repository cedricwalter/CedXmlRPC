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

jimport('joomla.user.authentication');
jimport('joomla.libraries.phputf8.native.strlen');
jimport('joomla.language.text');

//use  \Joomla\String\String;

class xmlRpcFrontendHelper
{
	private $language;
	private $beforeWrapid;
	private $afterWrapid;
	private $readmore;
	private $pagebreak;

	/**
	 * xmlRpcHelper constructor.
	 *
	 * @param $language
	 * @param $beforewrapid
	 * @param $afterwrapid
	 * @param $readmore
	 * @param $pagebreak
	 * @since
	 */
	public function __construct($language, $beforewrapid, $afterwrapid, $readmore, $pagebreak)
	{
		$this->language     = $language;
		$this->beforeWrapid = $beforewrapid;
		$this->afterWrapid  = $afterwrapid;

		$this->readmore  = $readmore;
		$this->pagebreak = $pagebreak;
	}

	public function authenticateUser($username, $password)
	{
		// Get the global JAuthentication object.
		jimport('joomla.user.authentication');

		$authenticate            = JAuthentication::getInstance();
		$credentials             = array();
		$credentials['username'] = strval($username);
		$credentials['password'] = strval($password);

		$options = array('remember' => false);

		$authenticate = $authenticate->authenticate($credentials, $options);

		if ($authenticate->status == JAuthentication::STATUS_FAILURE || empty($authenticate->username) || empty($authenticate->password) || empty($authenticate->email))
		{
			return false;
		}

		$user = JUser::getInstance($authenticate->username);
		//Check Status
		if (empty($user->id) || $user->block || !empty($user->activation))
		{
			return false;
		}

		JFactory::getSession()->set('user', $user);

		return $user;
	}

	public function getCatTitle($id)
	{
		$db = JFactory::getDbo();
		if (!$id)
		{
			return null;
		}
		$query = 'SELECT title'
			. ' FROM #__categories'
			. ' WHERE id = ' . (int) $id;
		$db->setQuery($query);

		return $db->loadResult();
	}

	public function GoogleDocsToContent(&$content)
	{
		if (is_array($content) || (is_string($content) && strpos($content, 'google_header') === false))
		{
			return;
		}

		//Header title
		$headerRegex = '/<div.+?google_header[^>]+>(.+?)<\/div>/is';
		//Old page break;
		$oldpbregex = '/<p.+?page-break-after[^>]+>.*?<\/p>/is';
		//Horizontal line
		$hrizonregex = '/<hr\s+?size="2"[^>]*?>/is';
		//New page break;
		$newpbregex = '/<hr\s+?class="pb"[^>]*?>/is';

		$match = array();
		if (preg_match($headerRegex, $content, $match))
		{
			$title        = trim($match[1]);
			$introAndFull = preg_replace($headerRegex, '', $content);
		}
		else
		{
			$title        = utf8_substr($content, 0, 30);
			$introAndFull = str_replace($title, '', $content);
		}

		$text     = preg_split($oldpbregex, $introAndFull, 2, PREG_SPLIT_NO_EMPTY);
		$fulltext = '';
		if (count($text) > 1)
		{
			$introtext = trim($text[0]);
			$fulltext  = trim($text[1]);
		}
		else
		{

			//new
			if (!$this->readmore)
			{
				//Horizontal line
				$regex = $hrizonregex;
			}
			else
			{
				//Page break
				$regex = $newpbregex;
			}

			//first horizontal line or pagebreak
			$text = preg_split($regex, $introAndFull, 2, PREG_SPLIT_NO_EMPTY);
			if (count($text) > 1)
			{
				$introtext = trim($text[0]);
				$fulltext  = trim($text[1]);
			}
			else
			{
				$introtext = trim($introAndFull);
			}
		}


		if ($this->pagebreak)
		{
			$count = 2;
			//for pagebreak
			$text = preg_split($newpbregex, $introtext, -1, PREG_SPLIT_NO_EMPTY);
			if (count($text) > 1)
			{
				$introtext = '';
				for ($i = 0, $total = count($text); $i < $total; $i++)
				{
					$alt = JText::sprintf('PAGEBREAK', $count);
					$count++;
					$introtext .= $text[$i];
					if ($i < ($total - 1))
					{
						$introtext .= '<hr title="' . $alt . '" alt="' . $alt . '" class="system-pagebreak" />';
					}
				}
			}

			if (!empty($fulltext))
			{
				$text = preg_split($newpbregex, $fulltext, -1, PREG_SPLIT_NO_EMPTY);
				if (count($text) > 1)
				{
					$fulltext = '';
					for ($i = 0, $total = count($text); $i < $total; $i++)
					{
						$alt = JText::sprintf('PAGEBREAK', $count);
						$count++;
						$fulltext .= $text[$i];
						if ($i < ($total - 1))
						{
							$fulltext .= '<hr title="' . $alt . '" alt="' . $alt . '" class="system-pagebreak" />';
						}
					}
				}
			}
		}

		//b to br and escape
		$replace_from = array('<b>', '</b>', '<br>');
		$replace_to   = array('<strong>', '</strong>', '<br />');

		$content                 = array();
		$content['title']        = htmlspecialchars(strip_tags($title), ENT_QUOTES, 'UTF-8');
		$content['description']  = str_replace($replace_from, $replace_to, $introtext);
		$content['mt_text_more'] = str_replace($replace_from, $replace_to, $fulltext);

		return;
	}

	function iso8601_encode($timeT, $utc=0)
	{
		return PhpXmlRpc\Helper\Date::iso8601Encode($timeT, $utc);
	}

	public function buildStruct($row, $mt = false)
	{
		$date = $this->iso8601_encode(strtotime($row->created), 0);

		if ($mt)
		{
			$xmlArray = array(
				'userid'      => new PhpXmlRpc\Value($row->created_by, 'string'),
				'dateCreated' => new PhpXmlRpc\Value($date, 'dateTime.iso8601'),
				'postid'      => new PhpXmlRpc\Value($row->id, 'string'),
				'title'       => new PhpXmlRpc\Value($row->title, 'string'),
			);
		}
		else
		{
			if (!isset($row->category_title))
			{
				$row->category_title = $this->getCatTitle($row->catid);
			}

			$link     = JRoute::_(ContentHelperRoute::getArticleRoute($row->id, $row->catid), false, 2);
			$xmlArray = array(
				'userid'            => new PhpXmlRpc\Value($row->created_by, 'string'),
				'dateCreated'       => new PhpXmlRpc\Value($date, 'dateTime.iso8601'),
				'postid'            => new PhpXmlRpc\Value($row->id, 'string'),
				'description'       => new PhpXmlRpc\Value($row->introtext, 'string'),
				'title'             => new PhpXmlRpc\Value($row->title, 'string'),
				'wp_slug'           => new PhpXmlRpc\Value($row->alias, 'string'),
				'mt_basename'       => new PhpXmlRpc\Value($row->alias, 'string'),
				'categories'        => new PhpXmlRpc\Value(array(new PhpXmlRpc\Value($row->category_title, 'string')), 'array'),
				'link'              => new PhpXmlRpc\Value($link, 'string'),
				'permaLink'         => new PhpXmlRpc\Value($link, 'string'),
				'mt_excerpt'        => new PhpXmlRpc\Value(
					(isset($row->metadesc) ? $row->metadesc : '')
					, 'string'),
				'mt_text_more'      => new PhpXmlRpc\Value(
					(isset($row->fulltext) ? $row->fulltext : '')
					, 'string'),
				'mt_allow_comments' => new PhpXmlRpc\Value('1', 'int'),
				'mt_allow_pings'    => new PhpXmlRpc\Value('0', 'int'),
				'mt_convert_breaks' => new PhpXmlRpc\Value('', 'string'),
				'mt_keywords'       => new PhpXmlRpc\Value(
					(isset($row->metakey) ? $row->metakey : '')
					, 'string')
			);
		}

		$xmlObject = new PhpXmlRpc\Value($xmlArray, 'struct');

		return array(true, $xmlObject);
	}

	public function buildData($content, $publish, $blogger = false)
	{
		if ($blogger)
		{
			$this->GoogleDocsToContent($content);
		}

		if (!isset($content['description']))
		{
			$content['description'] = '';
		}

		$content['articletext'] = $content['description'];
		unset($content['description']);

		//alias
		if (isset($content['mt_basename']) && !empty($content['mt_basename']))
		{
			$content['alias'] = $content['mt_basename'];
			unset($content['mt_basename']);
		}
		else
		{
			if (isset($content['wp_slug']) && !empty($content['wp_slug']))
			{
				$content['alias'] = $content['wp_slug'];
				unset($content['wp_slug']);
			}
		}

		if (!isset($content['mt_text_more']))
		{
			$content['mt_text_more'] = '';
		}

		$content['mt_text_more'] = trim($content['mt_text_more']);

		//TODO
		if (utf8_strlen($content['mt_text_more']) < 1)
		{
			$temp = explode('<!--more-->', $content['articletext']); //for MetaWeblog
			if (count($temp) > 1)
			{
				$content['articletext'] = $temp[0] . '<hr id="system-readmore" />';
				$content['articletext'] .= $temp[1];
			}
		}
		else
		{
			$content['articletext'] .= '<hr id="system-readmore" />';
			$content['articletext'] .= $content['mt_text_more'];
		}

		unset($content['mt_text_more']);

		if (!isset($content['mt_keywords']))
		{
			$content['mt_keywords'] = '';
		}

		$content['metakey'] = $content['mt_keywords'];

		//build tags
		$tags = $this->getAssignedTags($content['metakey']);
		if ($tags)
		{
			$content['metadata']         = array();
			$content['metadata']['tags'] = $tags;
		}

		if (!isset($content['mt_excerpt']))
		{
			$content['mt_excerpt'] = '';
		}

		$content['metadesc'] = $content['mt_excerpt'];

		$content['state'] = 0;

		if ($publish)
		{
			$content['state'] = 1;
		}

		$content['language'] = $this->language;

		//date
		$baseDate = null;
		switch (true)
		{
			case (isset($content['date_created_gmt'])):
				$baseDate = $content['date_created_gmt'];
				break;
			case (isset($content['dateCreated_gmt'])):
				$baseDate = $content['dateCreated_gmt'];
				break;

			case (isset($content['dateCreated'])):
				$baseDate = $content['dateCreated'];
				break;
		}

		if ($baseDate)
		{
			$timezone     = new DateTimeZone(JFactory::getConfig()->get('offset'));
			$now          = new DateTime('now', $timezone);
			$offsetsecond = $timezone->getOffset($now);

			$offset = 0;
			if ($offsetsecond)
			{
				$offset = $offsetsecond / 3600;
			}

			$date = JFactory::getDate(iso8601_decode($baseDate, $offset));
//			$date->setTimeZone(new DateTimeZone(JFactory::getConfig()->get('offset')));
			$content['created'] = $content['publish_up'] = $date->toSql();
		}

		if (empty($content['id']) && empty($content['created']))
		{
			$content['created'] = $content['publish_up'] = JFactory::getDate()->toSql();
		}

		$content['created_by_alias'] = '';
		if (isset($content['wp_author_id']) && $content['wp_author_id'] > 0)
		{
			$author = JFactory::getUser($content['wp_author_id']);
			if ($author)
			{
				$content['created_by_alias'] = $author->get('name');
			}
		}

		return $content;
	}

	public function buildCategoryTitle($title, $id, $featured = false)
	{
		if ($featured)
		{
			return $title;
		}

		$base = '%s' . $this->beforeWrapid . '%s' . $this->afterWrapid;

		return sprintf($base, $title, $id);
	}

	public function getCatId($title)
	{
		if (strpos($title, $this->beforeWrapid) === false)
		{
			return null;
		}

		$title = explode($this->beforeWrapid, $title);

		if (count($title) == 2)
		{
			return intval(str_replace($this->afterWrapid, '', $title[1]));
		}

		return 0;
	}

	public function assignCategory(& $content)
	{
		static $assigned = false;

		if (isset($content['categories']) && count($content['categories']))
		{
			foreach ($content['categories'] as $title)
			{
				$catId = $this->getCatId($title);

				if (is_null($catId))
				{
					$content['featured'] = 1;
					continue;
				}

				if (!$assigned && $catId > 0)
				{
					$content['catid'] = $catId;
					$assigned         = true;
				}
			}
		}
	}

	public function getFeatureStruct($mt = false, $isPrimary = 0)
	{
		if ($mt)
		{
			return new PhpXmlRpc\Value(
				array(
					'categoryName' => new PhpXmlRpc\Value($this->buildCategoryTitle(JText::_('PLG_XMLRPC_JOOMLA_FEATURED_TITLE'),
						0, true), 'string'),
					'categoryId'   => new PhpXmlRpc\Value('-1', 'string'),
					'isPrimary'    => new PhpXmlRpc\Value($isPrimary, 'boolean')
				), 'struct'
			);
		}

		return new PhpXmlRpc\Value(
			array(
				'categoryId'   => new PhpXmlRpc\Value('-1', 'string'),
				'categoryName' => new PhpXmlRpc\Value(
					$this->buildCategoryTitle(JText::_('PLG_XMLRPC_JOOMLA_FEATURED_TITLE'), 0, true)
					, 'string')
			), 'struct'
		);
	}

	/**
	 *  Assign tag_id from keywords
	 *
	 * @param string $text
	 *
	 * @return array|string
	 *
	 * @since
	 */
	public function getAssignedTags($text)
	{
		$result = array();

		$text = trim($text);
		if (empty($text))
		{
			return $result;
		}

		$tags = explode(',', $text);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('id, title');
		$query->from('#__tags');
//		$query->where('published = 1');

		jimport('joomla.filter.input');
		$filter = JFilterInput::getInstance();

		$cleans = array();
		$wheres = array();
		foreach ($tags as $tag)
		{
			$temp = trim($filter->clean($tag));
			if (empty($temp))
			{
				continue;
			}

			$result[] = '#new#' . $temp;
			$cleans[] = $temp;
			$wheres[] = 'title LIKE ' . $db->q($temp);
		}

		if (count($wheres) < 1)
		{
			$db->getQuery(true);

			return $result;
		}

		$query->where(implode(' OR ', $wheres));

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		//all new tags
		if (count($rows) < 1)
		{
			if (count($result))
			{
//				$post = array();
//				$post['metadata'] = array();
//				$post['metadata']['tags'] = $result;
//				JRequest::set($post);
			}

			return $result;
		}

		$result    = array();
		$cleanflip = array_flip($cleans);
		foreach ($rows as $row)
		{
			$result[] = $row->id;
			if (isset($cleanflip[$row->title]))
			{
				unset($cleans[$cleanflip[$row->title]]);
			}
		}

		if (count($cleans))
		{
			foreach ($cleans as $clean)
			{
				$result[] = '#new#' . $clean;
			}
		}

//		$post = array();
//		$post['metadata'] = array();
//		$post['metadata']['tags'] = $result;
//		JRequest::set($post);

		return $result;
	}

	public function getModel($type, $prefix = 'XMLRPCModel', $config = array())
	{
		return JModelLegacy::getInstance($type, $prefix, $config);
	}

	public function response($msg)
	{
		global $xmlrpcerruser;

		return new PhpXmlRpc\Response(0, $xmlrpcerruser + 1, $msg);
	}

	public function writeLog($message)
	{
		if (!JDEBUG)
		{
			return;
		}

		jimport('joomla.log.log');

		static $log = null;

		if (is_null($log))
		{
			$options['text_file'] = 'xmlrpc.info.php';
			$options['format']    = "{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}";
			JLog::addLogger($options, JLog::ALL, array('xmlrpc'));
		}

		if (!is_string($message))
		{
			$message = print_r($message, true);
		}

		JLog::add($message, JLog::INFO, 'xmlrpc');
	}

}