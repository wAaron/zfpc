<?php
	/**
	 * Shopify plugins.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Shopify
	 * @version 1.1.15
	 */
	class Shopify_PluginController extends D_Controller_InnerAuth_Plugin
	{
		protected $_platform = 'Shopify';

		/**
		 * @internal Overrode
		 */
		public function init()
		{
			// Load a plugin.
			$action = $this->_request->getActionName();
			if ( $action == 'index' ) {
				$plugin = Table::_( 'plugins' )->get(
					$this->_getParam( 'id' )
				);
			} else {
				$plugin = ( $action == 'uninstall' ) ? $this->_getParam( 'plugin' ) : base64_decode( $this->_getParam( 'plugin' ) );
				$plugin = Model::_( 'payment' )->getPlugin( $this->_platform, $plugin );
			}
			// Validate an user.
			if ( !in_array( $action, array ( 'index', 'uninstall' ) ) ) {
				$this->_user = $this->getHelper( $this->_platform )
					->validateUser( $plugin->name );
				if ( !$this->_user ) {
					throw new Exception( 'No user was found.' );
				}
			}
			// View.
			$this->view->headTitle(
				$this->getHelper( 'Platform' )
					->headTitle( $this->_platform, $plugin->name )
			);
			$this->view->platform = strtolower( $this->_platform );
			$this->view->setScriptPath(
				ROOT_PATH . '/library/D/Platform/InnerAuth/scripts/'
			);
		}

		/**
		 * Entrance to a plugin.
		 * For the first time, a plugin will be installed before.
		 * @internal HTTP action.
		 */
		public function indexAction()
		{
			$this->getHelper( 'Layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			// Parameters.
			$shopName = $this->_getParam( 'shop' );
			$code = $this->_getParam( 'code' );
			$plugin = Table::_( 'plugins' )->get(
				$this->_getParam( 'id' )
			);
			// Authorize.
			if ( !$code ) {
				return $this->getHelper( 'Shopify' )
					->authorize( $shopName, $plugin->id );
			}
			$token = $this->getHelper( 'Shopify' )
				->requestToken( $shopName, $plugin->id, $code );
			// Get shop details.
			$response = $this->getHelper( 'ShopifyApi' )
				->initialize( $shopName, $token )
				->get( 'shop' )
				;
			$shop = Table::_( 'shops' )->establish(
				$response->myshopify_domain, 'shopify', $response->email
			);
			$shop->custom_name = $response->domain;
			$shop->save();
			// Install a plugin if it is needed.
			if ( !Table::_( 'plugins' )->isInstalled( $shop->id, $plugin->id ) ) {
				// Create an user.
				if ( !$user = Table::_( 'users' )->getUserByShop( $shop->id ) ) {
					Table::_( 'users' )->insert( array (
						'shop_id' => $shop->id,
						'platform' => 'shopify',
						'name' => $response->shop_owner,
						'password' => mcrypt_encrypt(
							MCRYPT_DES,
							$config->crypt->key,
							crc32( $response->shop_owner ),
							MCRYPT_MODE_ECB
						)
					) );
					$user = Table::_( 'users' )->get(
						Table::_( 'users' )->getAdapter()->lastInsertId()
					);
				}
				// Set cookie.
				$this->getHelper( $this->_platform )
					->setUserCookie( $plugin->name, $user->id );
				// Go to installation.
				return $this->getHelper( 'Redirector' )
					->gotoSimple( 'install', null, null, array (
						'plugin' => base64_encode( $plugin->name ),
						'token' => $token
					) );
			}
			// Update user and instance last login date.
			$dbExpr = new Zend_Db_Expr( 'NOW()' );
			$user = Table::_( 'users' )->getUserByShop( $shop->id );
			$instance = Table::_( 'plugins' )->getInstance( $user->shop_id, $plugin->name );
			$user->last_login = $instance->last_login = $dbExpr;
			$user->save();
			$instance->save();
			// Make an user logged in.
			$this->getHelper( $this->_platform )
				->setUserCookie( $plugin->name, $user->id );
			$this->getHelper( $this->_platform )
				->setInstalledCookie( $plugin->name, $user->id );
			$this->getHelper( $this->_platform )
				->gotoPlugin( $plugin->name );
		}

		/**
		 * @internal Overrode
		 */
		protected function _platformSpecificInstall( $instance, $shop, $plugin )
		{
			// Credentials.
			$token = $this->_getParam( 'token' );
			if ( $token && !$this->_request->isPost() ) {
				Table::_( 'credentials' )->update( array (
					'api_key' => $token
				), array (
					'user_id = ?' => $this->_user->id,
					'plugin_id = ?' => $plugin->id
				) );
			}
			// Skip if current plan is free.
			$currentPlan = Table::_( 'plans' )->getPlan(
				$plugin->id, $instance->getSetting( 'current plan' )->value
			);
			if ( $currentPlan->isFree() ) {
				$this->getHelper( $this->_platform )
					->gotoPlugin( $plugin->name, 'install' );
			}
			// Prepare charge data.
			$products = $currentPlan->getProducts();
			$url = $this->view->serverUrl(
				$this->view->url( array (
					'module' => 'payment',
					'controller' => 'shopify',
					'action' => 'do-charge',
					'shopify_form' => 'main',
					'shop' => base64_encode( $shop->name ),
					'plugin' => base64_encode( $plugin->name ),
					'plan' => base64_encode( $currentPlan->getName() ),
					'product_id' => $products->getRow( 0 )->id,
					'charge_name' => base64_encode(
						Zend_Registry::get( 'translate' )
							->_( 'pay for shopify app' )
					),
					'charge_price' => $products->getRow( 0 )->price,
					'charge_return_url' => base64_encode(
						$this->view->serverUrl(
							$this->view->baseUrl(
								'/payment/shopify/finish-charge'
							)
						)
					),
					'recurrent' => '1',
					'install' => '1'
				), 'default', true )
			);
			// Go to Shopify.
			return $this->getHelper( 'Redirector' )
				->gotoUrl( $url );
		}

		/**
		 * @internal Overrode
		 * @internal CLI action.
		 * @internal [ platfrom ] [ user ] [ plugin ]
		 */
		public function uninstallAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			// Parameters.
			$platform = $this->_getParam( 'platform' );
			$shop = Table::_( 'shops' )->get( $this->_getParam( 'shop' ) );
			$plugin = Model::_( 'payment' )->getPlugin( $platform, $this->_getParam( 'plugin' ) );
			$user = Table::_( 'users' )->getUserByShop( $shop->id );
			// Uninstall.
			Table::_( 'plugins' )->uninstall( $shop->id, $plugin->id );
			$where = "`user_id` = {$user->id} AND `external_id` = {$plugin->id} AND `name` = 'update_news'";
			Table::_( 'userSettings' )->update( array (
				'value' => '0'
			), $where );
			if ( !count( Table::_( 'credentials' )->getForUser( $user->id ) ) ) {
				$where = "`user_id` = {$user->id} AND `name` = 'product_news'";
				Table::_( 'userSettings' )->update( array (
					'value' => '0'
				), $where );
			}
			Model::_( 'payment' )->deleteInstanceCharges( $shop->id, $plugin->id );
			// Delete instance webhooks. TODO delete later when events are implemented.
			try {
				$client = new Zend_Http_Client(
					$config->plugin->center->baseUrl .'webhooks/app/delete/user/'. $user->id .'/plugin/'. $plugin->id,
					array (
						'adapter' => 'Zend_Http_Client_Adapter_Curl'
					)
				);
				$client->request( 'GET' );
			}
			catch ( Exception $e ) { // TODO lang.
				Zend_Registry::get( 'taskLogger' )
					->err( 'Error. Webhooks have not been deleted for user '.$user->id.' plugin '.$plugin->id );
			}
			// Send notification.
			$platform = Table::_( 'platforms' )->get( $this->_platform );
			$enabled = Model::_( 'settings' )->getPlatformValue( $platform->id, 'uninstallation', 'bool' );
			if ( $enabled ) {
				// Prepare body.
				$helperEmail = $this->getHelper( 'email' );
				$path = array_shift( $this->view->getScriptPaths() ) . 'plugin/notification/';
				$name = $helperEmail->formTemplateName( $plugin, $path, 'uninstalled.phtml' );
				$body = $this->_replacePlaceholders(
					$this->view->render( "plugin/notification/{$name}" ),
					$plugin, $user
				);
				// Send email.
				$helperEmail->setSubject( $name )
					->getMailer()
					->clearRecipients()
					->addTo( $shop->email )
					->setBodyHtml( $body )
					->send()
					;
			}
		}

		/**
		 * @internal Overrode
		 */
		protected function _platformSpecificConfigure(
			$instance, $shop, $plugin,
			$PC_currentPlanName, $PC_planName,
			$currentPlanName, $planName
		) {
			$charge = Model::_( 'payment' )->getLastCharge( $shop->id, $plugin->id );
			if ( $currentPlanName && ( $currentPlanName != $planName ) && $charge ) {
				Model::_( 'payment' )->setting( $shop, $plugin, 'plan changed', 1 );
				// Recreate recurring charge.
				return $this->getHelper( 'Redirector' )
					->gotoRoute( array (
						'module' => 'payment',
						'controller' => 'shopify',
						'action' => 'recreate-charge',
						'charge_id' => $charge->id,
					), 'default', true );
			}
		}
	}
