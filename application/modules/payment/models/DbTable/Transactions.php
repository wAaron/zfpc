<?php
	/**
	 * Payment transactions db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.2.5
	 */
	class Payment_Model_DbTable_Transactions extends D_Db_Table_Abstract
	{
		protected $_name = 'payment_transactions';

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
					array ( 't' => $this->info( 'name' ) ),
					array (
						'plugin_id', 'totalEarned' => new Zend_Db_Expr( 'ROUND( SUM( amount ), 2 )' )
					)
				)
				->join(
					array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					's.shop_id = t.shop_id AND s.plugin_id = t.plugin_id AND name = \'exclude from stat\'',
					array ( 'value' )
				)
				->join(
					array ( 'plug' => Table::_( 'plugins' )->info( 'name' ) ),
					't.plugin_id = plug.id',
					array ()
				)
				->join(
					array ( 'plat' => Table::_( 'platforms' )->info( 'name' ) ),
					'plug.platform = plat.name',
					array ( 'id', 'title' )
				)
				->where( 'value = 0' )
				->where( 'invoice_status = \'deposited\'' )
				->where( 'fraud_status = \'pass\'' )
				->where( 'refunded = 0' )
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
					array ( 't' => $this->info( 'name' ) ),
					array (
						'plugin_id', 'totalEarned' => new Zend_Db_Expr( 'ROUND( SUM( amount ), 2 )' )
					)
				)
				->join(
					array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					's.shop_id = t.shop_id AND s.plugin_id = t.plugin_id AND name = \'exclude from stat\'',
					array ( 'value' )
				)
				->join(
					array ( 'plug' => Table::_( 'plugins' )->info( 'name' ) ),
					't.plugin_id = plug.id',
					array ( 'name' )
				)
				->join(
					array ( 'plat' => Table::_( 'platforms' )->info( 'name' ) ),
					'plug.platform = plat.name',
					array ( 'platform' => 'title' )
				)
				->where( 'value = 0' )
				->where( 'invoice_status = \'deposited\'' )
				->where( 'fraud_status = \'pass\'' )
				->where( 'refunded = 0' )
				->group( 't.plugin_id' )
				;
			if ( $pluginId ) {
				$select->where( 't.plugin_id = ?', $pluginId );
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
			$this->getAdapter()
				->query( 'SET @period = DATE_FORMAT( CURDATE() - INTERVAL 1 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$row = $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 't' => $this->info( 'name' ) ),
						array (
							'plugin_id', 'date',
							'totalEarned' => new Zend_Db_Expr( 'SUM( amount )' ),
							'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
						)
					)
					->join(
						array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
						's.shop_id = t.shop_id AND s.plugin_id = t.plugin_id AND name = \'exclude from stat\'',
						array ( 'value' )
					)
					->where( 'value = 0' )
					->where( 't.plugin_id = ?', $pluginId )
					->where( 'invoice_status = \'deposited\'' )
					->where( 'fraud_status = \'pass\'' )
					->where( 'refunded = 0' )
					->group( 'trans_period' )
					->having( 'trans_period = @period' )
			);
			return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
		}

		/**
		 * Returns recurrent transaction amount for a given plugin for previous month.
		 * @todo common nethod ( current | previous )
		 * @param integer $pluginId - plugin id.
		 * @return integer
		 */
		public function previousMonthRecurrentTransactionAmount( $pluginId )
		{
			$this->getAdapter()
				->query( 'SET @period = DATE_FORMAT( CURDATE() - INTERVAL 1 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$row = $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 't' => $this->info( 'name' ) ),
						array (
							'plugin_id', 'date',
							'totalEarned' => new Zend_Db_Expr( 'SUM( amount )' ),
							'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
						)
					)
					->join(
						array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
						's.shop_id = t.shop_id AND s.plugin_id = t.plugin_id AND name = \'exclude from stat\'',
						array ( 'value' )
					)
					->where( 'value = 0' )
					->where( 't.plugin_id = ?', $pluginId )
					->where( 'invoice_status = \'deposited\'' )
					->where( 'fraud_status = \'pass\'' )
					->where( 'recurring = 1' )
					->where( 'product_id > 1' )
					->where( 'refunded = 0' )
					->group( 'trans_period' )
					->having( 'trans_period = @period' )
			);
			return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
		}

		/**
		 * Returns recurrent transaction amount for a given plugin for current month.
		 * @todo common nethod ( current | previous )
		 * @param integer $pluginId - plugin id.
		 * @return integer
		 */
		public function currentMonthRecurrentTransactionAmount( $pluginId )
		{
			$this->getAdapter()
				->query( 'SET @period = DATE_FORMAT( CURDATE(), \'%Y%c\' )' )
				->execute()
				;
			$row = $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 't' => $this->info( 'name' ) ),
						array (
							'plugin_id', 'date',
							'totalEarned' => new Zend_Db_Expr( 'SUM( amount )' ),
							'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
						)
					)
					->where( 't.plugin_id = ?', $pluginId )
					->where( 'invoice_status = \'deposited\'' )
					->where( 'fraud_status = \'pass\'' )
					->where( 'recurring = 1' )
					->where( 'product_id > 1' )
					->where( 'refunded = 0' )
					->group( 'trans_period' )
					->having( 'trans_period = @period' )
			);
			return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
		}

		/**
		 * Returns total earned amount for a given plugin for current month.
		 * @param integer $pluginId - plugin id.
		 * @return integer
		 */
		public function currentMonthEarned( $pluginId )
		{
			$this->getAdapter()
				->query( 'SET @period = DATE_FORMAT( CURDATE(), \'%Y%c\' )' )
				->execute()
				;
			$row = $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 't' => $this->info( 'name' ) ),
						array (
							'plugin_id', 'date',
							'totalEarned' => new Zend_Db_Expr( 'SUM( amount )' ),
							'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
						)
					)
					->join(
						array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
						's.shop_id = t.shop_id AND s.plugin_id = t.plugin_id AND name = \'exclude from stat\'',
						array ( 'value' )
					)
					->where( 'value = 0' )
					->where( 't.plugin_id = ?', $pluginId )
					->where( 'invoice_status = \'deposited\'' )
					->where( 'fraud_status = \'pass\'' )
					->where( 'refunded = 0' )
					->group( 'trans_period' )
					->having( 'trans_period = @period' )
			);
			return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
		}

		/**
		 * Returns plugin's instances which already have a transaction in current month.
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
						'id', 'shop_id', 'plugin_id',
						'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
					) )
					->where( 'plugin_id = ?', $pluginId )
					->where( 'invoice_status = \'deposited\'' )
					->where( 'fraud_status = \'pass\'' )
					->where( 'refunded = 0' )
					->having( 'trans_period = @period' )
			);
		}

		/**
		 * Returns amount is planned to earn for a given plugin for current month.
		 * @todo common method ( current | next )
		 * @param integer $pluginId - plugin id.
		 * @param array $excluded - a list of excluded transaction ids.
		 * @return integer
		 */
		public function currentMonthPlannedTransactionAmount( $pluginId, $excluded = array () )
		{
			$this->getAdapter()
				->query( 'SET @period3 = DATE_FORMAT( CURDATE() - INTERVAL 3 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$this->getAdapter()
				->query( 'SET @period6 = DATE_FORMAT( CURDATE() - INTERVAL 6 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$this->getAdapter()
				->query( 'SET @period12 = DATE_FORMAT( CURDATE() - INTERVAL 12 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 't' => $this->info( 'name' ) ),
					array (
						'plugin_id', 'product_id', 'date',
						'totalEarned' => new Zend_Db_Expr( 'SUM( amount )' ),
						'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
					)
				)
				->join(
					array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					's.shop_id = t.shop_id AND s.plugin_id = t.plugin_id AND name = \'exclude from stat\'',
					array ( 'value' )
				)
				->where( 'value = 0' )
				->where( 't.plugin_id = ?', $pluginId )
				->where( 'invoice_status = \'deposited\'' )
				->where( 'fraud_status = \'pass\'' )
				->where( 'recurring = 1' )
				->where( 'refunded = 0' )
				->group( 'trans_period' )
				->having( 'trans_period = @period3 AND product_id = 2' )
				->orHaving( 'trans_period = @period6 AND product_id = 3' )
				->orHaving( 'trans_period = @period12 AND product_id = 4' )
				;
			if ( $excluded ) {
				$select->where( 't.id NOT IN ( ? )', implode( ',', $excluded ) );
			}
			$rowset = $this->fetchAll( $select );
			$totalEarned = 0;
			if ( count( $rowset ) ) {
				foreach ( $rowset as $_row ) {
					$totalEarned += isset ( $_row->totalEarned ) ? $_row->totalEarned : 0;
				}
			}
			return $totalEarned;
		}

		/**
		 * Returns amount is planned to earn for a given plugin for next month.
		 * @todo common nethod ( current | next )
		 * @param integer $pluginId - plugin id.
		 * @param array $excluded - a list of excluded transaction ids.
		 * @return integer
		 */
		public function nextMonthPlannedTransactionAmount( $pluginId, $excluded = array () )
		{
			$this->getAdapter()
				->query( 'SET @period3 = DATE_FORMAT( CURDATE() - INTERVAL 2 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$this->getAdapter()
				->query( 'SET @period6 = DATE_FORMAT( CURDATE() - INTERVAL 5 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$this->getAdapter()
				->query( 'SET @period12 = DATE_FORMAT( CURDATE() - INTERVAL 11 MONTH, \'%Y%c\' )' )
				->execute()
				;
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 't' => $this->info( 'name' ) ),
					array (
						'plugin_id', 'product_id', 'date',
						'totalEarned' => new Zend_Db_Expr( 'SUM( amount )' ),
						'trans_period' => new Zend_Db_Expr( 'DATE_FORMAT( date, \'%Y%c\' )' )
					)
				)
				->join(
					array ( 's' => Table::_( 'paymentSettings' )->info( 'name' ) ),
					's.shop_id = t.shop_id AND s.plugin_id = t.plugin_id AND name = \'exclude from stat\'',
					array ( 'value' )
				)
				->where( 'value = 0' )
				->where( 't.plugin_id = ?', $pluginId )
				->where( 'invoice_status = \'deposited\'' )
				->where( 'fraud_status = \'pass\'' )
				->where( 'recurring = 1' )
				->where( 'refunded = 0' )
				->group( 'trans_period' )
				->having( 'trans_period = @period3 AND product_id = 2' )
				->orHaving( 'trans_period = @period6 AND product_id = 3' )
				->orHaving( 'trans_period = @period12 AND product_id = 4' )
				;
			if ( $excluded ) {
				$select->where( 't.id NOT IN ( ? )', implode( ',', $excluded ) );
			}
			$rowset = $this->fetchAll( $select );
			$totalEarned = 0;
			if ( count( $rowset ) ) {
				foreach ( $rowset as $_row ) {
					$totalEarned += isset ( $_row->totalEarned ) ? $_row->totalEarned : 0;
				}
			}
			return $totalEarned;
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
					->where( 'shop_id = ?', $shopId )
					->where( 'plugin_id = ?', $pluginId )
					->where( 'invoice_status = \'deposited\'' )
					->order( 'id DESC' )
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
				->from( $this->info( 'name' ), array (
					'*', 'totalEarned' => new Zend_Db_Expr( 'SUM( amount )' ),
				) )
				->where( 'shop_id = ?', $shopId )
				->where( 'invoice_status = \'deposited\'' )
				->group( 'shop_id' )
				;
			if ( $pluginId ) {
				$select->where( 'plugin_id = ?', $pluginId );
			}
			$row = $this->fetchRow( $select );
			return isset ( $row->totalEarned ) ? $row->totalEarned : 0;
		}
	}
