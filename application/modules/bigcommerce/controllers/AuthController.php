<?php
	/**
	 * BigCommerce user authentication.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package BigCommerce
	 * @version 1.2.0
	 */
	class Bigcommerce_AuthController extends D_Controller_InnerAuth_Auth
	{
		protected $_platform = 'Bigcommerce';

		/**
		 * @internal Overrode
		 */
		public function indexAction()
		{
			parent::indexAction();
			$this->view->descrKey = 'bc auth index page description';
			$this->view->formRegister = false;
		}

		/**
		 * @internal Overrode
		 */
		public function accountAction()
		{
			parent::accountAction();
			$this->view->setScriptPath(
				APPLICATION_PATH . '/modules/bigcommerce/views/scripts'
			);
		}
	}
