<?php
	require_once 'Zend/Controller/Action/Helper/Abstract.php';

	/**
	 * Options helper.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.0
	 */
	class D_Controller_Action_Helper_Options extends Zend_Controller_Action_Helper_Abstract
	{
		/**
		 * Total price for all overdrafts.
		 * @var integer
		 */
		private $_overdraftTotalPrice = 0;

		/**
		 * Returns all overdrafts for full months.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @return array
		 */
		public function overdrafts( $shopId, $pluginId )
		{
			$overdrafts = array ();
			$tableStatisticsOptions = new Default_Model_DbTable_OptionStatistics();
			$statistics = $tableStatisticsOptions->overdrafts( $shopId, $pluginId );
			if ( count ( $statistics ) ) {
				foreach ( $statistics as $_record ) {
					$currentValue = $_record->os_value / $_record->option_overdraft_unit_count;
					if ( $currentValue > $_record->option_value ) {
						$overdraftValue = $currentValue - $_record->option_value;
						$price = $overdraftValue * $_record->option_price_for_overdraft_unit;
						$this->_overdraftTotalPrice += $price;
						$overdrafts[] = array (
							'id' => $_record->id,
							'name' => $_record->option_name,
							'period' => $_record->period,
							'price' => $price
						);
					}
				}
			}
			return $overdrafts;
		}

		/**
		 * Returns an overdraft total price.
		 * @return integer
		 */
		public function overdraftTotalPrice() {
			return $this->_overdraftTotalPrice;
		}
	}
