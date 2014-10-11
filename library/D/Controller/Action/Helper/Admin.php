<?php
	require_once 'Zend/Controller/Action/Helper/Abstract.php';

	/**
	 * Admin helper.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class D_Controller_Action_Helper_Admin extends Zend_Controller_Action_Helper_Abstract
	{
		private $_cronHash;

		/**
		 * Creates paginator object for given data.
		 * @param Zend_Db_Table_Rowset | array $data - some data to paginate.
		 * @return Zend_Paginator
		 */
		public function getPaginator( $data )
		{
			$config = Config::getInstance();
			Zend_View_Helper_PaginationControl::setDefaultViewPartial( 'ajax_pagination.phtml' );
			$paginator = Zend_Paginator::factory( $data );
			$paginator->setItemCountPerPage(
				$config->plugin->center->admin->itemsPerPage
			);
			$page = $this->getRequest()->getParam( 'page', 1 );
			$paginator->setCurrentPageNumber( $page );
			return $paginator;
		}

		/**
		 * Returns platforms with assigned plugin ids.
		 * @todo remove it and $platformPlugins after plugins db table will have integer platform id.
		 * @return array
		 */
		public function getPlatformPlugins()
		{
			// Load platforms.
			$platforms = Table::_( 'platforms' )->fetchAll();
			$platformIds = array ();
			foreach ( $platforms as $platform ) {
				$platformIds[ $platform->name ] = $platform->id;
			}
			// Load and attach plugins.
			$plugins = Table::_( 'plugins' )->fetchAll();
			$platformNames = array_keys( $platformIds );
			$platformPlugins = array ();
			foreach ( $plugins as $plugin ) {
				if ( in_array( $plugin->platform, $platformNames ) ) {
					$platformPlugins[ $platformIds[ $plugin->platform ] ][] = $plugin->id;
				}
			}
			// Return.
			return $platformPlugins;
		}

		/**
		 * Saves stat about cron task start.
		 * @param string $key - cron task key.
		 */
		public function startCronTask( $key )
		{
			$this->_cronHash = md5( $key . time() );
			Zend_Layout::getMvcInstance()
				->getView()->getHelper( 'action' )
				->action( 'start', 'cron', 'default', array (
					'key' => $key, 'hash' => $this->_cronHash
				) );
		}

		/**
		 * Saves stat about cron task stop.
		 * @param string $key - cron task key.
		 */
		public function stopCronTask( $key )
		{
			Zend_Layout::getMvcInstance()
				->getView()->getHelper( 'action' )
				->action( 'stop', 'cron', 'default', array (
					'key' => $key, 'hash' => $this->_cronHash
				) );
		}
	}
