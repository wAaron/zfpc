<?php
	/**
	 * Module plugin.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.2
	 */
	class D_Controller_Plugin_Modules extends Zend_Controller_Plugin_Abstract
	{
		/**
		 * @internal Overrided.
		 */
		public function preDispatch( Zend_Controller_Request_Abstract $request )
		{
			$module = $request->getModuleName();
			if ( $module == 'admin' ) {
				Zend_Layout::getMvcInstance()->setLayout( 'admin' );
				Zend_View_Helper_PaginationControl::setDefaultViewPartial( 'pagination.phtml' );
				return;
			}
			$platform = ( $module == 'default' ) ? $request->getParam( 'platform' ) : $module;
			Zend_Layout::getMvcInstance()->assign( 'module', $module );
			Zend_Layout::getMvcInstance()->assign( 'platform', $platform );
			// CSS.
			$headLink = new Zend_View_Helper_HeadLink();
			$headLink->headLink( array (
				'rel' => 'stylesheet',
				'media' => 'screen',
				'href' => 'public/css/common.css'
			) );
			if ( $platform != 'ecwid' ) {
				$headLink->headLink( array (
					'rel' => 'stylesheet',
					'media' => 'screen',
					'href' => 'public/css/common-theme.css'
				) );
			}
			$headLink->headLink( array (
				'rel' => 'stylesheet',
				'media' => 'screen',
				'href' => 'public/css/'. $module .'.css'
			) );
			$headLink->headLink( array (
				'rel' => 'stylesheet',
				'media' => 'screen',
				'href' => 'public/css/jquery-ui.min.css'
			) );
			// JS.
			$headScript = new Zend_View_Helper_HeadScript();
			// jQuery.
			$headScript->appendFile( 'public/js/jquery.min.js' );
			$headScript->appendFile( 'public/js/jquery-ui.min.js' );
			// Google analytics.
			$validator = new Zend_Validate_Alpha();
			if ( $validator->isValid( $platform ) && !in_array( $module, array ( 'admin', 'payment' ) ) ) {
				$headScript->appendFile( "public/js/{$platform}_google.js" );
			}
		}
	}
