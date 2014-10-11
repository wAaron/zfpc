<?php
	/**
	 * Users db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.2.3
	 */
	class Default_Model_DbTable_Users extends D_Db_Table_Abstract
	{
		protected $_name = 'users';

		protected $_rowClass = 'Default_Model_DbRow_User';

		/**
		 * Returns a list for admin side.
		 * @param array $params - filter parameters.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getAdminList( $params )
		{
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'u' => $this->info( 'name' ) ),
					array ( '*' )
				)
				->joinLeft(
					array ( 'plt' => Table::_( 'platforms' )->info( 'name' ) ),
					'plt.name = u.platform',
					array ( 'platform_id' => 'id', 'platform_name' => 'name', 'platform_title' => 'title' )
				)
				->joinLeft(
					array ( 's' => Table::_( 'shops' )->info( 'name' ) ),
					's.id = u.shop_id',
					array ( 'shop_id' => 'id', 'shop' => 'name', 'email' )
				)
				->order( 'u.id DESC' )
				;
			// Filter by platform.
			if ( isset ( $params['platform'] ) && !empty ( $params['platform'] ) ) {
				$select->having( 'platform_id = ?', $params['platform'] );
			}
			// Filter by user.
			if ( isset ( $params['user'] ) && !empty ( $params['user'] ) ) {
				$select->where( 'u.name LIKE ?', '%'. $params['user'] .'%' );
			}
			// Filter by shop.
			if ( isset ( $params['shop'] ) && !empty ( $params['shop'] ) ) {
				$select->where( 's.name LIKE ?', '%'. $params['shop'] .'%' );
			}
			// Filter by email.
			if ( isset ( $params['email'] ) && !empty ( $params['email'] ) ) {
				$select->where( 's.email LIKE ?', '%'. $params['email'] .'%' );
			}
			// Fetch.
			return $this->fetchAll( $select );
		}

		/**
		 * Returns an user is assigned to a shop.
		 * @param integer $shopId - shop id.
		 * @return Zend_Db_Table_Row
		 */
		public function getUserByShop( $shopId )
		{
			return $this->fetchRow(
				$this->select()
					->where( 'shop_id = ?', $shopId )
					->limit( 1 )
			);
		}

		/**
		 * Returns an user for platform.
		 * @param string $name - username.
		 * @param string $platform - platform.
		 * @return Zend_Db_Table_Row
		 */
		public function getUserForPlatform( $name, $platform )
		{
			return $this->fetchRow(
				$this->select()
					->where( 'name = ?', $name )
					->where( 'platform = ?', $platform )
					->limit( 1 )
			);
		}

		/**
		 * Returns user for Mailchimp import.
		 *
		 * @todo remove platform table after db rebuilding.
		 * @param integer $platformId - platform id.
		 * @param string $newsType - news type.
		 * @param integer $pluginId - plugin id.
		 * @param bool $all - all users or with enabled subscription.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getUsersForMailchimp( $platformId, $newsType, $pluginId, $all = false )
		{
			$tableUserSettings = new Default_Model_DbTable_UsersSettings();
			$tableInstalledPlugins = new Default_Model_DbTable_InstalledPlugins();
			$tablePlatforms = new Default_Model_DbTable_Platforms();
			$tableShops = new Default_Model_DbTable_Shops();
			$select = $this->select()->distinct()
				->setIntegrityCheck( false )
				->from(
					array ( 'u' => $this->info( 'name' ) ),
					array ( 'id', 'shop_id', 'name' )
				)
				->join(
					array ( 'p' => $tablePlatforms->info( 'name' ) ),
					'p.name = u.platform',
					array ( 'platform_id' => 'id' )
				)
				->join(
					array ( 'us' => $tableUserSettings->info( 'name' ) ),
					'us.user_id = u.id',
					array ( 'external_id',
						'setting_name' => 'name',
						'setting_value' => 'value'
					)
				)
				->join(
					array ( 's' => $tableShops->info( 'name' ) ),
					's.id = u.shop_id',
					array ( 'email' )
				)
				->having( 'platform_id = ?', $platformId )
				;
			if ( $pluginId ) {
				$select->where( 'external_id = ?', $pluginId );
			}
			if ( $newsType ) {
				$select->having( 'setting_name = ?', $newsType );
			}
			if ( !$all ) {
				$select->having( 'setting_value = 1' );
			}
			$users = $this->fetchAll( $select );
			// Remove users with no plugins.
			$data = array ();
			foreach ( $users as $user ) {
				$select = $tableInstalledPlugins->select()
					->where( 'shop_id = ?', $user->shop_id )
					->where( 'state = \'installed\'' )
					;
				if ( $pluginId ) {
					$select->where( 'plugin_id = ?', $pluginId );
				}
				if ( $tableInstalledPlugins->fetchRow( $select ) ) {
					$data[] = $user->toArray();
				}
			}
			$rowset = new Zend_Db_Table_Rowset( array (
				'rowClass' => 'Default_Model_DbRow_User',
				'data' => $data
			) );
			return $rowset;
		}
	}
