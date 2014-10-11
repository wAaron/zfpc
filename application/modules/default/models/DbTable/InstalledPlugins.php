<?php
	/**
	 * Installed Plugins db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.4.8
	 */
	class Default_Model_DbTable_InstalledPlugins extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins_installed';

		protected $_rowClass = 'Default_Model_DbRow_PluginInstance';

		/**
		 * Checks out whether a plugin exists in db or not.
		 * @param integer $shopId - shop id.
		 * @param mixed $plugin - plugin id or name.
		 * @return Zend_Db_Table_Row
		 */
		public function exists( $shopId, $plugin )
		{
			$pluginWhere = is_numeric( $plugin ) ? 'p.id = ?' : 'p.name = ?';
			return $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'ip' => $this->info( 'name' ) ),
						'ip.*'
					)
					->join(
						array ( 'p' => Table::_( 'plugins' )->info( 'name' ) ),
						'p.id = ip.plugin_id',
						array ()
					)
					->where( $pluginWhere, $plugin )
					->where( 'ip.shop_id = ?', $shopId )
					->limit( 1 )
			);
		}

		/**
		 * Returns the list of plugin instances.
		 * @param array $filter - filter by fields.
		 * @param bool $excluded - with excluded instances or not.
		 * @return Zend_Db_Table_Rowset
		 */
		public function instances( $filter = array (), $excluded = true )
		{
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'i' => $this->info( 'name' ) ), '*'
				)
				->join(
					array ( 'u' => Table::_( 'users' )->info( 'name' ) ),
					'u.shop_id = i.shop_id',
					array ( 'user' => 'name' )
				)
				->join(
					array ( 'sh' => Table::_( 'shops' )->info( 'name' ) ),
					'sh.id = i.shop_id',
					array ( 'platform', 'shop' => 'name', 'email' )
				)
				->join(
					array ( 'p' => Table::_( 'plugins' )->info( 'name' ) ),
					'p.id = i.plugin_id',
					array ( 'plugin' => 'name' )
				)
				->order( 'i.id DESC' )
				;
			// Exclude marked from stat.
			if ( !$excluded ) {
				$select->join(
					array ( 'se' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					'( se.shop_id = i.shop_id ) AND ( se.plugin_id = i.plugin_id ) AND ( se.name = \'exclude from stat\' ) AND ( se.value = 0 )',
					array ( 'value' )
				);
			}
			// Filter params.
			if ( isset ( $filter['platform'] ) ) {
				$select->where( 'sh.platform = ?', $filter['platform'] );
			}
			if ( isset ( $filter['plugin_id'] ) ) {
				$select->where( 'i.plugin_id = ?', $filter['plugin_id'] );
			}
			if ( isset ( $filter['state'] ) ) {
				$select->where( 'i.state = ?', $filter['state'] );
			}
			if ( isset ( $filter['period'] ) ) {
				$select->where( 'DATE_FORMAT( paid_till, \'%Y-%m\' ) = ?', $filter['period'] );
			}
			if ( isset ( $filter['shop'] ) && !empty ( $filter['shop'] ) ) {
				$select->where( 'sh.name LIKE ?', '%'. $filter['shop'] .'%' );
			}
			if ( isset ( $filter['email'] ) && !empty ( $filter['email'] ) ) {
				$select->where( 'sh.email LIKE ?', '%'. $filter['email'] .'%' );
			}
			// Fetch.
			return $this->fetchAll( $select );
		}

		/**
		 * Returns first installation date.
		 * @param string
		 */
		public function getFirstInstallDate()
		{
			return $this->fetchRow(
				$this->select()
					->order( 'id ASC' )
					->limit( '1' )
			)->installation_date;
		}

		/**
		 * Returns installation/uninstallation amount by days.
		 * @param string $state - state field value.
		 * @param string $platformName - platform Name.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function installStat( $state, $platformName = null, $pluginId = null )
		{
			$periodField = ( $state == 'installed' ) ? 'i.installation_date' : 'i.deinstallation_date';
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'i' => $this->info( 'name' ) ),
					array ( 'COUNT( i.id ) AS amount, DATE_FORMAT( '. $periodField .', \'%Y-%m-%d\' ) AS period' )
				)
				->join(
					array ( 'p' => Table::_( 'plugins' )->info( 'name' ) ),
					'p.id = i.plugin_id',
					array ( 'platform' )
				)
				->where( 'i.state = ?', $state )
				->group( 'period' )
				;
			if ( $platformName ) {
				$select->where( 'p.platform = ?', $platformName );
			}
			if ( $pluginId ) {
				$select->where( 'i.plugin_id = ?', $pluginId );
			}
			return $this->fetchAll( $select );
		}

		/**
		 * Returns installation/uninstallation amount for period.
		 * @param string $state - state field value.
		 * @param string $period - period key.
		 * @param bool $byPlatform - group by platform.
		 * @param bool $byPlugin - group by plugin.
		 * @return Zend_Db_Table_Row | Zend_Db_Table_Rowset
		 */
		public function installStatForPeriod( $state, $period = null, $byPlatform = false, $byPlugin = false )
		{
			$periodField = ( $state == 'installed' ) ? 'i.installation_date' : 'i.deinstallation_date';
			$periodValue = "DATE_FORMAT( $periodField, '%Y-%m-%d' )";
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'i' => $this->info( 'name' ) ),
					array ( 'COUNT( i.id ) AS amount', $periodField )
				)
				->join(
					array ( 'plug' => Table::_( 'plugins' )->info( 'name' ) ),
					'plug.id = i.plugin_id',
					array ( 'plugin_name' => 'name' )
				)
				->join(
					array ( 'plat' => Table::_( 'platforms' )->info( 'name' ) ),
					'plug.platform = plat.name',
					array ( 'platform' => 'title' )
				)
				->where( 'i.state = ?', $state )
				->group( 'i.state' )
				;
			// Filter by period.
			if ( $period ) {
				switch ( $period ) {
					case 'now':
						$condition = '%s = CURDATE()';
						break;

					case '7d':
						$condition = '%s > ( CURDATE() - INTERVAL 8 DAY ) AND %s < CURDATE()';
						break;

					case '30d':
						$condition = '%s > ( CURDATE() - INTERVAL 31 DAY ) AND %s < CURDATE()';
						break;

					case '3m':
						//$condition = '%s > DATE_FORMAT( CURDATE() - INTERVAL 2 MONTH, \'%Y-%m\' ) AND %s < CURDATE()';
						$condition = '%s > ( CURDATE() - INTERVAL 91 DAY ) AND %s < CURDATE()';
						break;

					case '6m':
						//$condition = '%s > DATE_FORMAT( CURDATE() - INTERVAL 5 MONTH, \'%Y-%m\' ) AND %s < CURDATE()';
						$condition = '%s > ( CURDATE() - INTERVAL 181 DAY ) AND %s < CURDATE()';
						break;

					case '1y':
						//$condition = '%s > DATE_FORMAT( CURDATE() - INTERVAL 11 MONTH, \'%Y-%m\' ) AND %s < CURDATE()';
						$condition = '%s > ( CURDATE() - INTERVAL 366 DAY ) AND %s < CURDATE()';
						break;

					case 'all':
						$condition = '1';
						break;
				}
				$select->where( str_replace( '%s', $periodValue, $condition ) );
			}
			// Filter by platform.
			if ( $byPlatform ) {
				$select->group( 'platform' );
				return $this->fetchAll( $select );
			}
			// Filter by plugin.
			if ( $byPlugin ) {
				$select->group( 'i.plugin_id' );
				return $this->fetchAll( $select );
			}
			// Fetch.
			return $this->fetchRow( $select );
		}
	}
