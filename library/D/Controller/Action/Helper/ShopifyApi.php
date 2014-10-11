<?php
	require_once 'D/Controller/Action/Helper/Api/AbstractPlatform.php';

	/**
	 * Shopify API helper.
	 * Provides interaction with Shopify API.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Shopify
	 * @version 2.0.1
	 */
	class D_Controller_Action_Helper_ShopifyApi extends AbstractPlatformApi
	{
		protected $apiName = 'Shopify';

		protected $apiUrl = 'https://%s/admin/%s';

		protected $apiResouces = array (
			'application_charges', 'application_charges/activate', 'articles/authors', 'articles/tags', 'blogs', 'blogs/articles',
			'checkouts', 'collects', 'comments', 'comments/spam', 'comments/not_spam', 'comments/approve', 'comments/remove', 'comments/restore',
			'countries', 'countries/provinces', 'custom_collections', 'customers', 'events', 'fulfillment_services',
			'fulfillments', 'metafields', 'orders', 'orders/close', 'orders/open', 'orders/risks', 'orders/cancel', 'orders/events', 'orders/fulfillments',
			'pages', 'products', 'products/images', 'products/variants', 'products/metafields', 'product_search_engines',
			'recurring_application_charges', 'recurring_application_charges/activate', 'redirects', 'script_tags', 'shop', 'smart_collections', 'themes', 'themes/assets', 'transactions', 'variants', 'webhooks'
		);

		/**
		 * Initializes api.
		 * @param string $shop - shop name.
		 * @param string $token - token.
		 */
		public function initialize( $shop, $token )
		{
			$this->shop = $shop;
			$this->headers = array (
				"X-Shopify-Access-Token: {$token}"
			);
			return $this;
		}

		/**
		 * @see AbstractAPI::_processResponse()
		 */
		protected function _processResponse( $response )
		{
			$code = $this->client->getLastResponse()->getStatus();
			if ( $code == 429 ) {
				usleep( 500000 );
				$response = $this->client->request();
				$response = json_decode( trim( $response->getBody() ) );
			}
			if ( isset ( $response->errors ) ) {
				$errors = json_encode( $response->errors );
				throw new Exception(
					$this->_message( $errors )
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
			if ( $code != 200 ) {
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
			$bunch = array (
				'products' => 'product',
                'webhooks' => 'webhook'
			);
			if ( in_array( $resource, array_keys( $bunch ) ) && $idBunch ) {
				$resource = $bunch[ $resource ];
			}
			if ( isset ( $response->$resource ) ) {
				$response = $response->$resource;
			}
			return $response;
		}

		/**
		 * @see AbstractPlatformApi::ping()
		 */
		public function ping()
		{
			try {
				$this->get( 'shop' );
				$code = $this->client->getLastResponse()->getStatus();
				if ( $code == 200 ) {
					return true;
				}
				throw new Exception();
			} catch ( Exception $ex ) {
				return false;
			}
		}

		/**
		 * Returns main theme name.
		 * @return string|null
		 */
		public function getMainTheme()
		{
			if ( $themes = $this->getItems( 'themes' ) ) {
				foreach ( $themes as $_theme ) {
					if ( $_theme->role == 'main' ) {
						return $_theme;
					}
				}
			}
			return null;
		}
	}
