<?php
	/**
	 * Keeps Shopify currency rate in actual condition.
	 * Provide with statistics about orders of shops.
	 *
	 * @author Kovalev Yury
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.2
	 */
	class Default_FinanceController extends Zend_Controller_Action
	{
		/**
		 * Updates currency code in file.
		 * @internal Cron action
		 */
		public function updateRatesAction()
		{
			$config = Config::getInstance();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Start cron.
			$this->getHelper( 'admin' )
				->startCronTask( 'update-rates' );
			// Connect to remote server.
			$client = new Zend_Http_Client( $config->finance->xeCurrencyPath , array (
				'adapter' => 'Zend_Http_Client_Adapter_Curl',
			) );
			$response = $client->request( 'GET' );
			if ( $response->isSuccessful() ) {
				file_put_contents(
					PUBLIC_FILE_PATH .'/'. $config->finance->currencyFileName,
					$this->getRates( $response->getBody(), false )
				);
			} else {
				throw new Exception( $response->getBody() );
			}
			// Stop cron.
			$this->getHelper( 'admin' )
				->stopCronTask( 'update-rates' );
		}

		/**
		 * Convert Currency
		 * @internal CLI action.
		 * @return float
		 */
		public function convertAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			$_rates = json_decode( file_get_contents( PUBLIC_FILE_PATH .'/'. $config->finance->currencyFileName ), true );
			$amount = (float)$this->_getParam( 'amount' );
			$from = $this->_getParam( 'fromCode' );
			$to = $this->_getParam( 'toCode', 'USD' );

			if (isset($_rates[$from]) && isset($_rates[$to]) && !empty($from)) {
				$amount = ($amount * $_rates[$from]) / $_rates[$to];
			}

			echo $amount;
			return;
		}

		/**
		 * Returns rates.
		 * @param string $content - Shopify shop response.
		 * @param boolean $is_array - decode or not.
		 * @return bool|mixed
		 */
		protected function getRates( $content, $is_array = true ) {
			if (preg_match("/{(.*)}/", $content, $matches)) {
				if ($is_array) {
					$currency = isset($matches[0]) ? json_decode($matches[0], true) : false;
				} else {
					$currency = isset($matches[0]) ? $matches[0] : false;
				}
				return $currency;
			}
			return false;
		}
	}
