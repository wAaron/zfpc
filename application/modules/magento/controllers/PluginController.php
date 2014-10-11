<?php
	/**
	 * Magento plugins.
	 *
	 * @author Kovalev Yury, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Magento
	 * @version 1.0.0
	 */
	class Magento_PluginController extends D_Controller_InnerAuth_Plugin
	{
		protected $_platform = 'Magento';

		/**
		 * Preparing of Form Values - Overload the parent method
		 * @param $form - credentials form (install, configure)
		 * @return array
		 */
		protected function prepareCredentialsFormValues($form)
		{
			return array (
				'plan_id' => $form->plan_id->getValue(),
				'api_key' => $form->api_key->getValue(),
				'api_user' => $form->api_user->getValue(),
				'store_view' => $form->store_view->getValue()
			);
		}
	}
