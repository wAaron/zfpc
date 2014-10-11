<?php
/**
 *  Processing tasks by cron
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Default
 * @version 1.0.2
 */
class Webhooks_CronController extends D_Webhooks_Controller_Abstract
{
	/**
	 * list of ids of inc webhooks which we should to delete
	 */
	protected $_processedIncIds = array();

	/**
	 * prepare incoming webhooks to send to apps
	 */
	public function prepareWebhooksAction()
	{
		// Start cron.
		$this->getHelper( 'admin' )
			->startCronTask( 'pc-prepare-webhooks' );

		//verify webhooks (shopify only at the moment)
		$this->_verifyWebhooks();

		//prepare outcoming webhooks list
		$this->_makeOutWebhooks();

		//clear webhooks which just are unnecessary for us
		$this->_clearProcessed();

		// Stop cron.
		$this->getHelper( 'admin' )
			->stopCronTask( 'pc-prepare-webhooks' );
	}

	/**
	 * verify all new incoming webhooks using hmac value
	 */
	protected function _verifyWebhooks()
	{
		$incWebhooks = Table::_( 'webhooksInc' )->getAllPending();

		foreach( $incWebhooks as $incWebhook ) {
			try {
				//check webhook
				$isVerified = Table::_( 'pluginDetails' )
					->get( $incWebhook->plugin_id )
					->verifyWebhook( $incWebhook->hmac, $incWebhook->body );

				if( !$isVerified )
					throw new Exception( 'HMAC header has been not verified.' );

				$incWebhook->status = 'verified';

			} catch( Exception $e ) {
				$incWebhook->status = 'error';
				$this->_logger->err( 'Error. IncWebhook #' . $incWebhook->id . ': ' . $e->getMessage() );
			}
			//save changed status
			$incWebhook->save();
		}
	}

	/**
	 * prepare incoming webhooks to resend to apps
	 */
	protected function _makeOutWebhooks()
	{
		$webhooksOut = Table::_( 'webhooksInc' )->getForOut( Table::_( 'webhooks' ) );
		$webhooksOutTable = Table::_( 'webhooksOut' );

		foreach( $webhooksOut as $webhookData ) {
			try {
				//shopify app/uninstall hook should be send only for 1 plugin
				//todo get out SH check
				if( $webhookData['topic'] != 'app/uninstalled'
					|| $webhookData['registered_plugin_id'] == $webhookData['plugin_id']
				){

					$webhooksOutTable->createRow( array(
						'callback_url' => $webhookData['callback_url'],
						'headers' => $webhookData['headers'],
						'body' => $webhookData['body'],
						'platform_id' => $webhookData['platform_id'],
						'income_time' => $webhookData['create_time'],
						'plugin_id' => $webhookData['registered_plugin_id'],
					) )
						->save();
				}
				//mark inc webhook as processed, which one we can to delete
				$this->_processedIncIds[] = $webhookData['id'];

			} catch( Exception $e ) {
				$this->_logger->err( 'Error. IncWebhook #' . $webhookData['id'] . ': ' . $e->getMessage() );
			}
		}
	}

	/**
	 * clear inc webhooks table
	 */
	protected function _clearProcessed()
	{
		//delete inc webhooks, which we just have processed
		if( $this->_processedIncIds ){
			Table::_( 'webhooksInc' )->clearProcessed( $this->_processedIncIds );
		}
	}


	/**
	 * send webhooks to apps
	 */
	public function sendAction()
	{
		// Start cron.
		$this->getHelper( 'admin' )
			->startCronTask( 'pc-send-webhooks' );

		//get webhooks to send
		$webhooksOut = Table::_( 'webhooksOut' )->getAllPending();
		$webhooksStatsTable = Table::_( 'webhooksStats' );

		foreach( $webhooksOut as $webhook ) {
			try {
				$client = new Zend_Http_Client( $webhook->callback_url, array(
					'adapter' => 'Zend_Http_Client_Adapter_Curl',
					'strict' => false
				) );

				//set headers, body and go
				$client->setHeaders( json_decode( $webhook->headers, true ) )
					->setRawData( $webhook->body, 'application/json' );
				//make request
				$response = $client->request( "POST" );

				//check has the app received webhook
				if( $response->getStatus() != 200 )
					throw new Exception( 'Http response code: ' . $response->getStatus() );

				//mark as sent
				$webhook->status = 'sent';
				$webhook->sent_time = date( LOCAL_DATETIME_FORMAT );
				//increment stats
				$webhooksStatsTable->increment( $webhook->platform_id, $webhook->plugin_id );

			} catch( Exception $e ) {
				$webhook->status = 'error';
				$webhook->message = $e->getMessage();
				$this->_logger->err( 'Error. OutWebhook #' . $webhook['id'] . ': ' . $e->getMessage() );
			}
			//save status
			$webhook->save();
		}

		// Stop cron.
		$this->getHelper( 'admin' )
			->stopCronTask( 'pc-send-webhooks' );
	}


	/**
	 * clear db table webhooksOut
	 */
	public function clearOutAction()
	{
		// Start cron.
		$this->getHelper( 'admin' )
			->startCronTask( 'pc-clear-webhooks' );

		$config = Config::getInstance();
		//delete old webhooks
		$deletedQty = Table::_( 'webhooksOut' )->deleteOldItems( $config->webhooks->expiredDays, array( 'sent', 'error' ) );
		if( $deletedQty ){
			$this->_logger->info( 'Clear webhooks process completed. Deleted items: ' . $deletedQty );
		}

		// Stop cron.
		$this->getHelper( 'admin' )
			->stopCronTask( 'pc-clear-webhooks' );
	}


	/**
	 * check each registered webhook via api and reinstall if we need
	 */
	public function checkAction()
	{
		// Start cron.
		$this->getHelper( 'admin' )
			->startCronTask( 'pc-check-webhooks' );

		$webhooks = Table::_( 'webhooks' )->getAllRegisteredWebhooks();
		foreach( $webhooks as $webhook ) {
			try {
				//get api client
				$apiClient = $this->_getApiClient( $webhook->plugin_id, $webhook->user_id );

				//check is webhook registered
				if( !$apiClient->get( 'webhooks', $webhook->webhook_id ) )
					throw new Exception( 'Empty response' );

			} catch( Exception $e ) {
				$this->_logger->err( 'Error. Webhook #' . $webhook->id . ' is not registered.' );
				//reinstall webhook
				$this->_checkWebhook( $webhook, $apiClient );
			}
		}
		// Stop cron.
		$this->getHelper( 'admin' )
			->stopCronTask( 'pc-check-webhooks' );
	}


	/**
	 * reinstall webhook or delete it and install for the first child
	 * @param $webhook
	 * @param $apiClient
	 * @param string $platform
	 */
	protected function _checkWebhook( $webhook, $apiClient, $platform = 'shopify' )
	{
		try {
			$callbackUrl = $this->_getCallbackUrl( $webhook->plugin_id, $platform );
			//try to reinstall webhook
			if( !$webhook->install( $apiClient, $callbackUrl, $platform ) )
				throw new Exception( 'Plugin #' . $webhook->plugin_id . ' has been deleted for domain ' . $webhook->domain );

		} catch( Exception $e ) {
			$this->_logger->err( 'Error. ' . $e->getMessage() );

			//install webhook for the first child if exists
			$childWebhook = Table::_( 'webhooks' )->getByParentId( $webhook->id );
			if( $childWebhook && !$childWebhook->install( $apiClient, $callbackUrl, $platform ) ){
				$this->_logger->err( 'Error. Child Webhook #' . $childWebhook->id . ' has not been installed.' );
			}
			//delete the old one
			$webhook->delete();
		}
	}


	/**
	 * reinstall shopify webhooks from the apps to PC
	 */
	public function regenerateShopifyWebhooksAction()
	{
		$platformName = 'shopify';
		$pluginName = '8Upsell';
		$platform = Table::_( 'platforms' )->get( $platformName );
		$platformID = $platform ? $platform->id : 0;
		//get plugin id
		$modelPayment = new Payment_Model_Payment();
		$plugin = $modelPayment->getPlugin( $platformName, $pluginName );
		$pluginId = $plugin ? $plugin->id : 0;

		$webhooksTable = Table::_( 'webhooks' );
		$credentialsRows = Table::_( 'credentials' )->getCredentialsForApi( $pluginId, $platformName );
		foreach( $credentialsRows as $shopCredentials ) {
			try {
				if( !$shopCredentials['api_key'] )
					throw new Exception( 'Empty token for ' . $shopCredentials['name'] );

				//get webhooks registered to app
				$apiClient = $this->getHelper( 'ShopifyApi' )
					->initialize( $shopCredentials['name'], $shopCredentials['api_key'] );
				$response = $apiClient->get( 'webhooks' );

				if(!$response){
					throw new Exception('Empty set of old webhooks for '.$shopCredentials['name']);
				}

				//reinstall webhooks from app to pc side
				foreach( $response as $oldWebhook ) {
					//check is webhook just registered to pc
					if( strpos( $oldWebhook->address, 'webhooks/accept/shopify' ) == false ){
						$this->_logger->debug( $oldWebhook->address.' '.$oldWebhook->topic. ' '.$oldWebhook->id. ' '.$shopCredentials['name']. ' '.$shopCredentials['api_key'] );

						/*
						$newWebhook = $webhooksTable->addItem( array(
							'plugin_id' => $shopCredentials['plugin_id'],
							'platform_id' => $platformID,
							'callback_url' => $oldWebhook->address,
							'domain' => $shopCredentials['name'],
							'topic' => $oldWebhook->topic,
							'user_id' => $shopCredentials['user_id']
						) );

						//at first remove app webhook
						$apiClient->delete( 'webhooks', $oldWebhook->id );

						//if we need to register this webhook
						if( $newWebhook->registered ){
							$pcCallbackUrl = $this->_getCallbackUrl( $shopCredentials['plugin_id'], $platformName );
							if( !$newWebhook->install( $apiClient, $pcCallbackUrl, $platformName ) ){
								throw new Exception( 'Webhook has not been reinstalled to pc from plugin ' . $shopCredentials['plugin_id']
									. ' domain ' . $shopCredentials['name'] );
							}
						}
						*/
					}
				}
			} catch( Exception $e ) {
				$this->_logger->err( 'Error. '.$shopCredentials['name'].' ' . $e->getMessage() );
			}
		}
	}


	/**
	 * reinstall shopify webhooks from the apps to PC
	 */
	private function updateShopifyWebhooksAction()
	{
		$platformName = 'shopify';
		$pluginName = 'Upsell by Email';
		$platform = Table::_( 'platforms' )->get( $platformName );
		$platformID = $platform ? $platform->id : 0;
		//get plugin id
		$modelPayment = new Payment_Model_Payment();
		$plugin = $modelPayment->getPlugin( $platformName, $pluginName );
		$pluginId = $plugin ? $plugin->id : 0;

		$webhooksTable = Table::_( 'webhooks' );
		$credentialsRows = Table::_( 'credentials' )->getCredentialsForApi( $pluginId, $platformName );
		foreach( $credentialsRows as $shopCredentials ) {
			try {
				if( !$shopCredentials['api_key'] )
					throw new Exception( 'Empty token for ' . $shopCredentials['name'] );

				//get webhooks registered to app
				$apiClient = $this->getHelper( 'ShopifyApi' )
					->initialize( $shopCredentials['name'], $shopCredentials['api_key'] );
				$response = $apiClient->get( 'webhooks' );

				if(!$response){
					throw new Exception('Empty set of old webhooks for '.$shopCredentials['name']);
				}


				//reinstall webhooks from app to pc side
				foreach( $response as $oldWebhook ) {
					//check is webhook just registered to pc
					if( strpos( $oldWebhook->address, 'webhooks/accept/shopify' ) == false ){
						//$this->_logger->debug( 'UPDATE '.$oldWebhook->address.' '.$oldWebhook->topic. ' '.$oldWebhook->id. ' '.$shopCredentials['name']. ' '.$shopCredentials['api_key'] );

						$pcCallbackUrl = $this->_getCallbackUrl( $shopCredentials['plugin_id'], $platformName );
						if( !$apiClient->put('webhooks',$oldWebhook->id,array(
							'webhook' => array(
								'address' => $pcCallbackUrl
							)
						) ) ){
							$this->_logger->debug( 'UPDATED '.$oldWebhook->address.' '.$oldWebhook->topic. ' '.$oldWebhook->id. ' '.$shopCredentials['name']);
							throw new Exception( 'Webhook has not been updated' . $shopCredentials['plugin_id']
								. ' domain ' . $shopCredentials['name'] );
						}

					}else{
						$this->_logger->debug( 'DELETED '.$oldWebhook->address.' '.$oldWebhook->topic. ' '.$oldWebhook->id. ' '.$shopCredentials['name'] );
						$apiClient->delete( 'webhooks', $oldWebhook->id );
					}
				}


			} catch( Exception $e ) {
				$this->_logger->err( 'Error. '.$shopCredentials['name'].' ' . $e->getMessage() );
			}
		}
	}



	/**
	 * reinstall shopify webhooks from the apps to PC
	 */
	private function checkBugAction()
	{
		$platformName = 'shopify';
		$pluginName = 'Upsell by Email';
		$platform = Table::_( 'platforms' )->get( $platformName );
		$platformID = $platform ? $platform->id : 0;
		//get plugin id
		$modelPayment = new Payment_Model_Payment();
		$plugin = $modelPayment->getPlugin( $platformName, $pluginName );
		$pluginId = $plugin ? $plugin->id : 0;

		$credentialsRows = Table::_( 'credentials' )->getCredentialsForApi( $pluginId, $platformName );
		$putCount = 0;
		foreach( $credentialsRows as $shopCredentials ) {
			try {
				if( !$shopCredentials['api_key'] )
					throw new Exception( 'Empty token for ' . $shopCredentials['name'] );

				//get webhooks registered to app
				$apiClient = $this->getHelper( 'ShopifyApi' )
					->initialize( $shopCredentials['name'], $shopCredentials['api_key'] );
				$response = $apiClient->get( 'webhooks' );

				if(!$response){
					throw new Exception('Empty set of old webhooks for '.$shopCredentials['name']);
				}

				//reinstall webhooks from app to pc side
				foreach( $response as $oldWebhook ) {
					//check is webhook just registered to pc
					if( strpos( $oldWebhook->address, 'webhooks/accept/shopify' ) != false ){
						$pcCallbackUrl = $this->_getCallbackUrl( $shopCredentials['plugin_id'], $platformName );
						if( !$apiClient->put('webhooks',$oldWebhook->id,array(
							'webhook' => array(
								'address' => $pcCallbackUrl
							)
						) ) ){
							$putCount++;
							throw new Exception( 'Webhook has not been updated' . $shopCredentials['plugin_id']
								. ' domain ' . $shopCredentials['name'] );
						}
					}
				}

			} catch( Exception $e ) {
				$this->_logger->err( 'Error. '.$shopCredentials['name'].' ' . $e->getMessage() );
			}

			if($putCount>3){
				exit($shopCredentials['name']);
			}
		}
	}

}
