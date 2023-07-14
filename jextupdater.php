<?php
/**
 * @package     Extensions Live Updater Plugin for Joomla
 * @version     2.0
 * @company   	WEB EXPERT SERVICES LTD
 * @developer   Stergios Zgouletas <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2007-2023 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
 
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Plugin\CMSPlugin as JPlugin;
use Joomla\String\StringHelper as JString;
use Joomla\CMS\Uri\Uri as JURI;
use Joomla\CMS\Filesystem\Folder as JFolder;
use Joomla\CMS\Filesystem\File as JFile;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\CMS\Component\ComponentHelper as JComponentHelper;
use Joomla\CMS\Module\ModuleHelper as JModuleHelper;
use Joomla\Registry\Registry as JRegistry;

class plgSystemJExtUpdater extends JPlugin
{
	protected $app; 

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		if(!$this->app) $this->app=JFactory::getApplication();
	}
	
	public function onInstallerBeforePackageDownload(&$url, &$headers)
    {
		//Performance Check
		if(strpos($url,'=JExtUpdater')===false) return true;
		
		//Security Reasons		
		$allowedKeyNames=array('dlid','key','licensekey','downloadkey','downloadid','download');
		$query=parse_url($url, PHP_URL_QUERY);
		parse_str($query,$params);
		
		//Check Params if are compatible with current live update system
		if(empty($params) || !array_key_exists('extname',$params) || !array_key_exists('type',$params) || empty($params['extname']) || empty($params['type']))
		{
			return true;
		}
		
		if(!isset($params['license']) || empty($params['license']))
		{
			$params['license']='free';
		}
		
		if(!isset($params['keyparam']) || empty($params['keyparam']) || !in_array(strtolower($params['keyparam']),$allowedKeyNames))
		{
			$params['keyparam']=reset($allowedKeyNames);
		}
		
		$ddlKey='dlid';
		if(isset($params['ddlkey']) && !empty($params['ddlkey']))
		{
			$ddlKey=JString::trim(strip_tags($params['ddlkey']));
		}
		
		if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
		$extensionClassName=ucfirst(strtolower($params['extname']));
		$params['domain']=rtrim(str_replace(array('https:','http:','www.'),'',JURI::root()),'/');
		$params['serverip']=$_SERVER['SERVER_ADDR'];
		$params['php']=PHP_VERSION;
		$params['ioncube']=function_exists('ioncube_loader_version')?ioncube_loader_version():'';
		$params['joomla']=JVERSION;
		$params[$ddlKey]=null; //reset if exists
		
		if($params['type']=='module')
		{
			$module = JModuleHelper::getModule('mod_'.$params['extname']);
			$extensionParams = class_exists('JRegistry')? new JRegistry($module->params) : new JParameter($module->params);
			
			if(JFile::exists(JPATH_ROOT.DS.'modules'.DS.'mod_'.$params['extname'].DS.'helper.php'))
			{
				$class='mod'.$extensionClassName.'Helper';
				require_once(JPATH_ROOT.DS.'modules'.DS.'mod_'.$params['extname'].DS.'helper.php');
				if(method_exists($class,'onUpdateBeforePackageDownload'))
				{
					$result=$class::onUpdateBeforePackageDownload($extensionParams,$params,$url,$headers);
					if(is_bool($result)) return $result;
				}
			}
		}
		elseif($params['type']=='plugin')
		{
			$plugin = JPluginHelper::getPlugin($params['plgtype'],$params['extname']);
			$extensionParams = class_exists('JRegistry')? new JRegistry($plugin->params) : new JParameter($plugin->params);
			$keyParameter=$extensionParams->get($params['keyparam'],'');
			//VM extensions are tricky
			if(empty($keyParameter) && ($params['plgtype']=='vmpayment' || $params['plgtype']=='vmshipment'))
			{
				$db = JFactory::getDBO();
				$type=substr($params['plgtype'],2);
				$paramKey=$type.'_params';
				$db->setQuery('SELECT '.$db->quoteName($paramKey).' FROM '.$db->quoteName('#__virtuemart_'.$type.'methods').' WHERE '.$db->quoteName($type.'_element').'='.$db->quote($params['extname']).' ORDER BY `published`  DESC LIMIT 10;');
				$vmPluginDataList=$db->loadObjectList();
				
				foreach($vmPluginDataList as $vmPlugin)
				{
					$vmPluginData=$vmPlugin->$paramKey;
					if(!empty($vmPluginData))
					{
						$vmparams=array();
						foreach(explode('|',$vmPluginData) as $ps)
						{
							if(empty($ps)) continue;
							$p=@explode('=',$ps,2);
							$vmparams[$p[0]]=@json_decode($p[1]);
						}
						$vmparams = class_exists('JRegistry')? new JRegistry($vmparams) : new JParameter($vmparams);
						if(!empty($vmparams->get($params['keyparam'],'')))
						{
							$extensionParams->merge($vmparams);
							break;
						}
					}
				}
				
				if(JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php'))
				{
					if(!class_exists ('VmConfig')) require(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
					$config = VmConfig::loadConfig();
				}
				if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			}
			
			JPluginHelper::importPlugin($params['plgtype'],$params['extname']);
			$result=$this->trigger('onUpdateBeforePackageDownload', array($extensionParams,$params,$url,$headers));
			if(is_bool($result)) return $result;
		}
		elseif($params['type']=='component')
		{
			$extensionParams = JComponentHelper::getParams('com_'.$params['extname']);
			if(JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_'.$params['extname'].DS.'helpers'.DS.'update.php'))
			{
				$class='com'.$extensionClassName.'Update';
				require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_'.$params['extname'].DS.'helpers'.DS.'update.php');
				if(method_exists($class,'onUpdateBeforePackageDownload'))
				{
					$result=$class::onUpdateBeforePackageDownload($extensionParams,$params,$url,$headers);
					if(is_bool($result)) return $result;
				}
			}
		}
		else
		{
			return true;
		}
		
		//Key is Only for Paid Extensions
		if($params['license']!='free')
		{
			$params[$ddlKey]=preg_replace("/[^a-zA-Z0-9_-]/",'',$extensionParams->get($params['keyparam'],$params[$ddlKey]));
		}
		$params=array_filter($params); //clean empty values
		$url=str_replace($query,http_build_query($params),$url);
		return true;
    }
	
	function trigger($event,$params=array())
	{
		if(version_compare(JVERSION,'4.0.0','ge'))
		{
			$rsp= $this->app->triggerEvent($event,$params);
		}
		else
		{
			$dispatcher = version_compare(JVERSION,'3.0','ge')?JEventDispatcher::getInstance():JDispatcher::getInstance();
			$rsp= $dispatcher->trigger($event,$params);
		}
		return $rsp;
	}
}