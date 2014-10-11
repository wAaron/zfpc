<?php
	/**
	 * Application bootstrap.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.2.21
	 */
	class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
	{
		/**
		 * Module list.
		 * @var array
		 */
		private $_modules = array (
			'admin', 'bigcommerce', 'default', 'ecwid', 'payment', 'shopify', 'volusion', 'magento', 'prestashop', 'webhooks', 'opencart'
		);

		/**
		 * Config initialization.
		 */
		protected function _initConfig()
		{
			$config = new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV );
			Zend_Registry::set( 'config', $config );
			Zend_Loader::loadClass( 'Config', ROOT_PATH . '/library/D' );
		}

		/**
		 * Database initialization.
		 */
		protected function _initDb()
		{
			$config = Zend_Registry::get( 'config' );
			$db = Zend_Db::factory( $config->database );
			Zend_Db_Table::setDefaultAdapter( $db );
			Zend_Registry::set( 'db', $db );
			Zend_Loader::loadClass( 'Table', ROOT_PATH . '/library/D/Db' );
			Zend_Loader::loadClass( 'Model', ROOT_PATH . '/library/D/Db' );
		}

		/**
		 * Cache initialization.
		 */
		protected function _initCache()
		{
			/*
			$config = Zend_Registry::get( 'config' );
			$cache = Zend_Cache::factory(
				'Core', 'Memcached',
				array (
					'automatic_serialization' => true,
					'ignore_user_abort' => true
				),
				array (
					'servers' => array (
						array (
							'host' => $config->memcached->host,
							'port' => $config->memcached->port,
							'persistent' => $config->memcached->persistent,
							'weight' => $config->memcached->weight,
							'timeout' => $config->memcached->timeout,
							'retry_interval' => $config->memcached->retryInterval,
							'status' => $config->memcached->status
						)
					)
				)
			);
			Zend_Registry::set( 'cache', $cache );
			*/
		}

		/**
		 * Routes initialization.
		 */
		protected function _initRoutes()
		{
			// CLI.
			if ( PHP_SAPI == 'cli' ) {
				Zend_Loader::loadClass( 'D_Controller_Router_Cli' );
				$front = $this->bootstrap( 'FrontController' )
					->getResource( 'FrontController' );
				$front->setRouter( new D_Controller_Router_Cli() );
				$front->setRequest( new Zend_Controller_Request_Simple() );
			}
			// HTTP.
			else {
				$front = $this->bootstrap( 'FrontController' )
					->getResource( 'FrontController' );
				// Bigcommerce route.
				$routeBigcommerce = new Zend_Controller_Router_Route(
					'bigcommerce/plugin/:id/*',
					array (
						'module' => 'bigcommerce',
						'controller' => 'plugin',
						'action' => 'index'
					),
					array (
						'id' => '\d+'
					)
				);
				$front->getRouter()
					->addRoute( 'bigcommerce', $routeBigcommerce );
				// Shopify route.
				$routeShopify = new Zend_Controller_Router_Route(
					'shopify/plugin/:id/*',
					array (
						'module' => 'shopify',
						'controller' => 'plugin',
						'action' => 'index'
					),
					array (
						'id' => '\d+'
					)
				);
				$front->getRouter()
					->addRoute( 'shopify', $routeShopify );
				// Page route.
				$routePage = new Zend_Controller_Router_Route(
					'default/index/page/:platform/:page',
					array (
						'module' => 'default',
						'controller' => 'index',
						'action' => 'page'
					)
				);
				$front->getRouter()
					->addRoute( 'page', $routePage );
			}
		}

		/**
		 * Autoloader initialization.
		 */
		protected function _initAutoloader()
		{
			// Library.
			$autoloader = Zend_Loader_Autoloader::getInstance();
			$autoloader->registerNamespace( 'D_' );
			$resourceLoader = new Zend_Loader_Autoloader_Resource( array (
				'basePath'  => ROOT_PATH . '/library/D',
				'namespace' => 'D',
			) );
			$resourceLoader->addResourceTypes( array (
				'IAP_Controller' => array (
					'path' => 'Platform/InnerAuth/controllers',
					'namespace' => 'Controller_InnerAuth',
				),
				'IAP_Form' => array (
					'path' => 'Platform/InnerAuth/forms',
					'namespace' => 'Form_InnerAuth',
				),
				'Admin_Form' => array (
					'path' => 'Admin/forms',
					'namespace' => 'Admin_Form',
				),
				'Acl' => array (
					'path' => '/',
					'namespace' => '',
				),
			) );
			// Modules.
			foreach ( $this->_modules as $_module ) {
				$resourceLoader = new Zend_Loader_Autoloader_Resource( array (
					'basePath'  => APPLICATION_PATH .'/modules/'. $_module,
					'namespace' => ucfirst( $_module ) .'_',
				) );
				$resourceLoader->addResourceTypes( array (
					'form' => array (
						'path' => 'forms/',
						'namespace' => 'Form',
					),
					'model' => array (
						'path' => 'models/',
						'namespace' => 'Model',
					),
				) );
			}
		}

		/**
		 * Logger initialization.
		 */
		protected function _initLogger()
		{
			// Error logger.
			$writer = new Zend_Log_Writer_Stream(
				APPLICATION_PATH . '/../log/error.log'
			);
			$writer->setFormatter( new Zend_Log_Formatter_Simple(
				'------------------------------------'. PHP_EOL
				.'%timestamp% %priorityName% (%priority%) : %message%'. PHP_EOL
				.'%info%'. PHP_EOL
			) );
			$this->registerPluginResource( 'Log', array (
				'writer' => $writer
			) );
			$this->bootstrap( 'Log' );
			// Debug logger.
			$logger = new Zend_Log( new Zend_Log_Writer_Stream(
				APPLICATION_PATH . '/../log/debug.log'
			) );
			Zend_Registry::set( 'logger', $logger );
			// Tasks logger.
			$taskLogger = new Zend_Log( new Zend_Log_Writer_Stream(
				APPLICATION_PATH . '/../log/tasks.log'
			) );
			Zend_Registry::set( 'taskLogger', $taskLogger );
		}

		/**
		 * Translator initialization.
		 */
		protected function _initTranslate()
		{
			$translate = new Zend_Translate( array (
				'adapter' => 'csv',
				'content' => APPLICATION_PATH . '/languages/en/general.csv',
				'locale'  => 'en'
			) );
			Zend_Registry::set( 'translate', $translate );
		}

		/**
		 * Controller resurces initialization.
		 */
		protected function _initController()
		{
			// Helpers.
			Zend_Controller_Action_HelperBroker::addPrefix( 'D_Controller_Action_Helper' );
			// Plugins.
			$front = $this->bootstrap( 'FrontController' )
				->getResource( 'FrontController' );
			$front->registerPlugin( new D_Controller_Plugin_Modules() );
			$front->registerPlugin( new D_Controller_Plugin_Actions() );
		}

		/**
		 * View initialization.
		 */
		protected function _initView()
		{
			// View.
			$view = new Zend_View();
			$view->translate()->setTranslator(
				Zend_Registry::get( 'translate' )
			);
			$view->addHelperPath( 'D/View/Helper', 'D_View_Helper' );
			$session = new Zend_Session_Namespace( 'pc.admin' );
			if ( $session->admin instanceof Zend_Db_Table_Row ) {
				$view->adminFullname = $session->admin->fullname;
			}
			Zend_Controller_Action_HelperBroker::getStaticHelper( 'ViewRenderer' )
				->setView( $view );
			// Layout.
			$layout = $this->bootstrap( 'Layout' )
				->getResource( 'Layout' );
			if ( PHP_SAPI == 'cli' ) {
				$layout->disableLayout();
			} else {
				$view->config = Zend_Registry::get( 'config' );
			}
		}
	}
