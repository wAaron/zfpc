<?php
	/**
	 * Payment charges db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.7.4
	 */
	class Payment_Model_DbTable_Charges extends D_Db_Table_Abstract
	{
		protected $_name = 'payment_charges';

		protected $_rowClass = 'Payment_Model_DbRow_Charge';

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
					array ( 'c' => $this->info( 'name' ) ),
					array ( '*', 'shop' => 's.name', 's.email', 'user' => 'u.name', 'plugin' => 'p.name' )
				)
				->joinLeft(
					array ( 'p' => Table::_( 'plugins' )->info( 'name' ) ),
					'p.id = c.plugin_id',
					array ()
				)
				->joinLeft(
					array ( 's' => Table::_( 'shops' )->info( 'name' ) ),
					's.id = c.shop_id',
					array ()
				)
				->joinLeft(
					array ( 'u' => Table::_( 'users' )->info( 'name' ) ),
					'u.shop_id = c.shop_id',
					array ()
				)
				->order( 'id DESC' )
				;
			// Filter by start date.
			if ( isset ( $params['start_date'] ) && !empty ( $params['start_date'] ) ) {
				$date = new DateTime( $params['start_date'] );
				$select->where( 'date > ?', $date->format( 'Y-m-d' ) );
			}
			// Filter by end date.
			if ( isset ( $params['end_date'] ) && !empty ( $params['end_date'] ) ) {
				$date = new DateTime( $params['end_date'] );
				$select->where( 'date < ?', $date->format( 'Y-m-d' ) );
			}
			// Filter by plugin.
			if ( isset ( $params['plugin'] ) && !empty ( $params['plugin'] ) ) {
				$select->where( 'plugin_id = ?', $params['plugin'] );
			}
			// Filter by user.
			if ( isset ( $params['user'] ) && !empty ( $params['user'] ) ) {
				$select->having( 'user LIKE ?', '%'. $params['user'] .'%' );
			}
			// Fetch.
			return $this->fetchAll( $select );
		}

		/**
		 * Returns total earned amount for a given platform.
		 * @param integer $platformId - platform id.
		 * @return integer | Zend_Db_Table_Rowset
		 */
		public function totalPlatformEarned( $platformId = null )
		{
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'c' => $this->info( 'name' ) ),
					array (
						'plugin_id', 'totalEarned' => new Zend_Db_Expr( 'ROUND( SUM( cb.amount ), 2 )' )
					)
				)
				->join(
					array ( 'cb' => Table::_( 'chargesBills' )->info( 'name' ) ),
					'cb.charge_id = c.charge_id',
					array ()
				)
				->join(
					array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					's.shop_id = c.shop_id AND s.plugin_id = c.plugin_id AND name = \'exclude from stat\'',
					array ( 'value' )
				)
				->join(
					array ( 'plug' => Table::_( 'plugins' )->info( 'name' ) ),
					'c.plugin_id = plug.id',
					array ()
				)
				->join(
					array ( 'plat' => Table::_( 'platforms' )->info( 'name' ) ),
					'plug.platform = plat.name',
					array ( 'id', 'title' )
				)
				->where( 'value = 0' )
				->group( 'plat.id' )
				;
			if ( $platformId ) {
				$select->where( 'plat.id = ?', $platformId );
				$row = $this->fetchRow( $select );
				return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
			} else {
				return $this->fetchAll( $select );
			}
		}

		/**
		 * Returns total earned amount for a given plugin.
		 * @param integer $pluginId - plugin id.
		 * @return integer | Zend_Db_Table_Rowset
		 */
		public function totalPluginEarned( $pluginId = null )
		{
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'c' => $this->info( 'name' ) ),
					array (
						'plugin_id', 'totalEarned' => new Zend_Db_Expr( 'ROUND( SUM( cb.amount ), 2 )' )
					)
				)
				->join(
					array ( 'cb' => Table::_( 'chargesBills' )->info( 'name' ) ),
					'cb.charge_id = c.charge_id',
					array ()
				)
				->join(
					array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					's.shop_id = c.shop_id AND s.plugin_id = c.plugin_id AND name = \'exclude from stat\'',
					array ( 'value' )
				)
				->join(
					array ( 'plug' => Table::_( 'plugins' )->info( 'name' ) ),
					'c.plugin_id = plug.id',
					array ( 'name' )
				)
				->join(
					array ( 'plat' => Table::_( 'platforms' )->info( 'name' ) ),
					'plug.platform = plat.name',
					array ( 'platform' => 'title' )
				)
				->where( 'value = 0' )
				->group( 'c.plugin_id' )
				;
			if ( $pluginId ) {
				$select->where( 'c.plugin_id = ?', $pluginId );
				$row = $this->fetchRow( $select );
				return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
			} else {
				return $this->fetchAll( $select );
			}
		}

		/**
		 * Returns revenue for a given plugin for previous month.
		 * @param integer $pluginId - plugin id.
		 * @return integer
		 */
		public function previousMonthRevenue( $pluginId )
		{
			$tableSettings = new Payment_Model_DbTable_Settings();
			$this->getAdapter()
				->query( 'SET @period = DATE_FORMAT( CURDATE() - INTERVAL 1 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$charges = $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'c' => $this->info( 'name' ) ),
						array (
							'charge_id', 'cb.amount',
							'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( cb.date, \'%Y%c\' )' )
						)
					)
					->join(
						array ( 'cb' => Table::_( 'chargesBills' )->info( 'name' ) ),
						'cb.charge_id = c.charge_id',
						array ()
					)
					->join(
						array ( 's' => $tableSettings->info( 'name' ) ),
						's.shop_id = c.shop_id AND s.plugin_id = c.plugin_id AND name = \'exclude from stat\'',
						array ( 'value' )
					)
					->where( 's.value = 0' )
					->where( 'c.plugin_id = ?', $pluginId )
					->having( 'trans_period = @period' )
			);
			return $this->_calcTotalAmount( $charges );
		}

		/**
		 * Returns total earned amount for a given plugin for current month.
		 * @param integer $pluginId - plugin id.
		 * @return integer
		 */
		public function currentMonthEarned( $pluginId )
		{
			$tableSettings = new Payment_Model_DbTable_Settings();
			$this->getAdapter()
				->query( 'SET @period = DATE_FORMAT( CURDATE(), \'%Y%c\' )' )
				->execute()
				;
			$charges = $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'c' => $this->info( 'name' ) ),
						array (
							'charge_id', 'cb.amount',
							'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( cb.date, \'%Y%c\' )' )
						)
					)
					->join(
						array ( 'cb' => Table::_( 'chargesBills' )->info( 'name' ) ),
						'cb.charge_id = c.charge_id',
						array ()
					)
					->join(
						array ( 's' => $tableSettings->info( 'name' ) ),
						's.shop_id = c.shop_id AND s.plugin_id = c.plugin_id AND name = \'exclude from stat\'',
						array ( 'value' )
					)
					->where( 's.value = 0' )
					->where( 'c.plugin_id = ?', $pluginId )
					->having( 'trans_period = @period' )
			);
			return $this->_calcTotalAmount( $charges );
		}

		/**
		 * Returns total amount for given charges.
		 * @param Zend_Db_Table_Rowset $charges - charges.
		 * @return integer
		 */
		private function _calcTotalAmount( $charges )
		{
			$totalEarned = 0;
			if ( count( $charges ) ) {
				foreach ( $charges as $charge ) {
					$totalEarned += $charge->amount;
				}
			}
			return $totalEarned;
		}

		/**
		 * Returns plugin's instances which already have a charge in current month.
		 * @param integer $pluginId
		 * @return Zend_Db_Table_Rowset
		 */
		public function currentMonthEarnedInstances( $pluginId )
		{
			$this->getAdapter()
				->query( 'SET @period = DATE_FORMAT( CURDATE(), \'%Y%c\' )' )
				->execute()
				;
			return $this->fetchAll(
				$this->select()
					->from( $this->info( 'name' ), array (
						'charge_id', 'shop_id', 'plugin_id',
						'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
					) )
					->where( 'plugin_id = ?', $pluginId )
					->having( 'trans_period = @period' )
			);
		}

		/**
		 * Returns a last payment for an instance.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Row
		 */
		public function lastPayment( $shopId, $pluginId )
		{
			return $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'c' => $this->info( 'name' ) ),
						array ()
					)
					->join(
						array ( 'cb' => Table::_( 'chargesBills' )->info( 'name' ) ),
						'cb.charge_id = c.charge_id',
						array ( 'amount', 'date' )
					)
					->where( 'c.shop_id = ?', $shopId )
					->where( 'c.plugin_id = ?', $pluginId )
					->order( 'cb.id DESC' )
					->limit( 1 )
			);
		}

		/**
		 * Returns total payment amount of an instance for the all time.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - shop id.
		 * @return integer
		 */
		public function totalPaymentAmount( $shopId, $pluginId = null )
		{
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'c' => $this->info( 'name' ) ),
					array ( '*', 'totalEarned' => new Zend_Db_Expr( 'SUM( cb.amount )' )	)
				)
				->join(
					array ( 'cb' => Table::_( 'chargesBills' )->info( 'name' ) ),
					'cb.charge_id = c.charge_id',
					array ()
				)
				->where( 'shop_id = ?', $shopId )
				->group( 'shop_id' )
				;
			if ( $pluginId ) {
				$select->where( 'plugin_id = ?', $pluginId );
			}
			$row = $this->fetchRow( $select );
			return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
		}
	}
