<?php
	/**
	 * Credentials db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.2
	 */
	class Default_Model_DbTable_Credentials extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins_credentials';

		/**
		 * Returns a concrete record.
		 * @param integer $id - record id.
		 * @return Zend_Db_Table_Row
		 */
		public function get( $id )
		{
			$tableShops = new Default_Model_DbTable_Shops();
			$tableUsers = new Default_Model_DbTable_Users();
			return $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array( 'api' => $this->info( 'name' ) ),
						'api.*'
					)
					->join(
						array( 'u' => $tableUsers->info( 'name' ) ),
						'u.id = api.user_id',
						array( 'shop_id' )
					)
					->join(
						array( 's' => $tableShops->info( 'name' ) ),
						's.id = u.shop_id',
						array( 'shop_domain' => 'name' )
					)
					->where( 'api.id = ?', $id )
					->limit( 1 )
			);
		}

		/**
		 * Returns user's api credentials.
		 * @param integer $userId - user id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getForUser( $userId )
		{
			$tablePlugins = new Default_Model_DbTable_Plugins();
			$tableInstalledPlugins = new Default_Model_DbTable_InstalledPlugins();
			$tableUsers = new Default_Model_DbTable_Users();
			$user = $tableUsers->get( $userId );
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array( 'api' => $this->info( 'name' ) ),
						'api.*'
					)
					->join(
						array( 'p' => $tablePlugins->info( 'name' ) ),
						'p.id = api.plugin_id',
						array( 'plugin_name' => 'name', 'plugin_desc' => 'description' )
					)
					->join(
						array( 'ip' => $tableInstalledPlugins->info( 'name' ) ),
						'ip.plugin_id = api.plugin_id',
						array( 'state' )
					)
					->where( 'api.user_id = ?', $userId )
					->where( 'ip.shop_id = ?', $user->shop_id )
					->where( 'ip.state = \'installed\'' )
			);
		}

		/**
		 * Returns plugin's api credentials.
		 * @param integer $plugin - plugin id.
		 * @param integer $userId - user id.
		 * @return Zend_Db_Table_Row
		 */
		public function getForPlugin( $pluginId, $userId )
		{
			$tableShops = new Default_Model_DbTable_Shops();
			$tableUsers = new Default_Model_DbTable_Users();
			return $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array( 'api' => $this->info( 'name' ) ),
						'api.*'
					)
					->join(
						array( 'u' => $tableUsers->info( 'name' ) ),
						'u.id = api.user_id',
						array()
					)
					->join(
						array( 's' => $tableShops->info( 'name' ) ),
						's.id = u.shop_id',
						array( 'shop_domain' => 'name' )
					)
					->where( 'api.plugin_id = ?', $pluginId )
					->where( 'api.user_id = ?', $userId )
					->limit( 1 )
			);
		}

		/**
		 * Returns name of base plan is assigned to current platform plan.
		 * @param integer $pluginId - plugin id.
		 * @param integer $userId - user id.
		 * @return string
		 */
		public function getBasePlan( $pluginId, $userId )
		{
			$tablePlans = new Default_Model_DbTable_Plans();
			$tableBasePlans = new Payment_Model_DbTable_Plans();
			return strtolower( $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array( 'api' => $this->info( 'name' ) ),
						'api.*'
					)
					->join(
						array( 'p' => $tablePlans->info( 'name' ) ),
						'p.id = api.plan_id',
						array( 'payment_plan_id' )
					)
					->join(
						array( 'bp' => $tableBasePlans->info( 'name' ) ),
						'bp.id = p.payment_plan_id',
						array( 'name' )
					)
					->where( 'api.plugin_id = ?', $pluginId )
					->where( 'api.user_id = ?', $userId )
					->limit( 1 )
			)->name );
		}

		/**
		 * Returns shop assigned to an user.
		 * @param integer $userId - user id.
		 * @return Zend_Db_Table_Row
		 */
		public function getUserShop( $userId )
		{
			$tableShops = new Default_Model_DbTable_Shops();
			$tableUsers = new Default_Model_DbTable_Users();
			return $tableShops->fetchRow(
				$tableShops->select()
					->setIntegrityCheck( false )
					->from(
						array( 's' => $tableShops->info( 'name' ) ),
						's.*'
					)
					->join(
						array( 'u' => $tableUsers->info( 'name' ) ),
						'u.shop_id = s.id',
						array( 'username' => 'name' )
					)
					->where( 'u.id = ?', $userId )
					->limit( 1 )
			);
		}

		/**
		 * get api credentials for stores
		 * @return Zend_Db_Table_Rowset_Abstract
		 * @author Kuksanau Ihnat
		 */
		public function getCredentialsForApi( $pluginId, $platform = 'shopify' )
		{
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array( 'c' => $this->info( 'name' ) )
					)
					->join(
						array( 'u' => Table::_( 'users' )->info( 'name' ) ),
						'u.id = c.user_id',
						array( 'shop_id' => 'u.shop_id' )
					)
					->join(
						array( 's' => Table::_( 'shops' )->info( 'name' ) ),
						'u.shop_id = s.id',
						array( 'name' => 's.name' )
					)
					->where( 'c.plugin_id = ?', $pluginId )
					->where( 'c.api_key IS NOT NULL' )
					->where( 'u.platform = ?', $platform )
			);
		}
	}
