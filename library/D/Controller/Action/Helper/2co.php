<?php
	require_once 'Zend/Controller/Action/Helper/Abstract.php';

	/**
	 * 2Checkout helper.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.2
	 */
	class D_Controller_Action_Helper_2co extends Zend_Controller_Action_Helper_Abstract
	{
		/**
		 * 2co account id.
		 * @var string
		 */
		private $_accountId = '1747059';

		/**
		 * 2co account secret word.
		 * @var string
		 */
		private $_secretWord = 'JK4G78KJjfgfg457';

		/**
		 * Transaction.
		 * @var Zend_Db_Table_Row
		 */
		private $_transaction;

		/**
		 * Returns 2co account id.
		 * @return string
		 */
		public function getAccountId() {
			return $this->_accountId;
		}

		/**
		 * Sets a transaction for calculation.
		 * @todo delete.
		 * @param Zend_Db_Table_Row $transaction - transaction object.
		 */
		public function setTransaction( Zend_Db_Table_Row $transaction ) {
			$this->_transaction = $transaction;
		}

		/**
		 * Processes request data and formats transaction parameters.
		 * @todo move to transaction DbRow.
		 * @return string
		 */
		public function transactionDetails()
		{
			$transactionData = $this->getRequest()->getParams();
			unset ( $transactionData['module'], $transactionData['controller'], $transactionData['action'] );
			$transactionDetails = '';
			foreach ( $transactionData as $_key => $_val ) {
				$transactionDetails .= "{$_key}={$_val}\n";
			}
			return $transactionDetails;
		}

		/**
		 * Returns a parameter's value, beforehand having found it at transaction details.
		 * @todo move to transaction DbRow.
		 * @param string $name - parameter name.
		 * @return string or null
		 */
		public function transactionDetail( $name )
		{
			$matches = array ();
			if ( preg_match( '/'.$name.'=(.+)/i', $this->_transaction->details, $matches ) ) {
				return $matches[1];
			}
			return null;
		}

		/**
		 * Returns transaction's date.
		 * @todo move to transaction DbRow.
		 * @return string
		 */
		public function transactionDate() {
			return date( "j M Y", strtotime( $this->_transaction->date ) );
		}

		/**
		 * Checks out whether hash key is valid or not.
		 * Uses by approve action.
		 * @return bool
		 */
		public function isKeyValid()
		{
			$key = strtoupper( md5(
				$this->_secretWord . $this->_accountId .
				$this->getRequest()->getParam( 'order_number' ) .
				$this->getRequest()->getParam( 'total' )
			) );
			return ( $key == $this->getRequest()->getParam( 'key' ) );
		}

		/**
		 * Checks out whether hash key is valid or not.
		 * Uses by ins action.
		 * @return bool
		 */
		public function isMD5Valid()
		{
			$key = strtoupper( md5(
				$this->getRequest()->getParam( 'sale_id' ) . $this->_accountId .
				$this->getRequest()->getParam( 'invoice_id' ) . $this->_secretWord
			) );
			return ( $key == $this->getRequest()->getParam( 'md5_hash' ) );
		}

		/**
		 * Forms HTML with price information.
		 * @param string $variety - product variety.
		 * @param integet $quantity - product quantity.
		 * @param integer $firstPrice - product first price.
		 * @param integer $price - product current price.
		 * @param bool $strongPrice - whether make price bold.
		 * @param bool $formatPrice - whether format price or not.
		 * @return string
		 */
		public function price( $variety, $quantity, $firstPrice, $price, $strongPrice = false, $formatPrice = false )
		{
			$variety = Zend_Registry::get( 'translate' )->_( array ( 'month', 'months', $quantity ) );
			$sign = is_numeric( $price ) ? '$' : '';
			$formattedPrice = $price;
			if ( $formatPrice && $price && is_numeric( $price ) && ( $quantity > 1 ) ) {
				$formattedPrice = round( $price / $quantity, 2 ) .'/'. strtolower( substr( $variety, 0, 2 ) );
			}
			if ( !$strongPrice ) {
				$string = "{$quantity} {$variety}: {$sign}{$formattedPrice}";
			} else {
				$string = "{$quantity} {$variety}: <strong>{$sign}{$formattedPrice}</strong>";
			}
			if ( ( $quantity > 1 ) && is_numeric( $firstPrice ) ) {
				$save = round( ( $firstPrice * $quantity ) - $price, 2 );
				$string .= " ( Save: \${$save} )";
			}
			return $string;
		}

		/**
		 * Calculates factor between old and new prices of the same plan.
		 * @param string $oldPlan - old plan name.
		 * @param string $newPlan - new plan name.
		 * @return mixed
		 */
		public function factor( $oldPlan, $newPlan )
		{
			$tablePlans = new Payment_Model_DbTable_Plans();
			$tablePrices = new Payment_Model_DbTable_Prices();
			// Old plan.
			$where = "`name` = ". $tablePlans->getAdapter()->quote( $oldPlan, 'string' );
			$planId = $tablePlans->fetchRow( $where )->id;
			$where = "`plugin_id` = {$this->_transaction->plugin_id} AND `payment_plan_id` = $planId AND `product_id` = {$this->_transaction->product_id}";
			$oldPrice = $tablePrices->fetchRow( $where )->price;
			// New plan.
			$where = "`name` = ". $tablePlans->getAdapter()->quote( $newPlan, 'string' );
			$planId = $tablePlans->fetchRow( $where )->id;
			$where = "`plugin_id` = {$this->_transaction->plugin_id} AND `payment_plan_id` = $planId AND `product_id` = {$this->_transaction->product_id}";
			$newPrice = $tablePrices->fetchRow( $where )->price;
			// Pay factor.
			$factor = $oldPrice / $newPrice;
			return $factor;
		}

		/**
		 * Calculates transaction paid period.
		 * @todo move to transaction DbRow.
		 * @return integer
		 */
		public function paidPeriod()
		{
			// Product.
			$tableProducts = new Payment_Model_DbTable_Products();
			$product = $tableProducts->get( $this->_transaction->product_id );
			// Period.
			$date = new DateTime( $this->_transaction->date );
			$timestamp = $date->getTimestamp();
			$date->modify( "+ {$product->quantity} month" );
			$timestamp = $date->getTimestamp() - $timestamp;
			return $timestamp;
		}
	}
