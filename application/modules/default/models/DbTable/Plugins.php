<?php
	/**
	 * Plugins db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.3.8
	 */
	class Default_Model_DbTable_Plugins extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins';

		/**
		 * Installs a plugin.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 */
		public function install( $shopId, $pluginId )
		{
			if ( !$this->getInstance( $shopId, $pluginId ) ) {
				Table::_( 'instances' )->insert( array (
					'shop_id' => $shopId,
					'plugin_id' => $pluginId
				) );
			}
		}

		/**
		 * Uninstalls a plugin from.
		 * @param integer $shopId - shop id.
		 * @param integer $plugin - plugin id.
		 * @param bool
		 */
		public function uninstall( $shopId, $pluginId )
		{
			$instance = $this->getInstance( $shopId, $pluginId );
			if ( $instance ) {
				$instance->state = 'uninstalled';
				$instance->deinstallation_date = new Zend_Db_Expr( 'NOW()' );
				$instance->save();
			}
		}

		/**
		 * Checks out whether a plugin installed or not.
		 * @param integer $shopId - shop id.
		 * @param mixed $plugin - shop id or name.
		 * @return bool
		 */
		public function isInstalled( $shopId, $plugin )
		{
			$instance = $this->getInstance( $shopId, $plugin );
			if ( $instance && ( $instance->state == 'installed' ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Returns platform plugin.
		 * @param string $name - plugin name.
		 * @param string $platform - platform name.
		 * @return Zend_Db_Table_Row
		 */
		public function getPlugin( $name, $platform )
		{
			return $this->fetchRow(
				$this->select()
					->where( 'name = ?', $name )
					->where( 'platform = ?', $platform )
			);
		}

		/**
		 * Returns a plugin instance.
		 * @param integer $shopId - shop id.
		 * @param mixed $plugin - plugin id or name.
		 * @return Default_Model_DbRow_PluginInstance
		 */
		public function getInstance( $shopId, $plugin ) {
			return Table::_( 'instances' )->exists( $shopId, $plugin );
		}

		/**
		 * Returns a list of installed plugins.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getInstalledPlugins()
		{
			return Table::_( 'instances' )->fetchAll(
				Table::_( 'instances' )->select()
					->from(
						array ( 'ip' => Table::_( 'instances' )->info( 'name' ) ),
						array ( 'ip.*',
							'last_notification_period_1' => new Zend_Db_Expr( 'TIMESTAMPDIFF( HOUR, `last_notification_1`, NOW() )' ),
							'last_notification_period_2' => new Zend_Db_Expr( 'TIMESTAMPDIFF( HOUR, `last_notification_2`, NOW() )' ),
							'last_notification_period_e' => new Zend_Db_Expr( 'TIMESTAMPDIFF( HOUR, `last_notification_e`, NOW() )' )
						)
					)
					->where( 'state = \'installed\'' )
			);
		}

		/**
		 * Returns a list of installed plugins by user.
		 * @param integer $shopId - shop id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getInstalledPluginsByUser( $shopId )
		{
			return Table::_( 'instances' )->fetchAll(
				Table::_( 'instances' )->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'i' => Table::_( 'instances' )->info( 'name' ) ),
						array ( '*' )
					)
					->join(
						array ( 'p' => $this->info( 'name' ) ),
						'p.id = i.plugin_id',
						array ( 'name' )
					)
					->where( 'i.state = \'installed\'' )
					->where( 'i.shop_id = ?', $shopId )
			);
		}

		/**
		 * Returns the list of recently installed plugins.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getRecentlyInstalledPlugins()
		{
			$config = Config::getInstance();
			return Table::_( 'instances' )->fetchAll(
				Table::_( 'instances' )->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'ip' => Table::_( 'instances' )->info( 'name' ) ),
						array ( 'ip.*',
							'days_after' => new Zend_Db_Expr( 'TIMESTAMPDIFF( DAY, `installation_date`, NOW() )' )
						)
					)
					->join(
						array ( 'p' => $this->info( 'name' ) ),
						'p.id = ip.plugin_id',
						array ( 'name', 'platform' )
					)
					->where( 'state = \'installed\'' )
					->having( 'days_after = ' . $config->notification->afterInstall )
			);
		}

		/**
		 * Returns the list of non-installed plugins of a shop.
		 * @param Zend_Db_Table_Row $shop - shop object.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getNotInstalledPlugins( Zend_Db_Table_Row $shop )
		{
			$installedPlugins = Table::_( 'instances' )->select()
				->from(
					array ( 'ip' => Table::_( 'instances' )->info( 'name' ) ),
					'ip.plugin_id'
				)
				->where( 'ip.shop_id = ?', $shop->id )
				->where( 'ip.state = \'installed\'' )
				->assemble()
				;
			return $this->fetchAll(
				$this->select()
					->from(
						array ( 'p' => $this->info( 'name' ) ),
						'p.*'
					)
					->where( "p.id NOT IN ( $installedPlugins )" )
					->where( "p.platform = ?", $shop->platform )
			);
		}

		/**
		 * Returns plugins by platform.
		 * @param string $platform - platform name.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getByPlatform( $platform = null )
		{
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'plg' => $this->info( 'name' ) ),
					'*'
				)
				->joinLeft(
					array ( 'plt' => Table::_( 'platforms' )->info( 'name' ) ),
					'plt.name = plg.platform',
					array ( 'payment' )
				);
			if ( $platform ) {
				$select->where( 'plg.platform = ?', $platform );
			}
			return $this->fetchAll( $select );
		}

		/**
		 * Returns plugin installation date.
		 * @param Zend_Db_Table_Row $shop - shop object.
		 * @param Zend_Db_Table_Row $plugin - plugin object.
		 * @return string
		 */
		public function installationDate( Zend_Db_Table_Row $shop, Zend_Db_Table_Row $plugin )
		{
			$where = "`shop_id` = {$shop->id} AND `plugin_id` = {$plugin->id}";
			return Table::_( 'instances' )->fetchRow( $where )->installation_date;
		}
	}
