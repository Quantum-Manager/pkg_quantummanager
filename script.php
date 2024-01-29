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

			//Download remotes
			$this->downloadRemotes($parent);
		}

	}


	public function postflight($type, $parent)
	{
		if ($type === 'update' || $type === 'install')
		{
			$msg    = '';
			$result = $this->installLibFields($parent);

			if ($result !== true)
			{
				$msg .= Text::sprintf('PKG_QUANTUMMANAGER_LIBFIELDS_INSTALLATION_ERROR', (string) $result);
			}

			if ($msg)
			{
				Factory::getApplication()->enqueueMessage($msg, 'error');

				return false;
			}

			if ($type === 'update')
			{
				$this->update150();
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

		if (!is_array($attributes) || empty($attributes[0]))
		{
			return;
		}

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

	protected function update150()
	{
		$db        = Factory::getDBO();
		$query     = $db->getQuery(true)
			->select($db->quoteName(['extension_id']))
			->from('#__extensions')
			->where('element =' . $db->quote('quantummanagercontent'))
			->where('folder =' . $db->quote('editors-xtd'));
		$extension = $db->setQuery($query)->loadObject();

		if (isset($extension->extension_id) && ((int) $extension->extension_id > 0))
		{
			$installer = Installer::getInstance();
			$installer->uninstall('plugin', (int) $extension->extension_id);
		}

		$db        = Factory::getDBO();
		$query     = $db->getQuery(true)
			->select($db->quoteName(['extension_id']))
			->from('#__extensions')
			->where('element =' . $db->quote('quantummanagercommedia'))
			->where('folder =' . $db->quote('system'));
		$extension = $db->setQuery($query)->loadObject();

		if (isset($extension->extension_id) && ((int) $extension->extension_id > 0))
		{
			$installer = Installer::getInstance();
			$installer->uninstall('plugin', (int) $extension->extension_id);
		}

	}

	protected function installLibFields($parent)
	{

		$tmp           = Factory::getConfig()->get('tmp_path');
		$libFieldsFile = 'https://github.com/JPathRu/lib_fields/releases/latest/download/lib_fields.zip';
		$tmpFile       = Path::clean($tmp . '/lib_fields.zip');
		$extDir        = Path::clean($tmp . '/' . uniqid('install_'));

		$contents = file_get_contents($libFieldsFile);
		if ($contents === false)
		{
			return Text::sprintf('PKG_QUANTUMMANAGER_LIBFIELDS_IE_FAILED_DOWNLOAD', $libFieldsFile);
		}

		$resultContents = file_put_contents($tmpFile, $contents);
		if ($resultContents == false)
		{
			return Text::sprintf('PKG_QUANTUMMANAGER_LIBFIELDS_IE_FAILED_INSTALLATION', $tmpFile);
		}

		if (!file_exists($tmpFile))
		{
			return Text::sprintf('PKG_QUANTUMMANAGER_LIBFIELDS_IE_NOT_EXISTS', $tmpFile);
		}

		$archive = new Archive(['tmp_path' => $tmp]);
		try
		{
			$archive->extract($tmpFile, $extDir);
		}
		catch (\Exception $e)
		{
			return Text::sprintf('PKG_QUANTUMMANAGER_LIBFIELDS_IE_FAILER_UNZIP', $tmpFile, $extDir, $e->getMesage());
		}

		$installer = new Installer();
		$installer->setPath('source', $extDir);
		if (!$installer->findManifest())
		{
			InstallerHelper::cleanupInstall($tmpFile, $extDir);

			return Text::_('PKG_QUANTUMMANAGER_LIBFIELDS_IE_INCORRECT_MANIFEST');
		}

		if (!$installer->install($extDir))
		{
			InstallerHelper::cleanupInstall($tmpFile, $extDir);

			return Text::_('PKG_QUANTUMMANAGER_LIBFIELDS_IE_INSTALLER_ERROR');
		}

		InstallerHelper::cleanupInstall($tmpFile, $extDir);

		return true;
	}

}