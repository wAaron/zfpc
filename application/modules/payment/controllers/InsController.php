<?php
	/**
	 * 2co ins handler.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.0.0
	 */
	class Payment_InsController extends Zend_Controller_Action
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
		 * The plug for unprocessed INS actions.
		 */
		public function indexAction()
		{
			$this->_saveLog();
		}

		/**
		 * Order Created.
		 */
		public function orderCreatedAction()
		{
			if ( !$this->getHelper( '2co' )->isMD5Valid() ) {
				return;
			}
			$modelPayment = new Payment_Model_Payment();
			$tableShops = new Default_Model_DbTable_Shops();
			$tablePlugins = new Default_Model_DbTable_Plugins();
			// Parameters.
			$invoiceId = $this->_getParam( 'invoice_id' );
			$invoiceStatus = $this->_getParam( 'invoice_status' );
			$fraudStatus = $this->_getParam( 'fraud_status' );
			$saleId = $this->_getParam( 'sale_id' );
			// Update transaction.
			$transaction = $modelPayment->fetchTransactionInvoice( $invoiceId );
			$transaction->invoice_status = $invoiceStatus;
			$transaction->fraud_status = $fraudStatus;
			$transaction->save();
			// Save sale.
			$modelPayment->insertSale(
				$saleId, $transaction->shop_id, $transaction->plugin_id, $transaction->payment_plan_id
			);
		}

		/**
		 * Invoice Status Changed.
		 */
		public function invoiceStatusChangedAction()
		{
			if ( !$this->getHelper( '2co' )->isMD5Valid() ) {
				return;
			}
			$modelPayment = new Payment_Model_Payment();
			// Processing.
			$modelPayment->invoiceStatus(
				$this->_getParam( 'invoice_id' ),
				$this->_getParam( 'invoice_status' )
			);
		}

		/**
		 * Fraud Status Changed.
		 */
		public function fraudStatusChangedAction()
		{
			if ( !$this->getHelper( '2co' )->isMD5Valid() ) {
				return;
			}
			$modelPayment = new Payment_Model_Payment();
			// Parameters.
			$invoiceId = $this->_getParam( 'invoice_id' );
			$fraudStatus = $this->_getParam( 'fraud_status' );
			// Processing.
			$transaction = $modelPayment->fetchTransactionInvoice( $invoiceId );
			if ( !$transaction->refunded && ( $transaction->fraud_status != 'pass' ) && ( $fraudStatus == 'pass' ) ) {
				// Extend paid till date.
				$this->_paidTill( $invoiceId );
				// Mark an options as paid.
				$matches = array ();
				if ( preg_match_all( '/li_[1-9]_product_id=(\d+)/', $transaction->details, $matches ) ) {
					$tableStatisticsOptions = new Default_Model_DbTable_OptionStatistics();
					foreach ( $matches[1] as $_id ) {
						$tableStatisticsOptions->overdraftStatus( $_id, 'paid' );
					}
				}
			}
			$modelPayment->fraudStatus( $invoiceId, $fraudStatus );
		}

		/**
		 * Refund Issued.
		 */
		public function refundIssuedAction()
		{
			if ( !$this->getHelper( '2co' )->isMD5Valid() ) {
				return;
			}
			$modelPayment = new Payment_Model_Payment();
			// Parameters.
			$invoiceId = $this->_getParam( 'invoice_id' );
			// Refund transaction.
			$modelPayment->refund( $invoiceId, 1 );
			// Reduse paid till date.
			$transaction = $modelPayment->fetchTransactionInvoice( $invoiceId );
			$this->_paidTill( $invoiceId );
		}

		/**
		 * Recurring Installment Success.
		 */
		public function recurringInstallmentSuccessAction()
		{
			if ( !$this->getHelper( '2co' )->isMD5Valid() ) {
				return;
			}
			$modelPayment = new Payment_Model_Payment();
			$tableShops = new Default_Model_DbTable_Shops();
			$tablePlugins = new Default_Model_DbTable_Plugins();
			// Save transaction.
			$sale = $modelPayment->fetchSale( $this->_getParam( 'sale_id' ) );
			$modelPayment->saveTransaction(
				$this->_getParam( 'invoice_id' ),
				$sale->shop_id, $sale->plugin_id, $sale->payment_plan_id,
				$this->_getParam( 'item_id_1' ),
				'deposited', 'pass', '1',
				$this->_getParam( 'item_usd_amount_1' ),
				$this->getHelper( '2co' )->transactionDetails()
			);
			// Extend paid till date.
			$this->_paidTill( $this->_getParam( 'invoice_id' ) );
		}

		/**
		 * Preliminary preparation for paid_till date changing.
		 * @param integer $invoiceId - 2co invoice id.
		 */
		private function _paidTill( $invoiceId )
		{
			$modelPayment = new Payment_Model_Payment();
			$tablePlugins = new Default_Model_DbTable_Plugins();
			$tableShops = new Default_Model_DbTable_Shops();
			// Prepare date period.
			$transaction = $modelPayment->fetchTransactionInvoice( $invoiceId );
			$this->getHelper( '2co' )->setTransaction( $transaction );
			$period = $this->getHelper( '2co' )->paidPeriod();
			if ( $transaction->refunded ) {
				$period *= -1;
			}
			// Change paid till date.
			$instance = $tablePlugins->getInstance( $transaction->shop_id, $transaction->plugin_id );
			$instance->paidTill( $period );
		}

		/**
		 * Logger.
		 */
		private function _saveLog()
		{
			$logger = Zend_Registry::get( 'logger' );
			$logger->debug( "" );
			$logger->debug( '--------------- '.$this->_getParam( 'message_type', 'APPROVE' ).' --------------' );
			$key = strtoupper( md5(
				$this->_getParam( 'sale_id' ) .'1747059'.
				$this->_getParam( 'invoice_id' ) .'JK4G78KJjfgfg457'
			) );
			$logger->debug( $key );
			foreach ( $this->_getAllParams() as $_key => $_param ) {
				$logger->debug( "{$_key}: {$_param}" );
			}
		}
	}
