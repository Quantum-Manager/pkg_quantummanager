<?php
/**
 * @package    quantummanager
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design & NorrNext. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://www.norrnext.com
 */


defined('_JEXEC') or die;

use Joomla\Archive\Archive;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

class pkg_QuantummanagerInstallerScript
{
	/**
	 * Minimum PHP version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  0.0.1
	 */
	protected $minimumPhp = '7.4';

	/**
	 * Minimum Joomla version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  0.0.1
	 */
	protected $minimumJoomla = '4.0.0';

	/**
	 * Extensions for php
	 * @var array
	 */
	protected $extensions = [
		'fileinfo',
		'curl',
		'mbstring',
	];

	/**
	 * Method to check compatible.
	 *
	 * @param   string            $type    Type of PostFlight action.
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @return  boolean  Compatible current version or not.
	 *
	 * @throws  Exception
	 *
	 * @since  0.0.1
	 */
	public function preflight($type, $parent)
	{
		if ($type === 'install')
		{
			// Check compatible
			if (!$this->checkCompatible())
			{
				return false;
			}

		}

	}

	/**
	 * Method to check compatible.
	 *
	 * @return  bool True if compatible.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function checkCompatible()
	{
		// Check old Joomla
		if (!class_exists('Joomla\CMS\Version'))
		{
			Factory::getApplication()->enqueueMessage(Text::sprintf('PKG_QUANTUMMANAGER_ERROR_COMPATIBLE_JOOMLA',
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
			if (!extension_loaded($extension))
			{
				$extensionsNotLoaded[] = $extension;
			}
		}

		if (count($extensionsNotLoaded))
		{
			$app->enqueueMessage(Text::sprintf('PKG_QUANTUMMANAGER_ERROR_EXTENSIONS', implode(',', $extensionsNotLoaded)),
				'error');

			return false;
		}

		return true;
	}

}