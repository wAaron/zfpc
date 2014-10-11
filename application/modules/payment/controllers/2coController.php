<?php
	/**
	 * Specific operations for 2co payment system.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.1.0
	 */
	class Payment_2coController extends Zend_Controller_Action
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			$this->_helper->layout->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
		}

		/**
		 * Creates a transaction after finishing paying process.
		 * @internal HTTP action.
		 */
		public function approveAction()
		{
			if ( !$this->getHelper( '2co' )->isKeyValid() ) {
				return;
			}
			$modelPayment = new Payment_Model_Payment();
			// Parameters.
			$platform = $this->_getParam( 'platform' );
			$shop = $this->_getParam( 'shop' );
			$plugin = $this->_getParam( 'plugin' );
			$plan = $this->_getParam( 'plan' );
			$invoiceId = $this->_getParam( 'invoice_id' );
			$productId = $this->_getParam( 'li_0_product_id' );
			// Save transaction.
			$shop = $modelPayment->getShop( $platform, $shop );
			$plugin = $modelPayment->getPlugin( $platform, $plugin );
			$basePlan = $this->getHelper( $platform )->getBasePlan( $plan );
			$modelPayment->saveTransaction(
				$invoiceId, $shop->id, $plugin->id, $basePlan, $productId, null, null,
				$this->_getParam( 'li_0_recurrence', 0 ),
				$this->_getParam( 'total' ),
				$this->getHelper( '2co' )->transactionDetails()
			);
		}

		/** !!!
		 * Cancels recurrent subscription.
		 */
		public function cancelRecurrentAction()
		{
$logger = Zend_Registry::get( 'logger' );
			$tableTransaction = new Payment_Model_DbTable_Transactions();
			$transaction = $tableTransaction->get(
				$this->_getParam( 'transaction_id' )
			);
			$this->getHelper( '2co' )->setTransaction( $transaction );
			$response = $this->getHelper( '2coAPI' )->call( 'sales/detail_sale', array (
				'invoice_id' => $this->getHelper( '2co' )->transactionDetail( 'invoice_id' )
			) );
			if ( $response->response_code == 'OK' ) {
				$lineitemId = (integer) $response->sale->invoices->lineitems->lineitem_id;
				if ( $lineitemId ) {
					$response = $this->getHelper( '2coAPI' )->call( 'sales/stop_lineitem_recurring', array (
						'lineitem_id' => $lineitemId
					), 'POST' );
$logger->debug( $lineitemId );
$logger->debug( json_encode( $response ) );
					if ( $response->response_code == 'OK' ) {
						$transaction->recurring = 0;
						$transaction->save();
					}
				}
			}
			return $this->getHelper( 'Redirector' )->gotoUrl(
				$_SERVER['HTTP_REFERER']
			);
		}
	}
