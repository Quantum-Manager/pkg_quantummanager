<?php
/**
 * @package    quantummanager
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design & NorrNext. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://www.norrnext.com
 */


defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\CMS\Installer\Installer;

class pkg_QuantummanagerInstallerScript
{
	/**
	 * Minimum PHP version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  0.0.1
	 */
	protected $minimumPhp = '7.1';

	/**
	 * Minimum Joomla version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  0.0.1
	 */
	protected $minimumJoomla = '3.9.0';

	/**
	 * Extensions for php
	 * @var array
	 */
	protected $extensions = [
		'fileinfo'
	];

	/**
	 * Method to check compatible.
	 *
	 * @param   string            $type    Type of PostFlight action.
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @throws  Exception
	 *
	 * @return  boolean  Compatible current version or not.
	 *
	 * @since  0.0.1
	 */
	public function preflight($type, $parent)
	{
		// Check compatible
		if (!$this->checkCompatible()) return false;

		//Download remotes
		$this->downloadRemotes($parent);
	}


	public function postflight($type, $parent)
	{
		if ($type === 'update')
		{
			$this->update142();
		}
	}


	/**
	 * Method to check compatible.
	 *
	 * @throws  Exception
	 *
	 * @return  bool True if compatible.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function checkCompatible()
	{
		// Check old Joomla
		if (!class_exists('Joomla\CMS\Version'))
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('PKG_RIEX_ERROR_COMPATIBLE_JOOMLA',
				$this->minimumJoomla), 'error');

			return false;
		}

		$app      = Factory::getApplication();
		$jversion = new Version();

		// Check PHP
		if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
		{
			$app->enqueueMessage(Text::sprintf('PKG_QUANTUMMANAGER_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
				'error');

			return false;
		}

		// Check joomla version
		if (!$jversion->isCompatible($this->minimumJoomla))
		{
			$app->enqueueMessage(Text::sprintf('PKG_QUANTUMMANAGER_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
				'error');

			return false;
		}

		//Check extension
		$extensionsNotLoaded = [];
		foreach ($this->extensions as $extension)
		{
			if(!extension_loaded($extension))
			{
				$extensionsNotLoaded[] = $extension;
			}
		}

		if(count($extensionsNotLoaded))
		{
			$app->enqueueMessage(Text::sprintf('PKG_QUANTUMMANAGER_ERROR_EXTENSIONS', implode(',', $extensionsNotLoaded)),
				'error');
			return false;
		}

		return true;
	}

	/**
	 * Method to download remotes.
	 *
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function downloadRemotes($parent)
	{
		$attributes = $parent->getParent()->manifest->xpath('files');
		$source     = $parent->getParent()->getPath('source');

		if (!is_array($attributes) || empty($attributes[0])) return;
		foreach ($attributes[0] as $type => $value)
		{
			if (!empty($value->attributes()->download) && !empty($value[0]))
			{
				$src  = htmlspecialchars_decode((string) $value->attributes()->download);
				$dest = $source . '/' . $value[0];
				@file_put_contents($dest, file_get_contents($src));
			}
		}
	}

	protected function update142()
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true)
			->select($db->quoteName(['extension_id']))
			->from('#__extensions')
			->where('element =' . $db->quote('quantummanagercontent'))
			->where('folder =' . $db->quote('editors-xtd'));
		$extension = $db->setQuery($query)->loadObject();

		if(isset($extension->extension_id) && ((int)$extension->extension_id > 0))
		{
			$installer = Installer::getInstance();
			$installer->uninstall('plugin', (int)$extension->extension_id);
		}

		$db = Factory::getDBO();
		$query = $db->getQuery(true)
			->select($db->quoteName(['extension_id']))
			->from('#__extensions')
			->where('element =' . $db->quote('quantummanagercommedia'))
			->where('folder =' . $db->quote('system'));
		$extension = $db->setQuery($query)->loadObject();

		if(isset($extension->extension_id) && ((int)$extension->extension_id > 0))
		{
			$installer = Installer::getInstance();
			$installer->uninstall('plugin', (int)$extension->extension_id);
		}

	}

}