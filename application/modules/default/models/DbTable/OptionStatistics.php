<?php
	/**
	 * Option statistics db table.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.1.1
	 */
	class Default_Model_DbTable_OptionStatistics extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins_plans_options_statistics';

		/**
		 * Increases statistical value.
		 * @param array $data - stistics data.
		 */
		public function increase( $data )
		{
			$period = date( "Y-m-00" );
			$where = "`shop_id` = {$data['shop_id']} AND `plugin_id` = {$data['plugin_id']} AND `key` = '{$data['key']}' AND `period` = '$period'";
			if ( $statistics = $this->fetchRow( $where ) ) {
				$this->update( array (
					'value' => $statistics->value + $data['value']
				), $where );
			}
			else {
				$this->_insert( $data, $period );
			}
		}

		/**
		 * Decreases statistical value.
		 * @param array $data - stistics data.
		 */
		public function decrease( $data )
		{
			$period = date( "Y-m-00" );
			$where = "`shop_id` = {$data['shop_id']} AND `plugin_id` = {$data['plugin_id']} AND `key` = '{$data['key']}' AND `period` = '$period'";
			if ( $statistics = $this->fetchRow( $where ) ) {
				$this->update( array (
					'value' => $statistics->value - $data['value']
				), $where );
			}
			else {
				$this->_insert( $data, $period );
			}
		}

		/**
		 * Sets statistical value.
		 * @param array $data - stistics data.
		 */
		public function set( $data )
		{
			$period = date( "Y-m-00" );
			$where = "`shop_id` = {$data['shop_id']} AND `plugin_id` = {$data['plugin_id']} AND `key` = '{$data['key']}' AND `period` = '$period'";
			if ( $statistics = $this->fetchRow( $where ) ) {
				$this->update( array (
					'value' => $data['value']
				), $where );
			}
			else {
				$this->_insert( $data, $period );
			}
		}

		/**
		 * Sets statistical value only if it higher then current.
		 * @param array $data - stistics data.
		 */
		public function setMax( $data )
		{
			$period = date( "Y-m-00" );
			$where = "`shop_id` = {$data['shop_id']} AND `plugin_id` = {$data['plugin_id']} AND `key` = '{$data['key']}' AND `period` = '$period'";
			if ( $statistics = $this->fetchRow( $where ) ) {
				if ( $data['value'] > $statistics->value ) {
					$this->update( array (
						'value' => $data['value']
					), $where );
				}
			}
			else {
				$this->_insert( $data, $period );
			}
		}

		/**
		 * Inserts a new statistical value.
		 * @param array $data - stistics data.
		 * @param string $period - date period.
		 */
		private function _insert( $data, $period )
		{
			$this->insert( array (
				'shop_id' => $data['shop_id'],
				'plugin_id' => $data['plugin_id'],
				'key' => $data['key'],
				'period' => $period,
				'value' => $data['value']
			) );
		}

		/**
		 * Returns statistics for current perion.
		 * @see period()
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param integer $planId - plan id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function current( $shopId, $pluginId, $planId ) {
			$period = date( "Y-m-00" );
			return $this->period( $shopId, $pluginId, $planId, $period );
		}

		/**
		 * Returns statistics for previous perion.
		 * @see period()
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param integer $planId - plan id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function previous( $shopId, $pluginId, $planId ) {
			$period = date( "Y-m-00", strtotime( "-1 month" ) );
			return $this->period( $shopId, $pluginId, $planId, $period );
		}

		/**
		 * Returns statistics for expired perion.
		 * @see period()
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param integer $planId - plan id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function expired( $shopId, $pluginId, $planId ) {
			$period = date( "Y-m-00", strtotime( "-2 month" ) );
			return $this->period( $shopId, $pluginId, $planId, $period );
		}

		/**
		 * Returns statistics for given perion.
		 * Doesn't select options marked as not for display.
		 *
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param integer $planId - plan id.
		 * @param string $period - date period.
		 * @return Zend_Db_Table_Rowset
		 */
		public function period( $shopId, $pluginId, $planId, $period )
		{
			$tableOptions = new Default_Model_DbTable_Options();
			$tablePlansToOptions = new Default_Model_DbTable_PlansToOptions();
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'os' => $this->info( 'name' ) ),
						array ( 'os.*', 'os_value' => 'os.value', 'os_id' => 'os.id' )
					)
					->joinLeft(
						array ( 'o' => $tableOptions->info( 'name' ) ),
						"o.key = os.key",
						array ( 'o.*' )
					)
					->joinLeft(
						array ( 'pto' => $tablePlansToOptions->info( 'name' ) ),
						"pto.option_id = o.id",
						array ( 'pto.plan_id' )
					)
					->where( 'os.shop_id = ?', $shopId )
					->where( 'os.plugin_id = ?', $pluginId )
					->where( 'os.period = ?', $period )
					->where( 'o.display = 1' )
					->where( 'pto.plan_id = ?', $planId )
			);
		}

		public function previousPeriod( $period )
		{
			$tableOptions = new Default_Model_DbTable_Options();
			$tablePlansToOptions = new Default_Model_DbTable_PlansToOptions();
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'os' => $this->info( 'name' ) ),
						array ( 'os.*' )
					)
					->joinLeft(
						array ( 'o' => $tableOptions->info( 'name' ) ),
						"o.key = os.key",
						array ( 'o.use_for_payment' )
					)
					->where( 'os.period = ?', $period )
			);
		}

		/**
		 * Returns all overdrafts for previous months.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function overdrafts( $shopId, $pluginId )
		{
			return $this->fetchAll(
				$this->select()
					->from(
						array ( 'os' => $this->info( 'name' ) ),
						array ( 'os.*', 'os_value' => 'os.value' )
					)
					->where( 'os.shop_id = ?', $shopId )
					->where( 'os.plugin_id = ?', $pluginId )
					->where( "os.overdraft_status = 'overdraft'" )
			);
		}

		 // Rename
		public function overdraftUnpaidPeriod( $shopId, $pluginId )
		{
			$where = "`shop_id` = $shopId AND `plugin_id` = $pluginId AND `overdraft_status` = 'overdraft' AND `period` < DATE_SUB( NOW(), INTERVAL 2 MONTH )";
			$select = $this->select()
				->from(
					array ( 'os' => $this->info( 'name' ) ),
					array ( 'os.*', 'os_value' => 'os.value' )
				)
				->where( $where )
				->limit( 1 )
				;
			if ( $record = $this->fetchRow( $select ) ) {
				return $record->period;
			}
			return null;
		}

		/**
		 * Checks out if there is current month overdraft.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param integer $currentPlanId - current plan id.
		 * @return bool
		 */
		public function hasCurrentMonthOverdraf( $shopId, $pluginId, $currentPlanId )
		{
			$currentMonthStatistics = $this->current( $shopId, $pluginId, $currentPlanId );
			if ( count( $currentMonthStatistics ) ) {
				foreach ( $currentMonthStatistics as $os ) {
					$currentValue = $os->os_value / $os->overdraft_unit_count;
					$currentValue = is_float( $currentValue ) ? round( $currentValue, 2 ) : $currentValue;
					if ( $os->use_for_payment && ( $currentValue > $os->value ) ) {
						return true;
						break;
					}
				}
			}
			return false;
		}

		/**
		 * Updates overdraft status.
		 * @param integer $recordId - record id.
		 * @param string $status - status.
		 */
		public function overdraftStatus( $recordId, $status )
		{
			return $this->update( array (
				'overdraft_status' => $status
			), "`id` = $recordId" );
		}
	}
