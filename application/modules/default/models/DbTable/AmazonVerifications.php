<?php
/**
 * Amazon verifications db table.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Default
 * @version 1.0.1
 */
class Default_Model_DbTable_AmazonVerifications extends D_Db_Table_Abstract
{
	protected $_name = 'amazon_verifications';

	/**
	 * statuses of verification response
	 */
	const STATUS_SUCCESS = 'Success';
	const STATUS_PENDING = 'Pending';
	const STATUS_FAILED = 'Failed';
	const STATUS_TEMPORARY_FAILURE = 'TemporaryFailure';
	const STATUS_NOT_STARTED = 'NotStarted';

	/**
	 * object of amazon api client
	 * @var
	 */
	protected $_amazonSES;

	/**
	 * get row by email
	 * @param $email
	 * @return null|Zend_Db_Table_Row_Abstract
	 */
	public function findByEmail($email){
		return $this->fetchRow(
			$this->select()
				->from(
					$this->info( 'name' ),
					array ( '*' )
				)
				->where('email = ? ',$email)
		);
	}

	/**
	 * Is email address verified by Amazon SES
	 *
	 * @param string  $email
	 * @return bool
	 */
	public function isVerified($email)
	{
		$email = $this->findByEmail($email);

		return (isset($email->status) && $email->status == self::STATUS_SUCCESS);
	}


	/**
	 * get all emails which are not verified
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function findNotVerified()
	{
		return $this->fetchAll(
			$this->select()
				->from(
					$this->info( 'name' ),
					array ( '*' )
				)
				->where('status != ? ',self::STATUS_SUCCESS)
				->limit(99)
		);
	}

	/**
	 * send multiple email verification request
	 * @param null $emails
	 * @return array
	 */
	public function updateStatus($emails = null)
	{
		$res = array(
			'total' => 0,
			'updated' => 0
		);

		if (!$emails) {
			$emails =  $this->findNotVerified();
		}

		$list = array();
		foreach ( $emails as $email ) {
			$list[$email->email] = array(
				'id' => $email->id,
				'status' => $email->status,
				'object' => $email
			);
		}

		if (empty($list)) {
			return $res;
		}

		$resObj = $this->getAmazonSES()
			->getIdentityVerificationAttributes(array_keys($list));

		$collection = $resObj->body
			->GetIdentityVerificationAttributesResult
			->VerificationAttributes;

		if (isset($resObj->body
			->GetIdentityVerificationAttributesResult
			->VerificationAttributes))
		{
			$updated = 0;
			foreach ($collection->entry as $emailInfo) {
				$emailKey = (string)$emailInfo->key;
				if ( $emailInfo->value->VerificationStatus != $list[$emailKey]['status'] ) {
					$row = $list[$emailKey]['object'];
					if ($row->save(array(
							'status' => (string)$emailInfo->value->VerificationStatus
					))) {
						$updated ++;
					}
				}
			}

			$res = array(
				'total' => count($list),
				'updated' => $updated
			);
		} else {
			if (isset($resObj->body->Error)) {
				$res['error'] = sprintf(
					'Error(%s): %s - %s',
					(string)$resObj->body->Error->Type,
					(string)$resObj->body->Error->Code,
					(string)$resObj->body->Error->Message
				);
			} else {
				$res['error'] = 'Unknown error.';
			}
		}

		return $res;
	}

	/**
	 * check is email just exists in list. send verification query if not
	 * @param $email
	 * @return bool
	 */
	public function checkEmail($email)
	{
		$res = true;
		$oldEmail = $this->findByEmail($email);

		if (empty($oldEmail)) {

			$this->getAmazonSES()->verify_email_identity($email);
			$email = $this->createRow(array(
				'email' => $email,
				'status' => self::STATUS_PENDING
			));
			$res = $email->save();
		}

		return $res;
	}

	/**
	 * Is Email under verification process or verified
	 *
	 * @param string $email
	 * @return bool
	 */
	public function isRegistered($email)
	{
		$count = $this->fetchAll(
			$this->select()
				->from(
					$this->info( 'name' ),
					array ( 'count(*)' )
				)
				->where('email = ? ',$email)
		);

		return (bool)$count;
	}

	/**
	 * get client api
	 * @return Zend_Service_Amazon_Ses
	 */
	protected function getAmazonSES()
	{
		if (empty($this->_amazonSES)) {
			$config = Config::getInstance();
			require_once(ROOT_PATH . '/library/AmazonSes/sdk.class.php');
			//Zend_Loader::loadClass( 'CFLoader', ROOT_PATH . '/library/AmazonSes' );
			$this->_amazonSES =  new AmazonSES(array(
				$config->notification->smtp->username,
				$config->notification->smtp->password
			));
		}

		return $this->_amazonSES;
	}
}
