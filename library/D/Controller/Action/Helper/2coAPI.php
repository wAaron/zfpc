<?php
	require_once 'Zend/Controller/Action/Helper/Abstract.php';

	/**
	 * 2Checkout API helper.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.0
	 */
	class D_Controller_Action_Helper_2coAPI extends Zend_Controller_Action_Helper_Abstract
	{
		/**
		 * API Client.
		 * @var Zend_Http_Client
		 */
		private $_client;

		/**
		 * API username.
		 * @var string
		 */
		private $_user = 'dcoding';

		/**
		 * API password.
		 * @var string
		 */
		private $_password = 'Dcoding123';

		/**
		 * API base URL.
		 * @var string
		 */
		private $_baseUrl = 'https://www.2checkout.com/api/';

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->_client = new Zend_Http_Client();
			$this->_client->setAuth( $this->_user, $this->_password );
		}

		/**
		 * Makes API call.
		 * @param string $name - call name.
		 * @param array $params - call parameters.
		 * @param string $method - http method.
		 * @return SimpleXMLElement
		 */
		public function call( $name, $params, $method = 'GET' )
		{
			$this->_client->setParameterGet( $params );
			$this->_client->setUri( $this->_baseUrl . $name );
			$response = $this->_client->request( $method );
			$body = new SimpleXMLElement( $response->getBody() );
			return $body;
		}
	}
