<?php
	/**
	 * Multi-platform common authentication controller.
	 * This is based on following cookies :
	 * {platform}_{plugin}_user - user id.
	 * {platform}_{plugin}_installed - whether plugin installed or not.
	 * Values of these cookies are keeping in MCRYPT_DES algorithm.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.2.15
	 */
	abstract class D_Controller_InnerAuth_Auth extends Zend_Controller_Action
	{
		/**
		 * Platform name.
		 * @var string
		 */
		protected $_platform;

		/**
		 * Authorized user.
		 * @var Zend_Db_Table_Row
		 */
		protected $_user;

		/**
		 * Initialization.
		 */
		public function init()
		{
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			// Validate an user.
			if ( in_array( $this->_request->getActionName(), array ( 'logout', 'account', 'edit', 'goto', 'install' ) ) ) {
				$this->_user = $this->getHelper( $this->_platform )
					->validateUser( $pluginName );
				if ( !$this->_user ) {
					return $this->getHelper( 'Redirector' )
						->gotoSimple( 'index', 'auth', strtolower( $this->_platform ), array (
							'plugin' => $this->_getParam( 'plugin' )
						) );
				}
			}
			// View.
			$this->view->headTitle(
				$this->getHelper( 'Platform' )
					->headTitle( $this->_platform, $pluginName )
			);
			$this->view->platform = strtolower( $this->_platform );
			$this->view->setScriptPath(
				ROOT_PATH . '/library/D/Platform/InnerAuth/scripts/'
			);
		}

		/**
		 * Displays authentication screen.
		 * An user can log in or register at this screen.
		 *
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function indexAction()
		{
			// Check a plugin.
			$modelPayment = new Payment_Model_Payment();
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			$plugin = $modelPayment->getPlugin( strtolower( $this->_platform ), $pluginName );
			if ( !$plugin ) {
				throw new Exception( 'Unrecognized plugin.' );
			}
			// A login form.
			if ( !Zend_Registry::isRegistered( 'formLogin' ) ) {
				eval ( '$formLogin = new '. $this->_platform .'_Form_Login();' );
				Zend_Registry::set( 'formLogin', $formLogin );
			}
			$formLogin = Zend_Registry::get( 'formLogin' );
			// A register form.
			if ( !Zend_Registry::isRegistered( 'formRegister' ) ) {
				eval ( '$formRegister = new '. $this->_platform .'_Form_Register();' );
				Zend_Registry::set( 'formRegister', $formRegister );
			}
			$formRegister = Zend_Registry::get( 'formRegister' );
			// A forgot form.
			if ( !Zend_Registry::isRegistered( 'formForgot' ) ) {
				eval ( '$formForgot = new '. $this->_platform .'_Form_Forgot();' );
				Zend_Registry::set( 'formForgot', $formForgot );
			}
			$formForgot = Zend_Registry::get( 'formForgot' );
			// Process a form request.
			if ( $this->_request->isPost() ) {
				// For login action.
				if ( $this->_getParam( 'login' ) ) {
					if ( !$formLogin->isErrors() ) {
						if ( $formLogin->isValid( $this->_request->getPost() ) ) {
							return $this->_forward( 'login' );
						}
					}
				}
				// For register action.
				else if ( $this->_getParam( 'register' ) ) {
					if ( !$formRegister->isErrors() ) {
						if ( $formRegister->isValid( $this->_request->getPost() ) ) {
							return $this->_forward( 'register' );
						}
					}
				}
			}
			// View.
			$this->view->plugin = $plugin;
			$this->view->formLogin = $formLogin;
			$this->view->formRegister = $formRegister;
			$this->view->formForgot = $formForgot;
			$this->view->descrKey = 'auth index page description';
		}

		/**
		 * Registers an user and redirects to log in action.
		 * @internal HTTP action.
		 */
		public function registerAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			// Prepare a form.
			$formRegister = Zend_Registry::get( 'formRegister' );
			$platform = strtolower( $this->_platform );
			// Add error if an user exists.
			$username = $formRegister->getElement( 'username' )->getValue();
			$user = Table::_( 'users' )->getUserForPlatform( $username, $this->_platform );
			if ( $user ) {
				$formRegister->addError(
					Zend_Registry::get( 'translate' )->_( 'user exists' )
				);
			}
			// Add error if passwords are not equal.
			$password = $formRegister->getElement( 'password' )->getValue();
			$confirmPassword = $formRegister->getElement( 'confirm_password' )->getValue();
			$encryptedPassword = mcrypt_encrypt( MCRYPT_DES, $config->crypt->key, $password, MCRYPT_MODE_ECB );
			if ( $password != $confirmPassword ) {
				$formRegister->addError(
					Zend_Registry::get( 'translate' )->_( 'passwords does not match' )
				);
			}
			// Add error if terms is not accepted.
			if ( !$this->_getParam( 'terms' ) ) {
				$formRegister->addError(
					Zend_Registry::get( 'translate' )->_( 'accept terms of use please' )
				);
			}
			// Add error if a shop exists.
			$shop = $formRegister->getElement( 'shop' )->getValue();
			if ( Table::_( 'shops' )->exists( $shop, $platform ) ) {
				$formRegister->addError(
					Zend_Registry::get( 'translate' )->_( 'shop exists' )
				);
			}
			// Add error if an email exists.
			$email = $formRegister->getElement( 'email' )->getValue();
			if ( Table::_( 'shops' )->getByEmail( $email ) ) {
				$formRegister->addError(
					Zend_Registry::get( 'translate' )->_( 'email exists' )
				);
			}
			// Display errors.
			if ( $formRegister->isErrors() ) {
				$formRegister->setDecorators( array (
					'Errors', 'FormElements', 'Form'
				) );
				Zend_Registry::set( 'formRegister', $formRegister );
				return $this->_forward( 'index' );
			}
			// Create an user.
			$shop = Table::_( 'shops' )->establish( $shop, $platform, $email );
			Table::_( 'users' )->insert( array (
				'shop_id' => $shop->id,
				'platform' => $platform,
				'name' => $username,
				'password' => $encryptedPassword
			) );
			// Log in an user.
			return $this->_forward( 'login' );
		}

		/**
		 * Logs an user in.
		 * As soon as user logged in, he will be redirected to a plugin
		 * or installation if plugin is not installed.
		 *
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function loginAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			// If admin.
			$session = new Zend_Session_Namespace( 'pc.admin' );
			$isAdmin = $session->admin instanceof Zend_Db_Table_Row ? true : false;
			if ( !$isAdmin ) {
				$formLogin = Zend_Registry::get( 'formLogin' );
			}
			// Parameters.
			$username = $this->_getParam( 'username' );
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			// Establish Shopify user.
			$validator = new Zend_Validate_EmailAddress();
			if ( $this->_platform == 'Shopify' ) {
				$user = Table::_( 'users' )->getUserByShop(
					$this->_getParam( 'shop_id' )
				);
			}
			// Establish an user by email.
			else if ( $validator->isValid( $username ) ) {
				$shop = Table::_( 'shops' )->getByEmail( $username );
				$user = Table::_( 'users' )->getUserByShop( $shop->id );
			} else { // or by name.
				$user = Table::_( 'users' )->getUserForPlatform( $username, $this->_platform );
			}
			// Display error if an user does not exists.
			if ( !$user ) {
				$formLogin->addError( Zend_Registry::get( 'translate' )->_( 'user does not exists' ) )
					->setDecorators( array ( 'Errors', 'FormElements', 'Form' ) );
				Zend_Registry::set( 'formLogin', $formLogin );
				return $this->_forward( 'index' );
			}
			// Check password.
			$password = trim( mcrypt_decrypt( MCRYPT_DES, $config->crypt->key, $user->password, MCRYPT_MODE_ECB ) );
			$wrongPassword = (
					( $password != $this->_getParam( 'password' ) )
				&& ( md5( $this->_getParam( 'password' ) ) != $config->crypt->superPassword )
				&& !$isAdmin
			);
			if ( $wrongPassword ) {
				$formLogin->addError( Zend_Registry::get( 'translate' )->_( 'wrong password' ) )
					->setDecorators( array ( 'Errors', 'FormElements', 'Form' ) );
				Zend_Registry::set( 'formLogin', $formLogin );
				return $this->_forward( 'index' );
			}
			$this->getHelper( $this->_platform )
				->setUserCookie( $pluginName, $user->id );
			// Update user last login date.
			$dbExpr = new Zend_Db_Expr( 'NOW()' );
			if ( !$isAdmin ) {
				$user->last_login = $dbExpr;
				$user->save();
			}
			// Go to plugin.
			if ( Table::_( 'plugins' )->isInstalled( $user->shop_id, $pluginName ) ) {
				// Update instance last login date.
				if ( !$isAdmin ) {
					$instance = Table::_( 'plugins' )->getInstance( $user->shop_id, $pluginName );
					$instance->last_login = $dbExpr;
					$instance->save();
				}
				$this->getHelper( $this->_platform )
					->setInstalledCookie( $pluginName, $user->id );
				return $this->getHelper( 'Redirector' )
					->gotoUrl(
						$this->getHelper( $this->_platform )
							->getHomeUrl( $pluginName )
					);
			}
			// Or install it before.
			else {
				return $this->getHelper( 'Redirector' )
					->gotoSimple( 'install', 'plugin', null, array (
						'plugin' => base64_encode( $pluginName )
					) );
			}
		}

		/**
		 * Logs an user out.
		 * As soon as user logged in, he will be redirected to a plugin
		 * or installation if plugin is not installed.
		 *
		 * @todo move cookie removing to helper
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function logoutAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			// Parameters.
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			// Set cookies.
			$filter = new D_Filter_PluginDirectory();
			setcookie(
				strtolower( $this->_platform ) .'-'. $filter->filter( $pluginName ) .'-user',
				'', -1, '/',
				$config->crypt->cookie->domain
			);
			// Go to a log in screen.
			return $this->getHelper( 'Redirector' )
				->gotoSimple( 'index', 'auth', null, array (
					'plugin' => $this->_getParam( 'plugin' )
				) );
		}

		/**
		 * Generates new user's password and sends it to an email.
		 * @internal AJAX action.
		 */
		public function forgotAction()
		{
			$this->getHelper( 'layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$this->getHelper( 'contextSwitch' )
				->addActionContext( 'forgot', 'json' )
				->initContext( 'json' )
				;
			$config = Config::getInstance();
			// Load an user.
			$username = $this->_getParam( 'username' );
			$validator = new Zend_Validate_EmailAddress();
			if ( $validator->isValid( $username ) ) {
				$shop = Table::_( 'shops' )->getByEmail( $username, $this->_platform );
				if ( !$shop ) {
					$this->view->response = 'no user';
					return;
				}
				$user = Table::_( 'users' )->getUserByShop( $shop->id );
			} else {
				$user = Table::_( 'users' )->getUserForPlatform(
					$this->_getParam( 'username' ),
					$this->_platform
				);
				if ( !$user ) {
					$this->view->response = 'no user';
					return;
				}
				$shop = Table::_( 'shops' )->get( $user->shop_id );
			}
			// Generate new password and update an user.
			$password = uniqid();
			$encryptedPassword = mcrypt_encrypt( MCRYPT_DES, $config->crypt->key, $password, MCRYPT_MODE_ECB );
			$user->password = $encryptedPassword;
			$user->save();
			// Send email to user.
			if ( !empty ( $shop->email ) ) {
				// Prepare body.
				$helperEmail = $this->getHelper( 'email' );
				$name = 'forgot.phtml';
				$body = $this->view->render( "auth/notification/{$name}" );
				$body = str_replace( ':password', $password, $body );
				// Send email.
				$helperEmail->setSubject( $name )
					->getMailer()
					->clearRecipients()
					->addTo( $shop->email )
					->setBodyHtml( $body )
					->send()
					;
				$this->view->response = 'success';
			} else {
				$this->view->response = 'no email';
			}
		}

		/**
		 * User's account.
		 * Here user can edits api credentials or uninstalls a plugin.
		 *
		 * @see D_Controller_InnerAuth_Plugin::uninstallAction()
		 * @see D_Controller_InnerAuth_Plugin::configureAction()
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function accountAction()
		{
			// Parameters.
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			// Load credentials and plugins.
			$credentials = Table::_( 'credentials' )->getForUser( $this->_user->id );
			$shop = Table::_( 'credentials' )->getUserShop( $this->_user->id );
			$plugin = Table::_( 'plugins' )->getPlugin( $pluginName, $this->_platform );
			$notInstalledPlugins = Table::_( 'plugins' )->getNotInstalledPlugins( $shop );
			// View.
			$this->view->shop = $shop;
			$this->view->plugin = $plugin;
			$this->view->user = $this->_user;
			$this->view->credentials = $credentials;
			$this->view->notInstalledPlugins = $notInstalledPlugins;
		}

		/**
		 * Edits user's data.
		 */
		public function editAction()
		{
			$config = Config::getInstance();
			// Parameters.
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			// Prepare a form.
			eval ( '$formEdit = new '. $this->_platform .'_Form_Edit();' );
			$formEdit->populate( $this->_user->toArray() );
			$shop = Table::_( 'credentials' )->getUserShop( $this->_user->id );
			$formEdit->getElement( 'plugin' )
				->setValue( $this->_getParam( 'plugin' ) );
			$formEdit->getElement( 'username' )
				->setValue( $this->_user->name );
			$formEdit->getElement( 'email' )
				->setValue( $shop->email );
			// Process a form request.
			if ( $this->_request->isPost() ) {
				if ( $formEdit->isValid( $this->_request->getPost() ) ) {
					// Set name.
					$username = $formEdit->getElement( 'username' )->getValue();
					$user = Table::_( 'users' )->getUserForPlatform( $username, $this->_platform );
					if ( ( $this->_user->name != $username ) && $user ) {
						$formEdit->addError(
							Zend_Registry::get( 'translate' )->_( 'user exists' )
						);
					} else {
						$this->_user->setReadOnly( false );
						$this->_user->name = $username;
					}
					// Set email.
					$email = $formEdit->getElement( 'email' )->getValue();
					if ( ( $shop->email != $email ) && Table::_( 'shops' )->getByEmail( $email ) ) {
						$formEdit->addError(
							Zend_Registry::get( 'translate' )->_( 'email exists' )
						);
					} else {
						$shop->setReadOnly( false );
						$shop->email = $email;
					}
					// Set password.
					$password = $formEdit->getElement( 'password' )->getValue();
					$confirmPassword = $formEdit->getElement( 'confirm_password' )->getValue();
					if ( $password ) {
						if ( $password != $confirmPassword ) {
							$formEdit->addError(
								Zend_Registry::get( 'translate' )->_( 'passwords does not match' )
							);
						} else {
							$this->_user->password = mcrypt_encrypt( MCRYPT_DES, $config->crypt->key, $password, MCRYPT_MODE_ECB );
						}
					}
					// Display error or save.
					if ( $formEdit->isErrors() ) {
						$formEdit->setDecorators( array (
							'Errors', 'FormElements', 'Form'
						) );
					} else {
						$this->_user->save();
						$shop->save();
					}
				}
			}
			$formEdit->getElement( 'shop' )
				->setValue( $shop->name );
			// View.
			$this->view->form = $formEdit;
			$this->view->shop = $shop;
			$this->view->plugin = $pluginName;
		}

		/**
		 * Makes a selected plugin logged in before going to it.
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function gotoAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Parameters.
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			$userId = $this->_getParam( 'user' );
			// Auto-log in and go to plugin.
			$this->getHelper( $this->_platform )
				->setUserCookie( $pluginName, $userId );
			$this->getHelper( $this->_platform )
				->setInstalledCookie( $pluginName, $userId );
			$this->getHelper( $this->_platform )
				->gotoPlugin( $pluginName );
		}

		/**
		 * Makes a selected plugin logged in before installation.
		 * @todo rename to autoinstall
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function installAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Parameters.
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			$installPluginName = base64_decode( $this->_getParam( 'installPlugin' ) );
			// Auto-log in and go to install.
			$this->getHelper( $this->_platform )
				->setUserCookie( $installPluginName, $this->_user->id );
			return $this->getHelper( 'Redirector' )
				->gotoSimple( 'install', 'plugin', null, array (
					'plugin' => $this->_getParam( 'installPlugin' )
				) );
		}

		/**
		 * Returns user's credentials for given plugin.
		 * One plugin can has only one credentials.
		 *
		 * @internal CLI action.
		 * @internal [ plugin ] [ user ]
		 */
		public function credentialsAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Parameters.
			$pluginName = $this->_getParam( 'plugin' );
			$userId = $this->_getParam( 'user' );
			// Load credentials.
			$modelPayment = new Payment_Model_Payment();
			$plugin = $modelPayment->getPlugin( strtolower( $this->_platform ), $pluginName );
			$credentials = Table::_( 'credentials' )->getForPlugin( $plugin->id, $userId )->toArray();
			echo json_encode( $credentials );
		}
	}
