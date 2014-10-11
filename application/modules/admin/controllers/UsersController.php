<?php
	/**
	 * Plugin Center users.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 2.0.2
	 */
	class Admin_UsersController extends D_Admin_Controller_Abstract
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			parent::init();
			$this->_entity = 'user';
			$this->_table = 'users';
		}

		/**
		 * User list.
		 */
		public function indexAction()
		{
			$config = Config::getInstance();
			// Prepare filter params.
			$formFilter = new Admin_Form_User_Filter();
			$filterParams = array ();
			if ( $this->_request->isPost() ) {
				if ( $formFilter->isValid( $this->_request->getPost() ) ) {
					$filterParams = $formFilter->getValues();
					$this->view->filtered = true;
				}
			}
			// Load plans.
			$page = $this->_getParam( 'page', 1 );
			$users = Table::_( 'users' )->getAdminList( $filterParams );
			$paginator = Zend_Paginator::factory( $users );
			$paginator->setItemCountPerPage(
				$config->plugin->center->admin->itemsPerPage
			);
			$paginator->setCurrentPageNumber( $page );
			// Prepare view.
			$this->view->paginator = $paginator;
			$this->view->formFilter = $formFilter;
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'members' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Mailchimp common interface.
		 * There is export file generation
		 */
		public function mailchimpAction()
		{
			$config = Config::getInstance();
			// Process submitted data.
			$formFilter = new Admin_Form_User_MailchimpFilter();
			if ( $this->_request->isPost() ) {
				if ( $formFilter->isValid( $this->_request->getPost() ) ) {
					$newsType = $this->_getParam( 'news_type' );
					// Platform.
					$platform = Table::_( 'platforms' )->get(
						$this->_getParam( 'platform' )
					);
					// Plugin.
					$pluginId = null;
					if ( $plugin = $this->_getParam( 'plugin' ) ) {
						$plugin = Table::_( 'plugins' )->get( $plugin );
						$pluginId = $plugin->id;
					}
					// Compare system users with mailchimp.
					$all = false;
					$mcListId = Table::_( 'mailchimpLists' )->getListId( $platform->id, $pluginId );
					if ( $newsType == 'all customers news' ) {
						$newsType = 'product news';
						$all = true;
					}
					else if ( $newsType == 'critical updates' ) {
						$newsType = 'update news';
						$all = true;
					}
					if ( $newsType == 'uninstalled apps' ) {
						$filter = array (
							'platform' => $platform->name,
							'state' => 'uninstalled',
						);
						if ( $pluginId ) {
							$filter['plugin_id'] = $pluginId;
						}
						$users = Table::_( 'instances' )->instances( $filter );
					} else {
						$users = Table::_( 'users' )->getUsersForMailchimp(
							$platform->id, $newsType, $pluginId, $all
						);
					}
					if ( count( $users ) ) {
						$this->_mailchimpGenerate( $users, $mcListId );
					} else {
						$this->view->message = Zend_Registry::get( 'translate' )->_( 'no new users' );
					}
				}
			}
			// Files.
			$files = array ();
			$directory = $config->mailchimp->directory;
			foreach ( scandir( $directory ) as $file ) {
				if ( substr( $file, 0, 1 ) != '.' ) {
					$files[] = $file;
				}
			}
			// View.
			$this->view->files = $files;
			$this->view->formFilter = $formFilter;
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'mailchimp synchronization' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Creates csv file to export to mailchimp list.
		 * File contains only users that are absent in mailchimp list.
		 * Directory for writing: files/mailchimp
		 *
		 * @param Zend_Db_Table_Rowset $users - users for export.
		 * @param integer $mcListId - mailchimp list id.
		 */
		private function _mailchimpGenerate( $users, $mcListId )
		{
			$config = Config::getInstance();
			// Get list name.
			$filter = new D_Filter_FileName();
			$mcFullLists = $this->getHelper( 'MCAPI' )
				->lists();
			foreach ( $mcFullLists['data'] as $fullList ) {
				if ( $fullList['id'] == $mcListId ) {
					$fullListName = $filter->filter( $fullList['name'] );
					$filename = $fullListName . '.csv';
					break;
				}
			}
			// Write new users in file.
			$filepath = $config->mailchimp->directory . $filename;
			if ( ( $file = fopen( $filepath, 'w' ) ) != false ) {
				list ( $subEmails, $unsubEmails ) = $this->_getListEmails( $mcListId );
				$newUsers = false;
				foreach ( $users as $user ) {
					$canExport = (
							!in_array( $user->email, $subEmails )
						&& !in_array( $user->email, $unsubEmails )
					);
					if ( $canExport ) {
						$newUsers = true;
						$userName = isset ( $user->name ) ? $user->name : $user->user;
						fputcsv( $file, array (
							$user->email,
							$userName
						) );
					}
				}
				fclose( $file );
				if ( !$newUsers ) {
					$this->view->message = Zend_Registry::get( 'translate' )->_( 'no new users' );
				} else {
					$this->view->message = Zend_Registry::get( 'translate' )->_( 'generated' );
				}
			}
		}

		/**
		 * Returns subscribed and unsubscribed users for given list id.
		 * @param integer $listId - list id.
		 * @return array
		 */
		private function _getListEmails( $listId )
		{
			$subEmails = $unsubEmails = array ();
			// Get subscribed users.
			$mcSubUsers = $this->getHelper( 'MCAPI' )
				->listMembers( $listId, 'subscribed' );
			if ( $mcSubUsers ) {
				foreach ( $mcSubUsers['data'] as $mcUser ) {
					$subEmails[] = $mcUser['email'];
				}
			}
			// Get unsubscribed users.
			$mcUnsubUsers = $this->getHelper( 'MCAPI' )
				->listMembers( $listId, 'unsubscribed' );
			if ( $mcUnsubUsers ) {
				foreach ( $mcUnsubUsers['data'] as $mcUser ) {
					$unsubEmails[] = $mcUser['email'];
				}
			}
			// Return.
			return array ( $subEmails, $unsubEmails );
		}

		/**
		 * Shows users which have to be deleted from mailchimp.
		 */
		public function mailchimpUsersToDeleteAction()
		{
			Zend_Layout::getMvcInstance()->disableLayout();
			// Parameters.
			$platformId = $this->_getParam( 'platform_id' );
			$newsType = $this->_getParam( 'news_type' );
			$pluginId = $this->_getParam( 'plugin_id' );
			if ( $newsType == 'all customers news' ) {
				$newsType = 'product news';
			} else if ( $newsType == 'critical updates' ) {
				$newsType = 'update news';
			}
			// Process users.
			$users = Table::_( 'users' )->getUsersForMailchimp( $platformId, $newsType, $pluginId, true );
			if ( count( $users ) ) {
				$mcListId = Table::_( 'mailchimpLists' )->getListId( $platformId, $pluginId );
				list ( $subEmails, $unsubEmails ) = $this->_getListEmails( $mcListId );
				$toDeleteFromSub = $toDeleteFromUnsub = array ();
				foreach ( $users as $user ) {
					$setting = $user->getSetting( $newsType, $pluginId );
					if ( !$setting->value && in_array( $user->email, $subEmails ) ) {
						$toDeleteFromSub[] = $user->email;
					}
					else if ( $setting->value && in_array( $user->email, $unsubEmails ) ) {
						$toDeleteFromUnsub[] = $user->email;
					}
				}
				$this->view->toDeleteFromSub = $toDeleteFromSub;
				$this->view->toDeleteFromUnsub = $toDeleteFromUnsub;
				$this->view->maxCount = max( array (
					count( $toDeleteFromSub ),
					count( $toDeleteFromUnsub )
				) );
			}
		}
	}
