<?php
	require_once 'Zend/Controller/Action/Helper/Abstract.php';

	/**
	 * Notification helper.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.2.5
	 */
	class D_Controller_Action_Helper_Email extends Zend_Controller_Action_Helper_Abstract
	{
		/**
		 * Mailer.
		 * @var Zend_Mail
		 */
		private $_mailer = null;

		/**
		 * Mail transport.
		 * @var Zend_Mail_Transport_Smtp
		 */
		private $_transport = null;

		/**
		 * Returns mailer.
		 * @return Zend_Mail
		 */
		public function getMailer()
		{
			if ( !$this->_mailer ) {
				$config = Config::getInstance();
				$this->_mailer = new D_Mail();
				$this->_mailer->setDefaultTransport( $this->getTransport() );
				$this->_mailer->setFrom(
					$config->notification->email->fromAddress,
					$config->notification->email->fromName
				);
			}
			return $this->_mailer;
		}

		/**
		 * Returns default smtp transport.
		 * @return null|Zend_Mail_Transport_Smtp
		 * @author Kuksanau Ihnat
		 */
		public function getTransport()
		{
			if ( !$this->_transport ) {
				$config = Config::getInstance();
				$this->_transport = new Zend_Mail_Transport_Smtp( $config->notification->smtp->address, array (
					'port' => $config->notification->smtp->port,
					'auth' => 'login',
					'username' => $config->notification->smtp->username,
					'password' => $config->notification->smtp->password,
					'ssl' => $config->notification->smtp->ssl
				) );
			}
			return $this->_transport;
		}

		/**
		 * Sets email subject by template.
		 * @param string $name - template name.
		 * @return D_Controller_Action_Helper_Email
		 */
		public function setSubject( $name )
		{
			$setting = Model::_( 'settings' )->getSetting( $name . '[email subject]' );
			if ( $setting ) {
				$subject = Model::_( 'settings' )->getValue( $setting->id, 'string' );
				if ( $subject ) {
					$this->getMailer()
						->clearSubject()
						->setSubject( $subject );
				}
			}
			return $this;
		}

		/**
		 * Forms extended template name and returns it if file exists.
		 * @param object $plugin - plugin.
		 * @param string $path - template folder path.
		 * @param string $name - template base name.
		 * @return string
		 */
		public function formTemplateName( $plugin, $path, $name )
		{
			$separator = '__';
			$path = realpath( $path ) . '/';
			$filter = new D_Filter_PluginDirectory();
			$pluginPrefix = str_replace( '-', '_', $filter->filter( $plugin->name ) );
			if ( is_file( $path . $pluginPrefix . $separator . $plugin->platform . $separator . $name ) ) {
				$name = $pluginPrefix . $separator . $plugin->platform . $separator . $name;
			} else if ( is_file( $path . $plugin->platform . $separator . $name ) ) {
				$name = $plugin->platform . $separator . $name;
			}
			return $name;
		}
	}
