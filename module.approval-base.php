<?php
// Copyright (C) 2012 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'approval-base/1.2.0',
	array(
		// Identification
		//
		'label' => 'Approval prerequisites',
		'category' => 'feature',

		// Setup
		//
		'dependencies' => array(
			
		),
		'mandatory' => false,
		'visible' => false,
		'installer' => 'approval_baseInstaller',

		// Components
		//
		'datamodel' => array(
			'model.approval-base.php'
		),
		'webservice' => array(
			
		),
		'data.struct' => array(
			// add your 'structure' definition XML files here,
		),
		'data.sample' => array(
			// add your sample data XML files here,
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
			'email_sender' => 'john.foo@john.business.com',
			'email_reply_to' => 'joao.ningem@john.business.com',
			'comment_attcode' => '',
		),
	)
);

// Module installation handler
//
class approval_baseInstaller extends ModuleInstallerAPI
{
	public static function BeforeWritingConfig(Config $oConfiguration)
	{
		// If you want to override/force some configuration values, do it here
		return $oConfiguration;
	}

	/**
	 * Handler called before creating or upgrading the database schema
	 * @param $oConfiguration Config The new configuration of the application
	 * @param $sPreviousVersion string PRevious version number of the module (empty string in case of first install)
	 * @param $sCurrentVersion string Current version number of the module
	 */
	public static function BeforeDatabaseCreation(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
	{
		// If you want to migrate data from one format to another, do it here
	}
	
	/**
	 * Handler called after the creation/update of the database schema
	 * @param $oConfiguration Config The new configuration of the application
	 * @param $sPreviousVersion string PRevious version number of the module (empty string in case of first install)
	 * @param $sCurrentVersion string Current version number of the module
	 */
	public static function AfterDatabaseCreation(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
	{
		self::CreateIndexIfNotExist();
	}

	public static function AfterDatabaseUpgrade(Config $oConfiguration)
	{
		self::CreateIndexIfNotExist();
	}

	protected static function CreateIndexIfNotExist()
	{
		$sTable = MetaModel::DBGetTable('ApprovalScheme');
		$aIndexDef = CMDBSource::QueryToArray("SHOW INDEX FROM `$sTable` WHERE Key_name = 'object_ref'");
		if (count($aIndexDef) == 0)
		{
			// The index does not exist, let's create it
			CMDBSource::Query("CREATE INDEX `object_ref` ON `$sTable` (obj_class, obj_key)");
		}
	}
}
?>
