<?php
/**
 *  App requests acceptor.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Default
 * @version 1.0.1
 */
class Webhooks_AppController extends D_Webhooks_Controller_Abstract
{
	/**
	 * register webhooks
	 */
	public function registerAction()
	{
		try {
			$webhooksToSave = $this->_prepareRequestData( $this->_getAllParams() );

			//save webhooks to local db
			$savedWebhooks = array();
			foreach( $webhooksToSave as $webhookData ) {
				$savedWebhooks[] = Table::_( 'webhooks' )->addItem( $webhookData );
			}

			//check is webbhooks have been saved
			if( !$firstWebhook = reset( $savedWebhooks ) )
				throw new Exception( 'No one webhook have not been saved into db (user_id - '
					.$this->_getParam( 'user' ).' plugin - '.$this->_getParam( 'plugin' ).')' );

			//create via platform api
			if( !$this->_registerWebhooks( $firstWebhook->user_id, $firstWebhook->plugin_id, $this->_getParam( 'platform' ) ) )
				throw new Exception( 'Not all webhooks have not been created via api (user_id - '
					.$firstWebhook->user_id.' plugin_id - '.$firstWebhook->plugin_id.')' );

			//everything is ok
			$this->_response->setHttpResponseCode( 200 )
				->sendResponse();

		} catch( Exception $e ) {
			$this->_logger->err( 'An error occurred: ' . $e->getMessage() );
			$this->_response->setHttpResponseCode( 300 )
				->sendResponse();
		}
	}

	/**
	 * prepare request data to suitable format
	 * @param $data
	 * @return mixed
	 */
	protected function _prepareRequestData( $data )
	{
		//get platform id
		$platform = Table::_( 'platforms' )->get( $data['platform'] );
		$platformID = $platform ? $platform->id : 0;

		//get plugin id
		$modelPayment = new Payment_Model_Payment();
		$plugin = $modelPayment->getPlugin( $data['platform'], $data['plugin'] );
		$pluginID = $plugin ? $plugin->id : 0;

		//get webhooks data
		$data['webhooksData'] = json_decode( base64_decode( $data['webhooksData'] ), true );

		$webhooksToSave = array();
		foreach( $data['webhooksData'] as $webhookData ) {
			$webhooksToSave[] = array(
				'plugin_id' => $pluginID,
				'platform_id' => $platformID,
				'user_id' => $data['user'],
				'domain' => $data['shop'],
				'callback_url' => $webhookData['callbackUrl'],
				'topic' => $webhookData['topic']
			);
		}

		return $webhooksToSave;
	}

	/**
	 * register webhooks for specific platform
	 * @param $data
	 * @return null|void response from platform api
	 */
	protected function _registerWebhooks( $userID, $pluginID, $platformName = 'shopify' )
	{
		$webhooks = Table::_( 'webhooks' )->getWebhooksToInstall( $pluginID, $userID );
		//install webhooks
		foreach( $webhooks as $webhook ) {
			try {
				//get api client
				$apiClient = $this->_getApiClient( $webhook->plugin_id, $webhook->user_id );
				$callbackUrl = $this->_getCallbackUrl( $webhook->plugin_id, $platformName );

				if( !$webhook->install( $apiClient, $callbackUrl, $platformName ) )
					throw new Exception( 'Webhook #' . $webhook->id . ' has not been registered.' );

			} catch( Exception $e ) {
				$this->_logger->err( 'Error. ' . $e->getMessage() );
				$result = false;
			}
		}

		return isset( $result ) ? false : true;
	}


	/**
	 * delete webhooks for the app
	 */
	public function deleteAction()
	{
		$plugin = Table::_( 'plugins' )->get( $this->_getParam( 'plugin', 0 ) );
		$webhooks = Table::_( 'webhooks' )
			->getByPlugin( $plugin->id, $this->_getParam( 'user', 0 ) );

		foreach( $webhooks as $webhook ) {
			//install webhook for the first child if exists
			$childWebhook = Table::_( 'webhooks' )->getByParentId( $webhook->id );
			if( $childWebhook ){
				$apiClient = $this->_getApiClient( $childWebhook->plugin_id, $childWebhook->user_id );
				$callbackUrl = $this->_getCallbackUrl( $childWebhook->plugin_id, $plugin->platform );

				if( !$childWebhook->install( $apiClient, $callbackUrl, $plugin->platform ) ){
					$this->_logger->err( 'Error. Child Webhook #' . $childWebhook->id . ' has not been installed.' );
				}
			}

			//delete the old one
			$webhook->delete();
		}
	}
}
