<?php
/**
 * @package     Joomla Extensions Live Updater Plugin
 * @version     2.0
 * @company   	WEB EXPERT SERVICES LTD
 * @developer   Stergios Zgouletas <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2007-2017 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die ;
class plgSystemJextupdaterInstallerScript
{
	function postflight( $type, $parent )
	{
		if($type == 'install') 
		{       
			$db = \Joomla\CMS\Factory::getDbo();
			$query = $db->getQuery(true);
			$fields = array($db->quoteName('enabled').' = 1');

			$conditions = array(
				$db->quoteName('element') . ' = ' . $db->quote('jextupdater'), 
				$db->quoteName('type') . ' = ' . $db->quote('plugin')
			);

			$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
			$db->setQuery($query);   
			version_compare(JVERSION,'3.0','ge')? $db->execute() : $db->query();    
		}
	}
}