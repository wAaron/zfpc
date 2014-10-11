<?php
/**
 * E-commers platforms webhooks acceptor.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Default
 * @version 1.0.1
 */
class Webhooks_AcceptController extends D_Webhooks_Controller_Abstract
{
    /**
     * save incoming sh webhooks to db
     */
    public function shopifyAction()
    {
        try {
            //check is this request from shopify
            if( !isset( $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] ) )
                throw new Exception( 'empty shop domain name' );

            //save webhook
            $webhooksTable = Table::_( 'webhooksInc' );
            $webhooksTable->createRow( array(
                'domain' => $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'],
                'hmac' => isset( $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ) ? $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] : null,
                'topic' => $_SERVER['HTTP_X_SHOPIFY_TOPIC'],
                'headers' => json_encode( $this->_getShopifyHeaders( $_SERVER ) ),
                'body' => $this->getRequestBody(),
                'create_time' => date( LOCAL_DATETIME_FORMAT ),
                'plugin_id' => $this->_getParam( 'id', 0 )
            ) )
                ->save();

            //send response to sh
            $this->_response->setHttpResponseCode( 200 )
                ->sendResponse();

        } catch( Exception $e ) {
            $this->_logger->err( 'An error occurred: ' . $e->getMessage() );
            $this->_response->setHttpResponseCode( 300 )
                ->sendResponse();
        }
    }

    /**
     * save incoming bc webhooks to db
     */
    public function bigcommerceAction()
    {
    }

    /**
     * get data from request body
     * @return null
     */
    protected function getRequestBody()
    {
        $requestBody = $this->getRequest()->getRawBody();

        return $requestBody ? $requestBody : null;
    }


    /**
     * returns shopify only headers from request headers
     * @param $server
     * @return array
     */
    protected function _getShopifyHeaders( $server )
    {
        //get only shopify headers
        $headers = array();
        foreach( $server as $key => $value ) {
            if( strpos( $key, 'SHOPIFY' ) != false ){
                //make it valid
                $parts = explode( '_', $key );
                $validKey = '';
                foreach( $parts as $part ) {
                    $validKey .= ucfirst( strtolower( $part ) ) . '-';
                }
                $validKey = str_replace('Http-','',$validKey);
                $headers[rtrim( $validKey, '-' )] = $value;
            }
        }

        return $headers;
    }
}
