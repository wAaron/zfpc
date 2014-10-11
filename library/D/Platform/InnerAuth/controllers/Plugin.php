<?php
	/**
	 * Multi-platform common controller for plugins.
	 * All interactions with plugin are performing in this controller.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.3.37
	 */
	abstract class D_Controller_InnerAuth_Plugin extends Zend_Controller_Action
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
			$this->_user = $this->getHelper( $this->_platform )
				->validateUser( $pluginName );
			if ( !$this->_user ) {
				return $this->getHelper( 'Redirector' )
					->gotoSimple( 'index', 'auth', strtolower( $this->_platform ), array (
						'plugin' => $this->_getParam( 'plugin' )
					) );
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
		 * Installs a plugin and redirects to it.
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function installAction()
		{
			$config = Config::getInstance();
			// Prepare data.
			$platform = Table::_( 'platforms' )->get( $this->_platform );
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			$plugin = Model::_( 'payment' )->getPlugin( $this->_platform, $pluginName );
			$shop = Table::_( 'shops' )->get( $this->_user->shop_id );
			Zend_Registry::set( 'shop', $shop );
			// If already installed.
			if ( Table::_( 'plugins' )->isInstalled( $this->_user->shop_id, $pluginName ) ) {
				$this->getHelper( $this->_platform )
					->gotoPlugin( $pluginName );
			}
			// If was uninstalled.
			$instance = Table::_( 'instances' )->exists( $this->_user->shop_id, $pluginName );
			if ( $instance ) {
				// Update an instance.
				$instance->state = 'installed';
				$instance->deinstallation_date = null;
				$instance->save();
				// Set cookie.
				$this->getHelper( $this->_platform )
					->setInstalledCookie( $pluginName, $this->_user->id );
				// Notification.
				$this->_sendInstallEmail( $platform, $plugin, $shop );
				// Platform specific actions.
				$this->_platformSpecificInstall( $instance, $shop, $plugin );
			}
			// Prepare a form.
			eval ( '$formInstall = new '. $this->_platform .'_Form_Install();' );
			$formInstall->plugin->setValue( $this->_getParam( 'plugin' ) );
			$formInstall->shop_domain->setValue( $shop->name );
			if ( $token = $this->_getParam( 'token' ) ) {
				$formInstall->api_key->setValue( $token );
			}
			// A plan select. TODO rewrite like instance settings when all forms be refactored.
			$multiOptions = array ( '' => '--' );
			$plans = Table::_( 'plans' )->getByPlugin( $plugin->id, null );
			foreach ( $plans as $_plan ) {
				$multiOptions[ $_plan->id ] = $_plan->name;
			}
			$formInstall->plan_id->setMultiOptions( $multiOptions );
			// Set a first plan automatically if it's free.
			$optionValues = array_values( $multiOptions );
			$plan = Table::_( 'plans' )->getPlan(
				$plugin->id, array_pop( $optionValues )
			);
			if ( $plan->isFree() ) {
				$formInstall->plan_id->setValue(
					array_pop( array_keys( $multiOptions ) )
				);
				$formInstall->plan_id->removeDecorator( 'label' );
				$formInstall->plan_id->getDecorator( 'HtmlTag' )
					->setOption( 'class', 'hidden' );
			}
			// Process a form request.
			if ( $this->_request->isPost() ) {
				if ( $formInstall->isValid( $this->_request->getPost() ) ) {
					Table::_( 'plugins' )->install( $this->_user->shop_id, $plugin->id );
					$instance = Table::_( 'plugins' )->getInstance( $this->_user->shop_id, $plugin->id );
					Table::_( 'notifications' )->insert( array (
						'instance' => $instance->id
					) );
					// Credentials.
					Table::_( 'credentials' )->insert( array_merge( array (
						'user_id' => $this->_user->id,
						'plugin_id' => $plugin->id
					), $this->prepareCredentialsFormValues( $formInstall )
					) );
					// Payment settings.
					$instance->paidTill( $config->plugin->trialPeriod * SECONDS_PER_DAY );
					Model::_( 'payment' )->setting( $shop, $plugin, 'suspend app', 0 );
					Model::_( 'payment' )->setting( $shop, $plugin, 'exclude from stat', 0 );
					Model::_( 'payment' )->setting( $shop, $plugin, 'trial period', $config->plugin->trialPeriod );
					Model::_( 'payment' )->setting( $shop, $plugin, 'trial changed', 0 );
					Model::_( 'payment' )->setting( $shop, $plugin, 'current plan',
						strtolower( $multiOptions[ $formInstall->plan_id->getValue() ] )
					); // TODO integer except string.
					Model::_( 'payment' )->setting( $shop, $plugin, 'plan changed', 0 );
					Model::_( 'payment' )->setting( $shop, $plugin, 'old rest', 0 );
					Model::_( 'payment' )->setting( $shop, $plugin, 'new rest', 0 );
					// Subscriptions.
					Table::_( 'userSettings' )->insert( array (
						'user_id' => $this->_user->id,
						'external_id' => $plugin->id,
						'name' => 'update news',
						'value' => $formInstall->update_news->getValue(),
					) );
					$where = "`user_id` = {$this->_user->id} AND `name` = 'product news'";
					if ( Table::_( 'userSettings' )->fetchRow( $where ) ) {
						Table::_( 'userSettings' )->update( array (
							'value' => $formInstall->product_news->getValue()
						), $where );
					} else {
						Table::_( 'userSettings' )->insert( array (
							'user_id' => $this->_user->id,
							'name' => 'product news',
							'value' => $formInstall->product_news->getValue(),
						) );
					}
					// Set cookie.
					$this->getHelper( $this->_platform )
						->setInstalledCookie( $pluginName, $this->_user->id );
					// Notification.
					$this->_sendInstallEmail( $platform, $plugin, $shop );
					// Platform specific actions.
					$this->_platformSpecificInstall( $instance, $shop, $plugin );
				}
			}
			// View.
			$this->view->plugin = $pluginName;
			$this->view->formInstall = $formInstall;
			$this->view->options = $this->_tariffPlanOptions(
				array_slice( array_keys( $multiOptions ), 1 )
			);
			$this->view->prices = $this->_tariffPlanPrices( $plugin->id );
		}

		/**
		 * Specific part of platform installation process.
		 * @param Default_Model_DbRow_PluginInstance $instance - instance.
		 * @param Zend_Db_Table_Row $shop - shop.
		 * @param Zend_Db_Table_Row $plugin - plugin.
		 */
		protected function _platformSpecificInstall( $instance, $shop, $plugin )
		{
			$this->getHelper( $this->_platform )
				->gotoPlugin( $plugin->name, 'install' );
		}

		/**
		 * Forms options html in specific format.
		 * @param array $plans - plan list.
		 * @see installAction(), configureAction()
		 */
		protected function _tariffPlanOptions( $plans )
		{
			$options = array ();
			foreach ( $plans as $_planId ) {
				if ( $_options = Table::_( 'options' )->getForPlan( $_planId ) ) {
					$options[ $_planId ] = $_options->toArray();
				}
			}
			return json_encode( $options );
		}

		/**
		 * Forms prices html in specific format.
		 * @param integer $pluginId - plugin id.
		 * @see installAction(), configureAction()
		 */
		protected function _tariffPlanPrices( $pluginId )
		{
			$firstPrice = 0;
			$prices = array ();
			$items = Table::_( 'plans' )->prices( $pluginId );
			foreach ( $items as $_price ) {
				if ( $_price->quantity == 1 ) {
					$firstPrice = $_price->price;
				} else if ( $this->_platform == 'Shopify' ) {
					continue;
				}
				$prices[ $_price->id ][] = $this->getHelper( '2co' )->price(
					$_price->variety, $_price->quantity, $firstPrice, $_price->price, false, true
				) . '<br />';
			}
			return json_encode( $prices );
		}

		/**
		 * Sends an email about successfully installation.
		 * @param Zend_Db_Table_Row $platform - platform.
		 * @param Zend_Db_Table_Row $plugin - plugin.
		 * @param Zend_Db_Table_Row $shop - shop.
		 */
		protected function _sendInstallEmail( $platform, $plugin, $shop )
		{
			$enabled = Model::_( 'settings' )->getPlatformValue( $platform->id, 'installation', 'bool' );
			if ( $enabled ) {
				// Prepare body.
				$helperEmail = $this->getHelper( 'email' );
				$path = array_shift( $this->view->getScriptPaths() ) . 'plugin/notification/';
				$name = $helperEmail->formTemplateName( $plugin, $path, 'installed.phtml' );
				$body = $this->_replacePlaceholders(
					$this->view->render( "plugin/notification/{$name}" ),
					$plugin
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
		 * Replaces placeholders in email body.
		 * @param string $body - body.
		 * @param object $plugin - installed plugin.
		 * @return string
		 */
		protected function _replacePlaceholders( $body, $plugin, $user = null )
		{
			if ( $this->_user ) {
				$user = $this->_user;
			}
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			$placeholders = array (
				'application_name' => $plugin->name,
				'username' => isset ( $user->name ) ? $user->name : Zend_Registry::get( 'translate' )->_( 'customer' ),
				'link_to_app_login' => $config->plugin->{$plugin->platform}->baseUrl . $filter->filter( $plugin->name ),
				'contact_email' => $config->notification->email->fromAddress,
			);
			// Replace placeholders.
			foreach ( $placeholders as $_key => $_value ) {
				$body = str_replace( ':'.$_key, $_value, $body );
			}
			return $body;
		}

		/**
		 * Uninstalls a plugin and redirects to auth page.
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function uninstallAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Parameters.
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			$shopId = $this->_getParam( 'shop_id' );
			$pluginId = $this->_getParam( 'plugin_id' );
			// Validate access.
			if ( $shopId != $this->_user->shop_id ) {
				return $this->getHelper( 'Redirector' )
					->gotoSimple( 'account', 'auth', strtolower( $this->_platform ), array (
						'plugin' => $this->_getParam( 'plugin' )
					) );
			}
			// Uninstall.
			$shop = Table::_( 'shops' )->get( $shopId );
			$plugin = Table::_( 'plugins' )->get( $pluginId );
			Table::_( 'plugins' )->uninstall( $shopId, $pluginId );
			// Unsubscribe.
			$where = "`user_id` = {$this->_user->id} AND `external_id` = $pluginId AND `name` = 'update news' AND `value` = 1";
			if ( $row = Table::_( 'userSettings' )->fetchRow( $where ) ) {
				$row->value = 0;
				$row->save();
			}
			if ( !count( Table::_( 'credentials' )->getForUser( $this->_user->id ) ) ) {
				$where = "`user_id` = {$this->_user->id} AND `name` = 'product news' AND `value` = 1";
				if ( $row = Table::_( 'userSettings' )->fetchRow( $where ) ) {
					$row->value = 0;
					$row->save();
				}
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
					$plugin
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
			// Call a plugin action.
			$url = $this->getHelper( $this->_platform )
				->getHomeUrl( $plugin->name ) .'/app/uninstall/'. $this->_user->id;
			$client = new Zend_Http_Client( $url, array () );
			$client->request();
			// Go back.
			return $this->getHelper( 'Redirector' )
				->gotoSimple( 'account', 'auth', strtolower( $this->_platform ), array (
					'plugin' => $this->_getParam( 'plugin' )
				) );
		}

		/**
		 * Configuring of credential API.
		 * @internal HTTP action.
		 * @internal [ plugin ]
		 */
		public function configureAction()
		{
			// Prepare a form.
			$autoloader = Zend_Loader_Autoloader::getInstance();
			if ( $autoloader->autoload( $this->_platform . '_Form_Configure' ) ) {
				eval ( '$formConfigure = new '. $this->_platform .'_Form_Configure();' );
			} else {
				$formConfigure = new D_Form_InnerAuth_Configure();
			}
			// Credentials.
			$apiData = Table::_( 'credentials' )->get(
				$this->_getParam( 'api_id', $formConfigure->id->getValue() )
			);
			$shop = Table::_( 'credentials' )->getUserShop( $this->_user->id );
			$plugin = Table::_( 'plugins' )->get( $apiData->plugin_id );
			// Validate access.
			if ( !$apiData || ( $apiData->user_id != $this->_user->id ) ) {
				return $this->getHelper( 'Redirector' )
					->gotoSimple( 'index', 'auth', strtolower( $this->_platform ), array (
						'plugin' => $this->_getParam( 'plugin' )
					) );
			}
			// Set a form data.
			$formConfigure->populate( $apiData->toArray() );
			$formConfigure->plugin->setValue( $this->_getParam( 'plugin' ) );
			if ( $setting = $this->_user->getSetting( 'update news', $plugin->id ) ) {
				$formConfigure->update_news->setValue( $setting->value );
			}
			if ( $setting = $this->_user->getSetting( 'product news' ) ) {
				$formConfigure->product_news->setValue( $setting->value );
			}
			// Plan list.
			$multiOptions = array ();
			$currentPlan = Table::_( 'paymentSettings' )->getSetting( 'current plan', $shop->id, $plugin->id );
			$plans = Table::_( 'plans' )->getByPlugin( $plugin->id, $currentPlan->value );
			foreach ( $plans as $_plan ) {
				$multiOptions[ $_plan->id ] = $_plan->name;
			}
			$formConfigure->plan_id->setMultiOptions( $multiOptions );
			// Set a first plan automatically if it's free.
			$plan = Table::_( 'plans' )->getPlan(
				$plugin->id, $multiOptions[ $formConfigure->plan_id->getValue() ]
			);
			if ( $plan->isFree() && ( count( $multiOptions ) == 1 ) ) {
				$formConfigure->plan_id->removeDecorator( 'label' );
				$formConfigure->plan_id->getDecorator( 'HtmlTag' )
					->setOption( 'class', 'hidden' );
			}
			// Process a form request.
			if ( $this->_request->isPost() ) {
				if ( $formConfigure->isValid( $this->_request->getPost() ) ) {
					// Save a current plan.
					$PC_currentPlanName = Table::_( 'credentials' )->getBasePlan( $plugin->id, $this->_user->id );
					$where = "`id` = ". $formConfigure->id->getValue();
					Table::_( 'credentials' )->update( $this->prepareCredentialsFormValues( $formConfigure ), $where );
					$PC_planName = Table::_( 'credentials' )->getBasePlan( $plugin->id, $this->_user->id );
					$planName = strtolower( $multiOptions[ $formConfigure->plan_id->getValue() ] );
					$currentPlanName = Model::_( 'payment' )->setting( $shop, $plugin, 'current plan' );
					Model::_( 'payment' )->setting( $shop, $plugin, 'old plan', $currentPlanName );
					Model::_( 'payment' )->setting( $shop, $plugin, 'current plan', $planName );
					// Subscriptions.
					$this->_user->setSetting( 'update news', $formConfigure->update_news->getValue(), $plugin->id );
					$this->_user->setSetting( 'product news', $formConfigure->product_news->getValue() );
					// Recalculation.
					$instance = Table::_( 'plugins' )->getInstance( $shop->id, $plugin->id );
					$platform = Table::_( 'platforms' )->get(
						$this->_platform
					);
					// Platform specific actions.
					$this->_platformSpecificConfigure(
						$instance, $shop, $plugin, $PC_currentPlanName, $PC_planName, $currentPlanName, $planName
					);
				}
			}
			// View.
			$this->view->shop = $shop;
			$this->view->plugin = $plugin->name;
			$this->view->form = $formConfigure;
			$this->view->username = $this->_user->name;
			$this->view->options = $this->_tariffPlanOptions( array_keys( $multiOptions ) );
			$this->view->prices = $this->_tariffPlanPrices( $plugin->id );
		}

		/**
		 * Specific part of platform configuration process.
		 * @param Default_Model_DbRow_PluginInstance $instance - instance.
		 * @param Zend_Db_Table_Row $shop - shop.
		 * @param Zend_Db_Table_Row $plugin - plugin.
		 * @param string $PC_currentPlanName - base plan before update.
		 * @param string $PC_planName - base plan after update.
		 * @param string $currentPlanName - current plugin plan from settings.
		 * @param string $planName - new plugin plan.
		 */
		protected function _platformSpecificConfigure(
			$instance, $shop, $plugin,
			$PC_currentPlanName, $PC_planName,
			$currentPlanName, $planName
		) {
			// TODO the check of paid till date.
			$transaction = Model::_( 'payment' )->getLastTransaction( $shop->id, $plugin->id, true );
			if ( $currentPlanName && ( $currentPlanName != $planName ) && $transaction ) {
				Model::_( 'payment' )->setting( $shop, $plugin, 'plan changed', 1 );
				$this->getHelper( '2co' )
					->setTransaction( $transaction );
				$factor = $this->getHelper( '2co' )
					->factor( $PC_currentPlanName, $PC_planName );
				if ( $factor < 1 ) {
					$instance->changePaidTill( $factor );
					// TODO cancel recurring transaction.
				}
			}
		}

		/**
		 * Preparing of Form Values
		 * @param $form - credentials form (install, configure)
		 * @author Kovalev Yury, SpurIT <contact@spur-i-t.com>
		 * @return array
		 */
		protected function prepareCredentialsFormValues($form)
		{
			return array (
				'plan_id' => $form->plan_id->getValue(),
				'api_key' => $form->api_key->getValue(),
				'api_user' => $form->api_user->getValue()
			);
		}
	}
