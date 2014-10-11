<?php
/**
 * Cron activity collector.
 *
 * @author Kuksanau Ihnat
 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Default
 * @version 1.0.1
 */
class Default_EmailController extends Zend_Controller_Action
{
    /**
     * Initialization.
     */
    public function init()
    {
        $this->_helper->layout->disableLayout();
        $this->getHelper( 'ViewRenderer' )->setNoRender( true );
    }

    /**
     * save incoming email to db
     */
    public function addemailAction()
    {
        $logger = Zend_Registry::get( 'logger' );
        //reformat data
        $data = $this->_prepareRequestData( $this->_getAllParams() );

        //check email from, send verification request if it is necessary
        Table::_( 'amazon_verifications' )->checkEmail( $data['emailFrom'] );

        //save email to email queue table
        if( !Table::_( 'emails' )->saveEmail( $data ) ){
            $logger->err( 'Email has not been sent. ' .
                ' platform: ' . $this->_getParam( 'platform' ) .
                ' plugin: ' . $this->_getParam( 'plugin' ) .
                ' email id: ' . $this->_getParam( 'appEmailId' ) );
        }
    }

    /**
     * prepare request data for save to local db
     * @param $data
     * @return mixed
     */
    protected function _prepareRequestData( $data )
    {
        $data['callbackUrl'] = isset( $data['callbackUrl'] ) ? base64_decode( $data['callbackUrl'] ) : null;
        $data['emailFromName'] = isset( $data['emailFromName'] ) ? base64_decode( $data['emailFromName'] ) : null;
        $data['emailSubject'] = base64_decode( $data['emailSubject'] );
        $data['emailMessage'] = base64_decode( $data['emailMessage'] );

        //get platform id
        $platform = Table::_( 'platforms' )->get( $data['platform'] );
        $data['platform_id'] = $platform ? $platform->id : 0;

        //get plugin id
        $modelPayment = new Payment_Model_Payment();
        $plugin = $modelPayment->getPlugin( $data['platform'], $data['plugin'] );
        $data['plugin_id'] = $plugin ? $plugin->id : 0;

        return $data;
    }

    /**
     * run amazon verification process
     * @internal Cron action.
     */
    public function amazonVerificationAction()
    {
        // Start cron.
        $this->getHelper( 'admin' )
            ->startCronTask( 'pc-amazon-verification' );

        $logger = Zend_Registry::get( 'logger' );

        //start verification process
        $res = Table::_( 'amazon_verifications' )->updateStatus();
        if( isset( $res['error'] ) ){
            $logger->err( "Emails verification interrupted: {$res['error']}" );
        }

        // Stop cron.
        $this->getHelper( 'admin' )
            ->stopCronTask( 'pc-amazon-verification' );
    }

    /**
     * send emails from queue
     * for 2 kinds of cron: 1 - without priority, 2 - sends emails with priority equal or higher than param 'emailPriority'
     * the second one starts more frequently
     * @internal Cron action.
     */
    public function emailSenderAction()
    {
        $priority = $this->_getParam( 'emailPriority', 0 );
        // Start cron.
        $this->getHelper( 'admin' )
            ->startCronTask( 'pc-email-sender-' . $priority );

        $config = Config::getInstance();
        $logger = Zend_Registry::get( 'logger' );
        $defaultSmtpTransport = $this->getHelper( 'email' )->getTransport();

        $i = 0;
        //get list of email by priority
        $emails = Table::_( 'emails' )->getListToMailer( $priority );

        foreach( $emails as $email ) {
            try {
                //check api call limit
                if( $i >= $config->notification->smtp->maxRequests ){
                    $logger->info( 'Api call limit has been exceeded.' );
                    break;
                }
                //send request. the mailer throws exception in case of fail
                $this->_sendEmail( $email, $defaultSmtpTransport, $config );

                $email->sent_time = date( LOCAL_DATETIME_FORMAT );
                $email->status = 'sent';

                //send hook onEmailSend to the app
                if( $email->callback_url ){
                    $url = $email->app_email_id ? $email->callback_url . '?id=' . $email->app_email_id : $email->callback_url;
                    $client = new Zend_Http_Client( $url, array(
                        'adapter' => 'Zend_Http_Client_Adapter_Curl',
                    ) );
                    $client->request( 'GET' );
                }
            } catch( Exception $e ) {
                $email->status = 'error';
                $logger->err( 'Email has not been sent. id: ' . $email->id . ' ' . $e->getMessage() );
            }

            $email->save();
            $i++;
            usleep( $config->notification->smtp->timeout );
        }

        // Stop cron.
        $this->getHelper( 'admin' )
            ->stopCronTask( 'pc-email-sender-' . $priority );
    }

    /**
     * clear old sent emails process
     * @internal Cron action.
     */
    public function clearEmailsAction()
    {
        // Start cron.
        $this->getHelper( 'admin' )
            ->startCronTask( 'pc-clear-emails' );

        //delete old emails
        $config = Config::getInstance();
        $deletedQty = Table::_( 'emails' )->deleteOldItems(
            $config->notification->email->expiredDays,
            array( 'sent', 'sent by app' )
        );

        if( $deletedQty ){
            Zend_Registry::get( 'logger' )->info( 'Clear emails process completed. Deleted items: ' . $deletedQty );
        }

        // Stop cron.
        $this->getHelper( 'admin' )
            ->stopCronTask( 'pc-clear-emails' );
    }

    /**
     * send email via amazon ses
     * @param $email
     * @param $defaultSmtpTransport
     * @param $config
     * @return Zend_Mail
     */
    protected function _sendEmail( $email, $defaultSmtpTransport, $config )
    {
        $mailer = new Zend_Mail();
        $mailer->setDefaultTransport( $defaultSmtpTransport );
        //set 'from' header
        if( Table::_( 'amazon_verifications' )->isVerified( $email->from ) ){
            //if 'from' email is verified by amazon ses
            $mailer->setFrom( $email->from, $email->from_name );
        } else{
            $mailer->setFrom( $config->notification->email->notverified, $email->from_name );
        }

        $mailer->addTo( $email->to )
            ->setSubject(
                $email->subject
            )
            ->setBodyHtml( gzuncompress( $email->message ) );

        return $mailer->send();
    }
}