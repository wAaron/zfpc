<?php
	/**
	 * Action plugin.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.1
	 */
	class D_Controller_Plugin_Actions extends Zend_Controller_Plugin_Abstract
	{
		/**
		 * @internal Overrided.
		 */
		public function preDispatch( Zend_Controller_Request_Abstract $request )
		{
			$module = $request->getModuleName();
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			// Reflection method.
			$moduleDirectory = Zend_Controller_Front::getInstance()->getModuleDirectory( $module );
			$controllerName = Zend_Controller_Front::getInstance()->getDispatcher()->formatControllerName( $controller );
			$actionName = Zend_Controller_Front::getInstance()->getDispatcher()->formatActionName( $action );
			$classDir = $moduleDirectory .'/controllers/'. $controllerName .'.php';
			$className = ucfirst( $module ) .'_'. $controllerName;
			Zend_Loader::loadFile( $classDir, null, true );
			// Check CLI security.
			if ( PHP_SAPI != 'cli' ) {
				$reflection = new ReflectionMethod( $className, $actionName );
				if ( strstr( $reflection->getDocComment(), '@internal CLI action' ) ) {
					throw new Exception( 'Access denied' );
				}
			}
		}
	}
