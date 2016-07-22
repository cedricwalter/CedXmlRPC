<?php
/**
 * 			XMLRPC
 * @version		2.0.6
 * @package		XMLRPC for Joomla!
 * @copyright		Copyright (C) 2007 - 2012 Yoshiki Kozaki All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 * @author		Yoshiki Kozaki  info@joomler.net
 * @link 			http://www.joomler.net/
 */

/**
* @package		Joomla
* @copyright		Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL
*/

defined('_JEXEC') or die;

require JPATH_LIBRARIES. '/cedxmlrpc/vendor/autoload.php';

/**
 * XML View class for the XMLRPC component
 *
 * @package		com_xmlrpc
 */
class XMLRPCViewService extends JViewLegacy
{
	public function display($tpl = null)
	{

		$app = JFactory::getApplication();

		$params = JComponentHelper::getParams('com_xmlrpc');

		$plugin = $params->get('plugin', 'joomla');

		JPluginHelper::importPlugin('xmlrpc', strtolower($plugin));
		$allCalls = $app->triggerEvent('onGetWebServices');
		if(count($allCalls) < 1){
			throw new Exception(JText::_('COM_XMLRPC_SERVICE_WAS_NOT_FOUND'), 404);
		}

		$methodsArray = $this->getMethodArrays($allCalls);

		@mb_regex_encoding('UTF-8');
		@mb_internal_encoding('UTF-8');

		require_once (JPATH_SITE.'/components/com_content/helpers/route.php');
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_content/tables');

		$xmlrpc = new PhpXmlRpc\Server($methodsArray, false);
		$xmlrpc->functions_parameters_type = 'phpvals';

		PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'UTF-8';
		$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';

		$xmlrpc->setDebug($params->get('debug', JDEBUG));
		@ini_set( 'display_errors', $params->get('display_errors', 0));

		$data = file_get_contents('php://input');

		if(empty($data)){
			throw new Exception(JText::_('COM_XMLRPC_INVALID_REQUEST'), 403);
		}

		error_log($data);

		$xmlrpc->service($data);
	}

	/**
	 * @param $allCalls
	 * @return array
	 */
	public function getMethodArrays($allCalls)
	{
		$methodsArray = array();

		foreach ($allCalls as $calls) {
			$methodsArray = array_merge($methodsArray, $calls);
		}
		return $methodsArray;
	}
}
