<?php
	require_once 'D/Controller/Action/Helper/Api/AbstractPlatform.php';

	/**
	 * BigCommerce API helper.
	 * Provides interaction with BigCommerce API.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Bigcommerce
	 * @version 2.0.0
	 */
	class D_Controller_Action_Helper_BigcommerceApi extends AbstractPlatformApi
	{
		protected $apiName = 'Bigcommerce';

		protected $apiUrl = 'https://api.bigcommerce.com/stores/%s/v2/%s';

		protected $apiResouces = array (
			'brands', 'categories', 'customers', 'customers/addresses', 'customer_groups', 'coupons', 'countries', 'countries/states',
			'orders', 'orders/coupons', 'orders/products', 'orders/shipments', 'orders/shippingaddresses', 'options', 'options/value', 'optionsets', 'optionsets/options', 'orderstatuses',
			'products', 'products/skus', 'products/configurablefields', 'products/customfields', 'products/discountrules', 'products/images', 'products/options', 'products/rules', 'products/videos',
			'redirects', 'shipping/methods', 'store', 'time',
		);

		/**
		 * Initializes api.
		 * @param string $storeHash - store hash.
		 * @param string $clientId - client id.
		 * @param string $token - token.
		 */
		public function initialize( $storeHash, $clientId, $token )
		{
			$this->shop = $storeHash;
			$this->headers = array (
				'X-Auth-Client' => $clientId,
				'X-Auth-Token' => $token
			);
			return $this;
		}

		/**
		 * @see AbstractAPI::_formGetResource()
		 */
		protected function _formGetResource( $resource, $count, $format, $params )
		{
			$url = "{$resource}{$count}";
			if ( $params ) {
				$url .= '?' . http_build_query( $params );
			}
			return $url;
		}

		/**
		 * @see AbstractAPI::_formPostResource()
		 */
		protected function _formPostResource( $resource, $format )
		{
			$url = "{$resource}";
			return $url;
		}

		/**
		 * @see AbstractAPI::_formPutResource()
		 */
		protected function _formPutResource( $resource, $format )
		{
			$url = "{$resource}";
			return $url;
		}

		/**
		 * @see AbstractAPI::_formDeleteResource()
		 */
		protected function _formDeleteResource( $resource, $format )
		{
			$url = "{$resource}";
			return $url;
		}

		/**
		 * @see AbstractAPI::_processResponse()
		 */
		protected function _processResponse( $response )
		{
			$code = $this->client->getLastResponse()->getStatus();
			$codeFirstNumber = substr( $code, 0, 1 );
			if ( isset ( $response->errors ) ) {
				$errors = json_encode( $response->errors );
				throw new Exception(
					$this->_message( $errors )
				);
			} else if ( ( $codeFirstNumber == 4 ) || ( $codeFirstNumber == 5 ) ) {
				throw new Exception(
					$this->_message( $response[0]->message )
				);
			}
			return $response;
		}

		/**
		 * @see AbstractAPI::_processNoResponse()
		 */
		protected function _processNoResponse()
		{
			$code = $this->client->getLastResponse()->getStatus();
			if ( $code != 204 ) {
				throw new Exception(
					$this->_message( 'No response: ' . $code )
				);
			}
			return true;
		}

		/**
		 * @see AbstractPlatformApi::_getMethodResponse()
		 */
		protected function _getMethodResponse( $resource, $response, $idBunch )
		{
			if ( isset ( $response->count ) ) {
				$response = $response->count;
			}
			return $response;
		}

		/**
		 * @see AbstractPlatformApi::ping()
		 */
		public function ping()
		{
			try {
				$this->get( 'time' );
				$code = $this->client->getLastResponse()->getStatus();
				if ( $code == 200 ) {
					return true;
				}
				throw new Exception();
			} catch ( Exception $ex ) {
				return false;
			}
		}
	}
