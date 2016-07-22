<?php
/**
 *            XMLRPC
 * @version        2.0.6
 * @package        XMLRPC for Joomla!
 * @copyright        Copyright (C) 2007 - 2012 Yoshiki Kozaki All rights reserved.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * @author        Yoshiki Kozaki  info@joomler.net
 * @link            http://www.joomler.net/
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT_SITE . '/helpers/route.php';

$controller = JControllerLegacy::getInstance('xmlrpc');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
