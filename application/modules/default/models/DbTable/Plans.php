<?php
	/**
	 * Tariff plans db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.4.8
	 */
	class Default_Model_DbTable_Plans extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins_plans';

		protected $_rowClass = 'Default_Model_DbRow_Plan';

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
					array ( 'p' => $this->info( 'name' ) ),
					array ( '*' )
				)
				->joinLeft(
					array ( 'ps' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					'ps.name = \'current plan\' AND ps.value = p.name',
					array ( 'setting_id' => 'id' )
				)
				->joinLeft(
					array ( 'plg' => Table::_( 'plugins' )->info( 'name' ) ),
					'plg.id = p.plugin_id',
					array ( 'plugin_name' => 'name' )
				)
				->joinLeft(
					array ( 'plt' => Table::_( 'platforms' )->info( 'name' ) ),
					'plt.name = plg.platform',
					array ( 'platform_id' => 'id', 'platform_name' => 'title' )
				)
				->joinLeft(
					array ( 'pp' => Table::_( 'paymentPlans' )->info( 'name' ) ),
					'pp.id = p.payment_plan_id',
					array ( 'payment_plan' => 'name' )
				)
				->group( 'p.id' )
				;
			// Filter by platform.
			if ( isset ( $params['platform'] ) && !empty ( $params['platform'] ) ) {
				$select->having( 'platform_id = ?', $params['platform'] );
			}
			// Filter by plugin.
			if ( isset ( $params['plugin'] ) && !empty ( $params['plugin'] ) ) {
				$select->where( 'p.plugin_id = ?', $params['plugin'] );
			}
			// Fetch.
			return $this->fetchAll( $select );
		}

		/**
		 * Returns a plan.
		 * @param integer $pluginId - plugin id.
		 * @param string $name - plan name.
		 * @return Default_Model_DbRow_Plan
		 */
		public function getPlan( $pluginId, $name )
		{
			return $this->fetchRow( array (
				'plugin_id = ?' => $pluginId,
				'name = ?' => $name
			) );
		}

		/**
		 * Returns plugin plans.
		 * @param integer $pluginId - plugin id.
		 * @param string $currentPlan - instance current plan.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getByPlugin( $pluginId, $currentPlan = false )
		{
			$select = $this->select()
				->where( 'plugin_id = ?', $pluginId );
			if ( is_string( $currentPlan ) ) {
				$select->where( "is_visible = 1 OR name like '%{$currentPlan}%'" );
			} else if ( is_null( $currentPlan ) ) {
				$select->where( "is_visible = 1" );
			}
			return $this->fetchAll( $select );
		}

		/**
		 * Returns prices for tariff plans.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function prices( $pluginId )
		{
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'plan' => $this->info( 'name' ) ),
						'plan.*'
					)
					->joinLeft(
						array ( 'price' => Table::_( 'paymentPrices' )->info( 'name' ) ),
						'price.payment_plan_id = plan.payment_plan_id',
						array ( 'price' )
					)
					->joinLeft(
						array ( 'prod' => Table::_( 'paymentProducts' )->info( 'name' ) ),
						'prod.id = price.product_id',
						array ( 'variety', 'quantity' )
					)
					->where( 'plan.plugin_id = ?', $pluginId )
					->where( 'price.plugin_id = ?', $pluginId )
					->where( 'plan.is_visible = 1' )
					->order( 'plan.id ASC' )
					->order( 'quantity ASC' )
			);
		}
	}
