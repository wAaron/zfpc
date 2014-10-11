<?php
/**
 * Abstract class for webhooks controllers.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Default
 * @version 1.0.1
 */
abstract class D_Webhooks_Controller_Abstract extends Zend_Controller_Action
{
    /**
     * webhooks logger object
     * @var null
     */
    protected $_logger = null;

    /**
     * Initialization.
     */
    public function init()
    {
        $this->_helper->layout->disableLayout();
        $this->getHelper( 'ViewRenderer' )->setNoRender( true );
        $this->_logger = Zend_Registry::get('taskLogger');
    }

    /**
     * returns api client
     * @param $webhook
     * @return mixed
     */
    protected function _getApiClient( $pluginID, $userID )
    {
		$shop = Table::_( 'shops' )->getForUser( $userID );
        if($shop->platform == 'shopify'){
            //get token
			$token = Table::_( 'credentials' )->getForPlugin( $pluginID, $userID )->api_key;

            $apiClient = $this->getHelper( 'ShopifyApi' )
                ->initialize( $shop->name, $token );
        }else{
            $apiClient = null;
        }

        return $apiClient;
    }


    /**
     * get callback url for webhooks
     * @param $webhook
     * @param string $platform
     * @return string
     */
    protected function _getCallbackUrl($pluginID, $platform = 'shopify')
    {
        $config = Zend_Registry::get('config');
        return $config->plugin->center->baseUrl .
        	'webhooks/accept/' . $platform . '/id/' . $pluginID;
    }
}
