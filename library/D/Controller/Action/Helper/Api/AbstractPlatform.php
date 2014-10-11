<?php
	require_once 'Zend/Controller/Action/Helper/Abstract.php';

	/**
	 * Base API provides with common cross-platform interface.
	 * Each platform API should use this this interface.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.2.4
	 */
	abstract class AbstractPlatformApi extends Zend_Controller_Action_Helper_Abstract
	{
		/**
		 * Shop domain.
		 * @var string
		 */
		protected $shop;

		/**
		 * API name.
		 * @var string
		 */
		protected $apiName;

		/**
		 * API url.
		 * @var string
		 */
		protected $apiUrl;

		/**
		 * API resources.
		 * @var array
		 */
		protected $apiResouces;

		/**
		 * API client.
		 * @var Zend_Http_Client
		 */
		protected $client;

		/**
		 * Extra headers.
		 * @var array
		 */
		protected $headers = array ();

		/**
		 * Constructor.
		 * Initializes HTTP client.
		 */
		public function __construct()
		{
			$this->client = new Zend_Http_Client();
			$this->client->setConfig( array (
				'strictredirects' => true,
				'adapter' => 'Zend_Http_Client_Adapter_Curl',
				'curloptions' => array (
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSLVERSION => 3,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => 0
				)
			) );
		}

		/**
		 * Cloning is disabled.
		 */
		protected function __clone() {}

		/**
		 * Sends HTTP request to API server and returns response.
		 * It is being used by wrapper methods.
		 *
		 * @param string $method - HTTP method.
		 * @param string $url - item (api resource) url part.
		 * @param string $format - request data format.
		 * @param string $fields - request parameters in JSON format.
		 * @throws Exception
		 * @return object
		 */
		public function request( $method, $url, $format, $fields = null )
		{
			$this->client->resetParameters();
			// Set URL.
			$url = sprintf( $this->apiUrl, $this->shop, $url );
			$this->client->setMethod( strtoupper( $method ) );
			$this->client->setUri( $url );
			// Set HTTP body and headers.
			$headers = array (
				'User-Agent: SpurIT external API Client',
				"Content-Type: application/{$format}; charset=utf-8",
				"Accept: application/{$format}",
			);
			if ( $fields ) {
				$fields = json_encode( $fields );
				$headers = array_merge( $headers, array (
					'Content-Length: ' . strlen( $fields )
				) );
				$this->client->setRawData( $fields );
			}
			$this->client->setHeaders( array_merge( $headers, $this->headers ) );
			// Call API and process a response.
			$response = $this->client->request();
			$response = trim( $response->getBody() );
			if ( $response ) {
				$response = json_decode( $response );
				return $this->_processResponse( $response );
			} else {
				return $this->_processNoResponse();
			}
		}

		/**
		 * Processes server respone.
		 * @param mixed $response - server response.
		 */
		abstract protected function _processResponse( $response );

		/**
		 * Processes no respone case.
		 */
		abstract protected function _processNoResponse();

		/**
		 * Processes GET method response.
		 */
		abstract protected function _getMethodResponse( $resource, $response, $idBunch );

		/**
		 * Returns api resource item/items by means of GET method.
		 * @param string $resource - resource name.
		 * @param integer|array $idBunch - bunch of id or single id.
		 * @param array $params - filter params.
		 * @param string $format - request/response format.
		 * @return mixed
		 */
		public function get( $resource, $idBunch = null, $params = null, $format = 'json' )
		{
			$_resource = $resource;
			$count = $this->_checkResource( $resource );
			if ( is_numeric( $idBunch ) ) {
				$this->_addResourceId( $resource, $idBunch );
			} else if ( is_array( $idBunch ) ) {
				$this->_addResourceId( $resource, $idBunch['id'] );
				$this->_addResourceSubId( $resource, $idBunch['subId'] );
			}
			$url = $this->_formGetResource( $resource, $count, $format, $params );
			$response = $this->request( 'get', $url, $format );
			return $this->_getMethodResponse( $_resource, $response, $idBunch );
		}

		/**
		 * Forms resorce url part for get method.
		 * @param string $resource - resource name.
		 * @param string $count - count part.
		 * @param string $format - format.
		 * @param array $params - filter params.
		 * @return string
		 */
		protected function _formGetResource( $resource, $count, $format, $params )
		{
			$url = "{$resource}{$count}.{$format}";
			if ( $params ) {
				$url .= '?' . http_build_query( $params );
			}
			return $url;
		}

		/**
		 * Creates new api resource item/items by means of POST method.
		 * @param string $resource - resource name.
		 * @param integer $id - main item id.
		 * @param array $fields - item fields.
		 * @param string $format - request/response format.
		 * @return mixed
		 */
		public function post( $resource, $id = null, $fields = null, $format = 'json' )
		{
			$this->_checkResource( $resource );
			if ( $id ) $this->_addResourceId( $resource, $id );
			$url = $this->_formPostResource( $resource, $format );
			$response = $this->request( 'post', $url, $format, $fields );
			return $response;
		}

		/**
		 * Forms resorce url part for post method.
		 * @param string $resource - resource name.
		 * @param string $format - format.
		 * @return string
		 */
		protected function _formPostResource( $resource, $format )
		{
			$url = "{$resource}.{$format}";
			return $url;
		}

		/**
		 * Updates api resource item/items by means of PUT method.
		 * @param string $resource - resource name.
		 * @param integer $id - main item id.
		 * @param array $fields - item fields.
		 * @param string $format - request/response format.
		 * @return mixed
		 */
		public function put( $resource, $id, $fields = null, $format = 'json' )
		{
			$this->_checkResource( $resource );
			$this->_addResourceId( $resource, $id );
			$url = $this->_formPutResource( $resource, $format );
			$response = $this->request( 'put', $url, $format, $fields );
			return $response;
		}

		/**
		 * Forms resorce url part for put method.
		 * @param string $resource - resource name.
		 * @param string $format - format.
		 * @return string
		 */
		protected function _formPutResource( $resource, $format )
		{
			$url = "{$resource}.{$format}";
			return $url;
		}

		/**
		 * Deletes api resource item/items by means of DELETE method.
		 * @param string $resource - resource name.
		 * @param integer $id - main item id.
		 * @param string $format - request/response format.
		 * @return mixed
		 */
		public function delete( $resource, $id = null, $format = 'json' )
		{
			$this->_checkResource( $resource );
			$this->_addResourceId( $resource, $id );
			$url = $this->_formDeleteResource( $resource, $format );
			$response = $this->request( 'delete', $url, $format );
			return $response;
		}

		/**
		 * Forms resorce url part for delete method.
		 * @param string $resource - resource name.
		 * @param string $format - format.
		 * @return string
		 */
		protected function _formDeleteResource( $resource, $format )
		{
			$url = "{$resource}.{$format}";
			return $url;
		}

		/**
		 * Server ping.
		 */
		abstract public function ping();

		/**
		 * Checks given resource.
		 * @param string $resource - resource name.
		 * @throws Exception
		 */
		protected function _checkResource( &$resource )
		{
			$count = '';
			if ( strstr( $resource, '/count' ) ) {
				$resource = str_replace( '/count', '', $resource );
				$count = '/count';
			}
			if ( !in_array( $resource, $this->apiResouces ) ) {
				throw new Exception(
					$this->_message( 'Wrong resource was given' )
				);
			}
			return $count;
		}

		/**
		 * Adds id to resource.
		 * @param string $resource - resorce name.
		 * @param integer $id - main item id.
		 */
		protected function _addResourceId( &$resource, $id )
		{
			if ( $id ) {
				if ( strstr( $resource, '/' ) ) {
					$resource = str_replace( '/', '/'. $id .'/', $resource );
				} else {
					$resource .= '/' . $id;
				}
			}
		}

		/**
		 * Adds sub-id to resource.
		 * @param string $resource - resorce name.
		 * @param integer $subId - sub-item id.
		 */
		protected function _addResourceSubId( &$resource, $subId )
		{
			if ( $subId ) {
				if ( strstr( $resource, '/' ) ) {
					$resource .= '/' . $subId;
				}
			}
		}

		/**
		 * Returns a message in full format.
		 * @param string $message - simple message.
		 * @return string
		 */
		protected function _message( $message ) {
			return $this->apiName .' API: '. $message .'.';
		}
	}
