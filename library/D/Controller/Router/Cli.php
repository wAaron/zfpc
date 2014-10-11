<?php
	/**
	 * CLI router.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.3
	 *
	 */
	class D_Controller_Router_Cli extends Zend_Controller_Router_Abstract
	{
		/**
		 * @internal Overrided.
		 */
		public function route( Zend_Controller_Request_Abstract $dispatcher )
		{
			$getopt = new Zend_Console_Getopt( array (
				'module|m-s' => 'Module name',
				'controller|c-s' => 'Controller name',
				'action|a=s' => 'Action name',
				'platform|P-s' => 'Platform name',
				'shop|s-s' => 'Shop name',
				'plugin|p-s' => 'Plugin name',
				'plan-s' => 'Plan name',
				'user-d' => 'User id',
				'option-s' => 'Option type',
				'value-s' => 'Value',
				'amount-d' => 'Amount',
				'key-s' => 'Key',
				'hash-s' => 'Hash',
				'fromCode-s' => 'From Currency Code',
				'toCode-s' => 'To Currency Code',
				'emailFrom-s' => 'Email from',
				'emailNameFrom-s' => 'Email name from',
				'emailTo-s' => 'Email to',
				'emailSubject-s' => 'Email subject',
				'emailMessage-s' => 'Email message',
				'appEmailId-s' => 'App email id',
				'emailPriority-d' => 'Email priority',
				'callbackUrl-s' => 'App callback url',
				'emailNotSend-d' => 'Do not send Email',
				'webhooksData-s' => 'Webhooks data',
			) );
			// Module.
			$module = 'default';
			if ( $getopt->getOption( 'm' ) ) {
				$module = $getopt->getOption( 'm' );
			}
			$dispatcher->setModuleName( $module );
			// Controller.
			$controller = 'payment';
			if ( $getopt->getOption( 'c' ) ) {
				$controller = $getopt->getOption( 'c' );
			}
			$dispatcher->setControllerName( $controller );
			// Action.
			$action = 'details';
			if ( $getopt->getOption( 'a' ) ) {
				$action = $getopt->getOption( 'a' );
			}
			$dispatcher->setActionName( $action );
			// Action parameters.
			if ( $getopt->getOption( 'P' ) ) {
				$dispatcher->setParams( array (
					'platform' => $getopt->getOption( 'P' )
				) );
			}
			if ( $getopt->getOption( 's' ) ) {
				$dispatcher->setParams( array (
					'shop' => $getopt->getOption( 's' )
				) );
			}
			if ( $getopt->getOption( 'p' ) ) {
				$dispatcher->setParams( array (
					'plugin' => $getopt->getOption( 'p' )
				) );
			}
			if ( $getopt->getOption( 'plan' ) ) {
				$dispatcher->setParams( array (
					'plan' => $getopt->getOption( 'plan' )
				) );
			}
			if ( $getopt->getOption( 'user' ) ) {
				$dispatcher->setParams( array (
					'user' => $getopt->getOption( 'user' )
				) );
			}
			if ( $getopt->getOption( 'option' ) ) {
				$dispatcher->setParams( array (
					'option' => $getopt->getOption( 'option' )
				) );
			}
			if ( $getopt->getOption( 'value' ) ) {
				$dispatcher->setParams( array (
					'value' => $getopt->getOption( 'value' )
				) );
			}
			if ( $getopt->getOption( 'amount' ) ) {
				$dispatcher->setParams( array (
					'amount' => $getopt->getOption( 'amount' )
				) );
			}
			if ( $getopt->getOption( 'key' ) ) {
				$dispatcher->setParams( array (
					'key' => $getopt->getOption( 'key' )
				) );
			}
			if ( $getopt->getOption( 'hash' ) ) {
				$dispatcher->setParams( array (
					'hash' => $getopt->getOption( 'hash' )
				) );
			}
			if ( $getopt->getOption( 'fromCode' ) ) {
				$dispatcher->setParams( array (
					'fromCode' => $getopt->getOption( 'fromCode' )
				) );
			}
			if ( $getopt->getOption( 'toCode' ) ) {
				$dispatcher->setParams( array (
					'toCode' => $getopt->getOption( 'toCode' )
				) );
			}
			if ( $getopt->getOption( 'emailFrom' ) ) {
				$dispatcher->setParams( array (
					'emailFrom' => $getopt->getOption( 'emailFrom' )
				) );
			}
			if ( $getopt->getOption( 'emailTo' ) ) {
				$dispatcher->setParams( array (
					'emailTo' => $getopt->getOption( 'emailTo' )
				) );
			}
			if ( $getopt->getOption( 'emailSubject' ) ) {
				$dispatcher->setParams( array (
					'emailSubject' => $getopt->getOption( 'emailSubject' )
				) );
			}
			if ( $getopt->getOption( 'emailMessage' ) ) {
				$dispatcher->setParams( array (
					'emailMessage' => $getopt->getOption( 'emailMessage' )
				) );
			}
			if ( $getopt->getOption( 'emailPriority' ) ) {
				$dispatcher->setParams( array (
					'emailPriority' => $getopt->getOption( 'emailPriority' )
				) );
			}
			if ( $getopt->getOption( 'appEmailId' ) ) {
				$dispatcher->setParams( array (
					'appEmailId' => $getopt->getOption( 'appEmailId' )
				) );
			}
			if ( $getopt->getOption( 'callbackUrl' ) ) {
				$dispatcher->setParams( array (
					'callbackUrl' => $getopt->getOption( 'callbackUrl' )
				) );
			}
			if ( $getopt->getOption( 'emailNameFrom' ) ) {
				$dispatcher->setParams( array (
					'emailNameFrom' => $getopt->getOption( 'emailNameFrom' )
				) );
			}
			if ( $getopt->getOption( 'emailNotSend' ) ) {
				$dispatcher->setParams( array (
					'emailNotSend' => $getopt->getOption( 'emailNotSend' )
				) );
			}
			if ( $getopt->getOption( 'webhooksData' ) ) {
				$dispatcher->setParams( array (
					'webhooksData' => $getopt->getOption( 'webhooksData' )
				) );
			}
			return $dispatcher;
		}

		/**
		 * @internal Not implemented.
		 */
		public function assemble( $userParams, $name = null, $reset = false, $encode = true ) {
			return;
		}
	}
