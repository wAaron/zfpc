<?php
	require_once 'D/Controller/Action/Helper/Platform.php';

	/**
	 * BigCommerce helper.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package BigCommerce
	 * @version 1.2.2
	 */
	class D_Controller_Action_Helper_Bigcommerce extends D_Controller_Action_Helper_Platform
	{
		protected $_platform = 'Bigcommerce';

		/**
		 * Requests bigcommerce token after authorizing.
		 * @param integer $pluginId - plugin id.
		 * @param string $code - bigcommerce authorized code.
		 * @param string $context - shop context.
		 * @return string or bool
		 */
		public function requestToken( $pluginId, $code, $context )
		{
			$config = Config::getInstance();
			$details = Table::_( 'pluginDetails' )->get( $pluginId );
			$client = new Zend_Http_Client(
				$config->plugin->bigcommerce->oauth->token,
				array (
					'strictredirects' => true,
					'adapter' => 'Zend_Http_Client_Adapter_Curl',
					'curloptions' => array (
						CURLOPT_SSLVERSION => '3'
					)
				)
			);
			$view = Zend_Layout::getMvcInstance()->getView();
			$redirectUri = $view->serverUrl( $view->url( array (
				'id' => $pluginId
			), 'bigcommerce', true ) );
			$client->setParameterPost( array (
				'client_id' => $details->client_id,
				'client_secret' => $details->client_secret,
				'code' => $code,
				'scope' => $details->scope,
				'grant_type' => 'authorization_code',
				'redirect_uri' => $redirectUri,
				'context' => $context
			) );
			$response = $client->request( 'POST' );
			if ( $response->isSuccessful() ) {
				$body = json_decode( $response->getBody() );
				return $body->access_token;
			}
			throw new Exception( $response->getBody() );
		}

		/**
		 * Verifies payload.
		 * @author BigCommerce
		 * @param string $payload - payload.
		 * @param string $secret - client secret.
		 * @return object
		 */
		public function verifyPayload( $payload, $secret )
		{
			list ( $payload, $encodedSignature ) = explode( '.', $payload, 2 );
			// decode the data
			$signature = base64_decode( $encodedSignature );
			$data = json_decode( base64_decode( $payload ), true );
			// confirm the signature
			$expectedSignature = hash_hmac( 'sha256', $payload, $secret, $raw = true );
			if ( $this->secureCompare( $signature, $expectedSignature ) ) {
				throw new Exception( 'Bad Signed JSON signature!' );
			}
			return $data;
		}

		/**
		 * Secure compares 2 string.
		 * @author BigCommerce
		 * @param string $str1
		 * @param string $str2
		 * @return bool
		 */
		public function secureCompare( $str1, $str2 )
		{
			$res = $str1 ^ $str2;
			$ret = strlen( $str1 ) ^ strlen( $str2 );
			for ( $i = strlen( $res ) - 1; $i >= 0; $i-- ) {
				$ret += ord( $res[ $i ] );
			}
			return !$ret;
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
					default:
						$action = base64_decode( $action );
				}
			}
			else {
				$action = '/';
			}
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			$session = new Zend_Session_Namespace( 'pc' );
			if ( isset ( $session->token ) ) {
				$action .= "?token={$session->token}&shop={$session->shop}&hash={$session->hash}";
				Zend_Session::namespaceUnset( 'pc' );
			}
			return Zend_Controller_Action_HelperBroker::getStaticHelper( 'Redirector' )->gotoUrl(
				$config->plugin->{strtolower( $this->_platform )}->baseUrl . $filter->filter( $pluginName ) . $action
			);
		}
	}
