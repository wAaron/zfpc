<?php
	/**
	 * Provides with independent template snippets.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.1
	 */
	class Default_TemplateController extends Zend_Controller_Action
	{
		/**
		 * Common menu snippet.
		 * @internal CLI action.
		 * @internal [ platform ] [ shop ] [ plugin ]
		 */
		public function menuAction()
		{
			$config = Config::getInstance();
			$tableShops = new Default_Model_DbTable_Shops();
			// Parameters.
			$platform = $this->_getParam( 'platform' );
			if ( !$shop = $this->_getParam( 'shop' ) ) {
				$shop = $tableShops->getForUser( $this->_getParam( 'user' ) );
				$shop = $shop->name;
			}
			$plugin = $this->_getParam( 'plugin' );
			// View.
			$this->view->shop = $shop;
			$this->view->plugin = $plugin;
			$filter = new D_Filter_PluginDirectory();
			$this->view->getHelper( 'BaseUrl' )->setBaseUrl(
				$config->plugin->$platform->baseUrl . $filter->filter( $plugin )
			);
			$this->view->target = $this->_getParam( 'target' );
			$this->view->PC_baseUrl = $config->plugin->center->baseUrl;
			$this->renderScript(
				"template/{$platform}_menu.phtml"
			);
		}
	}
