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

class xmlRpcBlogger
{

    protected $helper = null;
    protected $common = null;
    const USER_CODE = 800;

    /**
     * xmlRpcBlogger constructor.
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

    /**
     * @return mixed
     * @since
     */
    public function newPost()
    {
        $args = func_get_args();

        if (func_num_args() < 6) {
            return $this->response(JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
        }

        $blogId = (int)$args[1];
        $username = strval($args[2]);
        $password = strval($args[3]);
        $content = $args[4];
        $publish = (int)$args[5];

        return $this->common->newPost($blogId, $username, $password, $content, $publish, true);
    }

    public function editPost()
    {
        $args = func_get_args();

        if (func_num_args() < 6) {
            return $this->response(JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
        }

        $postId = (int)$args[1];
        $username = strval($args[2]);
        $password = strval($args[3]);
        $content = $args[4];
        $publish = (int)$args[5];

        return $this->common->editPost($postId, $username, $password, $content, $publish, true);
    }

    public function getRecentPosts()
    {
        $args = func_get_args();

        if (func_num_args() < 5) {
            return $this->response(JText::_('PLG_XMLRPC_JOOMLA_ILLEGAL_REQUEST'));
        }

        $blogId = (int)$args[1];
        $username = strval($args[2]);
        $password = strval($args[3]);
        $numberOfPost = (int)$args[4];

        return $this->common->getRecentPosts($blogId, $username, $password, $numberOfPost);
    }

    public function getRegisteredFunctions()
    {
        return array
        (
            'blogger.getUsersBlogs' => array('function' => array(c, 'getUserBlogs'), 'signature' => null),
            'blogger.getUserInfo' => array('function' => array($this->common, 'getUserInfo'), 'signature' => null),
            'blogger.getRecentPosts' => array('function' => array($this->common, 'getRecentPosts'), 'signature' => null)
        );
    }


    public function response($msg)
    {
        return new PhpXmlRpc\Response(0, self::USER_CODE + 1, $msg);
    }
}