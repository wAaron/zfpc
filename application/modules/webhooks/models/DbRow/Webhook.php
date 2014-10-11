<?php
/**
 * DbRow of webhooks DbTable.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Webhooks
 * @version 1.0.0
 */
class Webhooks_Model_DbRow_Webhook extends Zend_Db_Table_Row
{
    /**
     * install webhook via platform api
     * @param $apiClient
     * @param $callbackUrl
     * @return bool
     */
    public function install($apiClient, $callbackUrl, $platform = 'shopify')
    {
        try{
            //send request
            $response = $this->installShopify($apiClient, $callbackUrl);
            if(!$response)
                throw new Exception('');

            //if everything is ok
            $this->webhook_id =  $response->id;
            $this->parent_id = 0;
            $this->registered = 1;
            $this->save();
            $result = true;

        }catch(Exception $e){
            $result = false;
        }

        return $result;
    }

    /**
     * install webhook via api
     * @param $apiClient
     * @param $callbackUrl
     * @return Std Class Object
     */
    public function installShopify($apiClient, $callbackUrl)
    {
        //request to api
        $response = $apiClient->post('webhooks',null,array(
            'webhook' => array(
                'format' => 'json',
                'topic' => $this->topic,
                'address' => $callbackUrl,
            )
        ));
        //parse data
        if(!isset($response->id)){
            $response = $response->webhook;
        }

        return $response;
    }


	/**
	 * check if it has a similar webhook just registered via api
	 * @return $this
	 */
	public function checkParent()
	{
		if( !$this->isSystem() ){
			//check is this webhook registered for current domain, so we do not need to register the second one
			$registeredWebhook = $this->_table->getWebhookByParams( array(
				'topic' => $this->topic,
				'domain' => $this->domain,
				'registered' => 1
			), $this->id );

			if( $registeredWebhook ){
				//add link to the same webhook which just has been registered
				$this->parent_id = $registeredWebhook->id;
				$this->registered = 0;
				$this->save();
			}
		}

		return $this;
	}


	/**
	 * check is the webhook system (uninstall app)
	 * @return bool
	 */
	public function isSystem()
	{
		//todo get out system topic
		if($this->topic == 'app/uninstalled' ){
			$result = true;
		}else{
			$result = false;
		}

		return $result;
	}
}
