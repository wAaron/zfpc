<?php
	require_once 'Zend/View/Helper/Abstract.php';

	/**
	 * Helper for rendering main menu.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.2
	 */
	class Admin_View_Helper_MainMenu extends Zend_View_Helper_Abstract
	{
		/**
		 * Menu html content.
		 * @var string
		 */
		private $_menu = '';

		/**
		 * Index action.
		 * @param array $options - menu options.
		 */
		public function MainMenu( $options ) {
			$this->_formMenu( $options, 'main' );
			return $this->_menu;
		}

		/**
		 * Forms menu and submenu.
		 * @param array $options - menu options.
		 * @param string $type - menu type ( main | sub ).
		 */
		private function _formMenu( $options, $type )
		{
			$class = '';
			if ( $type == 'main' ) {
				$class = 'main-navigation-menu';
			} else if ( $type == 'sub' ) {
				$class = 'sub-menu';
			}
			$this->_menu .= '<ul class="'. $class .'">';
			foreach ( $options as $item ) {
				if($this->_isAllowedItem($item)){
					$this->_formMenuItem( $item, $type );
				}
			}
			$this->_menu .= '</ul>';
		}

		/**
		 * Forms menu item.
		 * @param array $item - item options.
		 * @param string $type - menu type ( main | sub ).
		 */
		private function _formMenuItem( $item, $type )
		{
			$frontController = Zend_Controller_Front::getInstance();
			$dispatcher = $frontController->getDispatcher();
			$router = $frontController->getRouter();
			$request = $frontController->getRequest();
			// Prepare item attributes.
			$href = '';
			$isActive = false;
			$hasSubmenu = isset ( $item['submenu'] );
			$icon = isset ( $item['icon'] ) ? '<i class="'. $item['icon'] .'"></i>' : '';
			$url = isset ( $item['url'] ) ? $item['url'] : array ();
			if ( !empty ( $url ) ) {
				if ( !isset ( $url['controller'] ) ) {
					$url['controller'] = $dispatcher->getDefaultControllerName();
				}
				if ( !isset ( $url['action'] ) ) {
					$url['action'] = $dispatcher->getDefaultAction();
				}
				$href = $router->assemble( $url );
				$currentUrl = $router->assemble( array (
					'module' => $request->getModuleName(),
					'controller' => $request->getControllerName(),
					'action' => $request->getActionName()
				) );
				$isActive = ( $href == $currentUrl );
			}
			$class = $isActive ? 'active open' : '';
			// Main menu item.
			if ( $type == 'main' ) {
				if ( !$class ) {
					$class = 'main-empty-class';
				}
				$this->_menu .= '
				<li class="'. $class .'">
					<a href="'. ( !$hasSubmenu ? $href : 'javascript:void(0);' ) .'">
						'. $icon .'
						<span class="title">'. Zend_Registry::get( 'translate' )->_( $item['title'] ) .'</span>
						'. ( $hasSubmenu ? '<i class="icon-arrow"></i>' : '' ) .'
						<span class="selected"></span>
					</a>
				';
			}
			// Sub-menu item.
			else if ( $type == 'sub' ) {
				if ( $class && ( ( $pos = strrpos( $this->_menu, 'main-empty-class' ) ) !== false ) ) {
					$this->_menu = substr_replace( $this->_menu, $class, $pos, strlen( 'main-empty-class' ) );
				}
				$this->_menu .= '
				<li class="'. $class .'">
					<a href="'. $href .'">
						<span class="title">'. Zend_Registry::get( 'translate' )->_( $item['title'] ) .'</span>
					</a>
				';
			}
			// Form sub-menu.
			if ( $hasSubmenu ) {
				$this->_formMenu( $item['submenu'], 'sub' );
			}
			// Close li tag.
			$this->_menu .= '</li>';
		}


		/**
		 * check is menu item allowed for viewer
		 * @param $item
		 * @return bool
		 * @author Kuksanau Ihnat
		 */
		private function _isAllowedItem( $item )
		{
			$isAllowed = true;
			$resource = isset( $item['resource'] ) ? $item['resource'] : null;
			$privilege = isset( $item['privilege'] ) ? $item['resource'] : null;

			if( $resource ){
				$isAllowed = $this->view->isAllowed( $resource, $privilege );
			}

			return $isAllowed;
		}
	}
