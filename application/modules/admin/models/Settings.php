<?php
	/**
	 * The model of settings tables.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.2.4
	 */
	class Admin_Model_Settings
	{
		/**
		 * Db adapter.
		 * @var Zend_Db_Adapter_Abstract
		 */
		private $_adapter;

		/**
		 * Settings.
		 * @var Admin_Model_DbTable_Settings
		 */
		private $_tableSettings;

		/**
		 * Bolean values of settings.
		 * @var Admin_Model_DbTable_SettingsBool
		 */
		private $_tableSettingsBoll;

		/**
		 * Timestamp values of settings.
		 * @var Admin_Model_DbTable_SettingsTimestamp
		 */
		private $_tableSettingsTimestamp;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->_adapter = Zend_Registry::get( 'db' );
			$this->_tableSettings = new Admin_Model_DbTable_Settings();
			$this->_tableSettingsBool = new Admin_Model_DbTable_SettingsBool();
			$this->_tableSettingsString = new Admin_Model_DbTable_SettingsString();
			$this->_tableSettingsTimestamp = new Admin_Model_DbTable_SettingsTimestamp();
		}

		/**
		 * Returns main setting, previously having created new one if it didn't exist.
		 * @param string $name - setting name.
		 * @param integer $platformId - plutform id.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Row
		 */
		public function setting( $name, $platformId = null, $pluginId = null )
		{
			$name = $this->_optimizeLongName( $name );
			// Form 'where' part.
			$adapter = Zend_Db_Table::getDefaultAdapter();
			$where = "`name` = " . $adapter->quote( $name, 'string' );
			if ( $platformId && is_numeric( $platformId ) ) {
				$where .= " AND `platform_id` = {$platformId}";
			}
			if ( $pluginId && is_numeric( $pluginId ) ) {
				$where .= " AND `plugin_id` = {$pluginId}";
			}
			// Insert if setting doesn't exists.
			if ( !$setting = $this->_tableSettings->fetchRow( $where ) ) {
				return $this->_tableSettings->insert( array (
					'platform_id' => $platformId,
					'plugin_id' => $pluginId,
					'name' => $name
				) );
			}
			return $setting->id;
		}

		/**
		 * Optimizes long setting name.
		 * @param string $name - setting name.
		 * @return string
		 */
		private function _optimizeLongName( $name )
		{
			if ( strstr( $name, '[' ) ) {
				$extendedName = '';
				$startPos = strpos( $name, '[' );
				$endPos = strrpos( $name, ']' );
				$extendedName = substr( $name, $startPos + 1, $endPos - $startPos - 1 );
				$name = substr_replace( $name, '', $startPos );
				if ( strstr( $name, '.phtml' ) ) {
					$name = str_replace( '.phtml', '', $name );
				}
				$name = str_replace( '__', ' ', $name );
				$name = str_replace( '_', ' ', $name );
				if ( strstr( $name, ' ' ) ) {
					$newName = '';
					foreach ( explode( ' ', $name ) as $word ) {
						$newName .= strtolower( substr( $word, 0, 1 ) );
					}
					$name = $newName;
				}
				if ( $extendedName ) {
					$name .= ' ' . $extendedName;
				}
			}
			return $name;
		}

		/**
		 * Sets bool value.
		 * @param integer $id - setting id.
		 * @param integer | bool $value - setting value.
		 */
		public function bool( $id, $value )
		{
			$id = (integer) $id;
			$where = "`setting_id` = {$id}";
			if ( !$this->_tableSettingsBool->fetchRow( $where ) ) {
				$this->_tableSettingsBool->insert( array (
					'setting_id' => $id,
					'value' => $value
				) );
			} else {
				$this->_tableSettingsBool->update( array (
					'value' => $value
				), $where );
			}
		}

		/**
		 * Sets timestamp value.
		 * @param integer $id - setting id.
		 * @param string $value - setting value.
		 */
		public function timestamp( $id, $value )
		{
			$id = (integer) $id;
			$where = "`setting_id` = {$id}";
			if ( !$this->_tableSettingsTimestamp->fetchRow( $where ) ) {
				$this->_tableSettingsTimestamp->insert( array (
					'setting_id' => $id,
					'value' => $value
				) );
			} else {
				$this->_tableSettingsTimestamp->update( array (
					'value' => $value
				), $where );
			}
		}

		/**
		 * Sets string value.
		 * @param integer $id - setting id.
		 * @param string $value - setting value.
		 */
		public function string( $id, $value )
		{
			$id = (integer) $id;
			$where = "`setting_id` = {$id}";
			if ( !$this->_tableSettingsString->fetchRow( $where ) ) {
				$this->_tableSettingsString->insert( array (
					'setting_id' => $id,
					'value' => $value
				) );
			} else {
				$this->_tableSettingsString->update( array (
					'value' => $value
				), $where );
			}
		}

		/**
		 * Returns bunch of setting with platforms.
		 * @param string $name - setting name.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getBunch( $name )
		{
			return $this->_tableSettings->fetchAll(
				$this->_tableSettings->select()
					->setIntegrityCheck( false )
					->from(
						array ( 's' => $this->_tableSettings->info( 'name' ) ),
						array ( '*' )
					)
					->joinLeft(
						array ( 'p' => Table::_( 'platforms' )->info( 'name' ) ),
						'p.id = s.platform_id',
						array ( 'platform_name' => 'name', 'platform_title' => 'title' )
					)
					->joinLeft(
						array ( 'b' => $this->_tableSettingsBool->info( 'name' ) ),
						'b.setting_id = s.id',
						array ( 'bool_value' => 'value' )
					)
					->where( 's.name = ?', $name )
			);
		}

		/**
		 * Returns concrete setting.
		 * @param string $name - setting name.
		 * @param integer $platformId - platform id.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Row
		 */
		public function getSetting( $name, $platformId = null, $pluginId = null )
		{
			$name = $this->_optimizeLongName( $name );
			$select = $this->_tableSettings->select()
				->where( 'name = ?', $name )
				;
			if ( $platformId ) {
				$select->where( 'platform_id = ?', $platformId );
			}
			else if ( $pluginId ) {
				$select->where( 'plugin_id = ?', $pluginId );
			}
			return $this->_tableSettings->fetchRow( $select );
		}

		/**
		 * Returns concrete setting value.
		 * @param integer $settingId - setting id.
		 * @param string $type - setting type.
		 * @return mixed
		 */
		public function getValue( $settingId, $type )
		{
			$tableValue = '_tableSettings' . ucfirst( $type );
			if ( !property_exists( $this, $tableValue ) ) {
				return null;
			}
			$row = $this->$tableValue->fetchRow(
				$this->$tableValue->select()
					->where( 'setting_id = ?', $settingId )
			);
			return ( $row ? $row->value : null );
		}

		/**
		 * Returns setting value for specific platform.
		 * @param integer $platformId - platform id.
		 * @param string $name - setting name.
		 * @param string $type - setting type.
		 * @return mixed
		 */
		public function getPlatformValue( $platformId, $name, $type )
		{
			$tableValue = '_tableSettings' . ucfirst( $type );
			if ( !property_exists( $this, $tableValue ) ) {
				return null;
			}
			$row = $this->_tableSettings->fetchRow(
				$this->_tableSettings->select()
					->setIntegrityCheck( false )
					->from(
						array ( 's' => $this->_tableSettings->info( 'name' ) ),
						array ( '*' )
					)
					->joinLeft(
						array ( 'v' => $this->$tableValue->info( 'name' ) ),
						"v.setting_id = s.id",
						array ( 'value' )
					)
					->where( 's.platform_id = ?', $platformId )
					->where( 's.name = ?', $name )
			);
			return ( $row ? $row->value : null );
		}
	}
