<?php
	/**
	 * BigCommerce plugins.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package BigCommerce
	 * @version 1.1.0
	 */
	class Bigcommerce_PluginController extends D_Controller_InnerAuth_Plugin
	{
		protected $_platform = 'Bigcommerce';

		/**
		 * @internal Overrode
		 */
		public function init()
		{
			// Load a plugin.
			if ( $id = $this->_getParam( 'id' ) ) {
				$plugin = Table::_( 'plugins' )->get( $id );
			} else {
				$plugin = Table::_( 'plugins' )->getPlugin(
					base64_decode( $this->_getParam( 'plugin' ) ),
					$this->_platform
				);
			}
			// Validate an user.
			$action = $this->_request->getActionName();
			if ( !in_array( $action, array ( 'index', 'uninstall' ) ) ) {
				$this->_user = $this->getHelper( $this->_platform )
					->validateUser( $plugin->name );
				if ( !$this->_user ) {
// tmp code @todo.
return $this->getHelper( 'Redirector' )
	->gotoSimple( 'index', 'auth', strtolower( $this->_platform ), array (
		'plugin' => $this->_getParam( 'plugin' )
	) );
					/*
					throw new Exception( 'No user was found.' );
					*/
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
			$code = $this->_getParam( 'code' );
			$payload = $this->_getParam( 'signed_payload' );
			$context = $this->_getParam( 'context' );
			$plugin = Table::_( 'plugins' )->get(
				$this->_getParam( 'id' )
			);
			// Authorize.
			$details = Table::_( 'pluginDetails' )->get( $plugin->id );
			if ( $code ) {
				$token = $this->getHelper( 'Bigcommerce' )
					->requestToken( $plugin->id, $code, $context );
				// Get shop details.
				$hash = str_replace( 'stores/', '', $context );
				$response = $this->getHelper( 'BigcommerceApi' )
					->initialize( $hash, $details->client_id, $token )
					->get( 'store' )
					;
				// TODO remove code for old shops support when all shops are reinstalled.
				$domain = sprintf( $config->plugin->bigcommerce->baseShopDomain, $hash );
				if ( !$shop = Table::_( 'shops' )->getByEmail( $response->admin_email, $this->_platform ) ) {
					$shop = Table::_( 'shops' )->establish(
						$domain, 'bigcommerce', $response->admin_email
					);
				}
				$shop->name = $domain;
				$shop->custom_name = $response->domain;
				$shop->save();
				// Store redirect data in session.
				$session = new Zend_Session_Namespace( 'pc' );
				$session->token = $token;
				$session->shop = $shop->name;
				$session->hash = $hash;
				// Install a plugin if it is needed.
				if ( !Table::_( 'plugins' )->isInstalled( $shop->id, $plugin->id ) ) {
					// Create an user.
					if ( !$user = Table::_( 'users' )->getUserByShop( $shop->id ) ) {
						$userId = Table::_( 'users' )->insert( array (
							'shop_id' => $shop->id,
							'platform' => 'bigcommerce',
							'name' => $response->name,
							'password' => mcrypt_encrypt(
								MCRYPT_DES,
								$config->crypt->key,
								crc32( $response->name ),
								MCRYPT_MODE_ECB
							)
						) );
						$user = Table::_( 'users' )->get( $userId );
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
				// Old scheme reinstallation. TODO remove when all shops are reinstalled.
				else {
					$user = Table::_( 'users' )->getUserByShop( $shop->id );
					// Credentials.
					Table::_( 'credentials' )->update( array (
						'api_key' => $token
					), array (
						'user_id = ?' => $user->id,
						'plugin_id = ?' => $plugin->id
					) );
					// Go to plugin.
					$this->getHelper( $this->_platform )
						->setUserCookie( $plugin->name, $user->id );
					$this->getHelper( $this->_platform )
						->gotoPlugin( $plugin->name, 'install' );
				}
			}
			else {
				$payload = $this->getHelper( 'Bigcommerce' )
					->verifyPayload( $payload, $details->client_secret );
				$shop = Table::_( 'shops' )->getByHash( $payload['store_hash'] );
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
				->gotoPlugin( $plugin->name, $this->_getParam( 'url' ) );
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
			// Go to plugin.
			$this->getHelper( $this->_platform )
				->gotoPlugin( $plugin->name, 'install' );
		}

		/**
		 * @internal Overrode
		 * @internal HTTP action.
		 */
		public function uninstallAction()
		{
			$this->getHelper( 'Layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			// Parameters.
			$payload = $this->_getParam( 'signed_payload' );
			$plugin = Table::_( 'plugins' )->get(
				$this->_getParam( 'id' )
			);
			// Uninstall.
			$details = Table::_( 'pluginDetails' )->get( $plugin->id );
			$payload = $this->getHelper( 'Bigcommerce' )
				->verifyPayload( $payload, $details->client_secret );
			if ( !$payload ) {
				Zend_Registry::get( 'logger' )
					->error( 'invalid payload' );
				return;
			}
			$shop = Table::_( 'shops' )->getByHash( $payload['store_hash'] );
			$user = Table::_( 'users' )->getUserByShop( $shop->id );
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
			// Call a plugin action.
			try {
				$url = $this->getHelper( $this->_platform )
					->getHomeUrl( $plugin->name ) .'/app/uninstall/'. $user->id;
				$client = new Zend_Http_Client( $url, array () );
				$client->request();
			}
			catch ( Exception $ex ) {
				Zend_Registry::get( 'logger' )
					->error( 'plugin uninstall action is unavailable' );
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
	}
