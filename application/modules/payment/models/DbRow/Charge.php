<?php
	/**
	 * DbRow of payment_charges DbTable.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.0.0
	 */
	class Payment_Model_DbRow_Charge extends Zend_Db_Table_Row
	{
		/**
		 * Tells whether charge is recurring or not.
		 * @return bool
		 */
		public function isRecurring() {
			return (bool) $this->recurring;
		}

		/**
		 * Returns a transaction detail.
		 * @param string $name - detail name.
		 * @return string | null
		 */
		public function detail( $name )
		{
			$matches = array ();
			if ( preg_match( '/'. $name .'=(.+)/i', $this->details, $matches ) ) {
				return $matches[1];
			}
			return null;
		}

		/**
		 * Calculates paid period.
		 * @return integer
		 */
		public function paidPeriod()
		{
			// Product.
			$tableProducts = new Payment_Model_DbTable_Products();
			$product = $tableProducts->get( $this->product_id );
			// Period.
			$date = new DateTime( $this->date );
			$timestamp = $date->getTimestamp();
			$date->modify( "+ {$product->quantity} month" );
			$timestamp = ( $date->getTimestamp() - $timestamp );
			return $timestamp;
		}

		/**
		 * Calculates factor between old and new prices of the same plan.
		 * @param string $oldPlan - old plan name.
		 * @param string $newPlan - new plan name.
		 * @return float | integer
		 */
		public function factor( $oldPlan, $newPlan )
		{
			$tablePlans = new Payment_Model_DbTable_Plans();
			$tablePrices = new Payment_Model_DbTable_Prices();
			$dbAdapter = $tablePlans->getAdapter();
			// Old plan.
			$where = "`name` = " . $dbAdapter->quote( $oldPlan, 'string' );
			$planId = $tablePlans->fetchRow( $where )->id;
			$where = "`plugin_id` = {$this->plugin_id} AND `payment_plan_id` = {$planId} AND `product_id` = {$this->product_id}";
			$oldPrice = $tablePrices->fetchRow( $where )->price;
			// New plan.
			$where = "`name` = ". $dbAdapter->quote( $newPlan, 'string' );
			$planId = $tablePlans->fetchRow( $where )->id;
			$where = "`plugin_id` = {$this->plugin_id} AND `payment_plan_id` = {$planId} AND `product_id` = {$this->product_id}";
			$newPrice = $tablePrices->fetchRow( $where )->price;
			// Pay factor.
			$factor = $oldPrice / $newPrice;
			return $factor;
		}
	}
