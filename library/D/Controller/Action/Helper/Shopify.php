<?php
	require_once 'D/Controller/Action/Helper/Platform.php';

	/**
	 * Shopify helper.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Shopify
	 * @version 1.0.6
	 */
	class D_Controller_Action_Helper_Shopify extends D_Controller_Action_Helper_Platform
	{
		protected $_platform = 'Shopify';

		/**
		 * Shopify oAuth.
		 * @param string $shop - shop name.
		 * @param integer $pluginId - plugin id.
		 * @param string $redirectURI - redirect_uri value.
		 */
		public function authorize( $shop, $pluginId, $redirectURI = null )
		{
			$config = Config::getInstance();
			$details = Table::_( 'pluginDetails' )->get( $pluginId );
			if ( !$redirectURI ) {
				$redirectURI = $config->plugin->center->shopifyRedirectUri . $pluginId;
			}
			return Zend_Controller_Action_HelperBroker::getStaticHelper( 'Redirector' )->gotoUrl(
				sprintf( $config->plugin->shopify->oauth->code, $shop, $details->client_id, $details->scope, $redirectURI )
			);
		}

		/**
		 * Requests shopify token after authorizing.
		 * @param string $shop - shop name.
		 * @param integer $pluginId - plugin id.
		 * @param string $code - shopify authorized code.
		 * @return string or bool
		 */
		public function requestToken( $shop, $pluginId, $code )
		{
			$config = Config::getInstance();
			$details = Table::_( 'pluginDetails' )->get( $pluginId );
			$client = new Zend_Http_Client(
				sprintf( $config->plugin->shopify->oauth->token, $shop ),
				array (
					'strictredirects' => true,
					'adapter' => 'Zend_Http_Client_Adapter_Curl',
					'curloptions' => array (
						CURLOPT_SSLVERSION => '3'
					)
				)
			);
			$client->setParameterPost( array (
				'client_id' => $details->client_id,
				'client_secret' => $details->client_secret,
				'code' => $code
			) );
			$response = $client->request( 'POST' );
			if ( $response->isSuccessful() ) {
				$body = json_decode( $response->getBody() );
				$session = new Zend_Session_Namespace( 'pc' );
				$session->shop = $shop;
				$session->token = $body->access_token;
				return $body->access_token;
			}
			throw new Exception( $response->getBody() );
		}

		/**
		 * @internal Overrided.
		 */
		public function gotoPlugin( $pluginName, $action = '' )
		{
			if ( $action ) {
				switch ( $action ) {
					case 'install':
						$action = '/app/install';
						break;
				}
			}
			else {
				$action = '/';
			}
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			$session = new Zend_Session_Namespace( 'pc' );
			$action .= "?token={$session->token}&shop={$session->shop}";
			return Zend_Controller_Action_HelperBroker::getStaticHelper( 'Redirector' )->gotoUrl(
				$config->plugin->{strtolower( $this->_platform )}->baseUrl . $filter->filter( $pluginName ) . $action
			);
		}
	}
