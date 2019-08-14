<?php
/**
 * NetPay_Netpayonlinepayments extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * NetPay default helper
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author		NetPay Support
 * @copyright  	Copyright (c) 2014
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */

class NetPay_Netpayonlinepayments_Helper_Data extends Mage_Core_Helper_Abstract
{
	private $testGatewayUrlHostedForm  = "https://hostedtest.revolution.netpay.co.uk/v1/gateway/payment";
	private $liveGatewayUrlHostedForm  = "https://hosted.revolution.netpay.co.uk/v1/gateway/payment";
	
	private $apiIntegrationLiveUrl  = "https://integration.revolution.netpay.co.uk/v1/";
	private $apiIntegartionTestUrl  = "https://integrationtest.revolution.netpay.co.uk/v1/";
	
	/**
	  * @Purpose : Function to return Payment Gateway Action Url for Hosted Payment Method
	  * @Outputs : Return Action Url for Hosted Payment Method as per transaction mode ( Live / Test) 
	  * @author  : NetPay Development Team
	 */
	public function getActionUrl() {
		
		$paymentMode = Mage::getModel('netpayonlinepayments/direct')->getConfigData('mode');
		$url = '';
		switch ($paymentMode)
    	{
    		case NetPay_Netpayonlinepayments_Model_Source_PaymentMode::PAYMENT_LIVE:
    			$url = $this->liveGatewayUrlHostedForm;
   			break;
			case NetPay_Netpayonlinepayments_Model_Source_PaymentMode::PAYMENT_TEST:
    			$url = $this->testGatewayUrlHostedForm;
   			break;
    	}
		return $url;
	}
	
	/**
	  * @Purpose : Function to return Payment Gateway Action Url for API Method
	  * @Outputs : Return Action Url for API Method as per transaction mode ( Live / Test) 
	  * @author  : NetPay Development Team
	 */
	public function getApiIntegartionUrl() {
		
		$paymentMode = Mage::getModel('netpayonlinepayments/netpayapi')->getConfigData('mode');
		$url = '';
		switch ($paymentMode)
    	{
    		case NetPay_Netpayonlinepayments_Model_Source_PaymentMode::PAYMENT_LIVE:
    			$url = $this->apiIntegrationLiveUrl;
   			break;
			case NetPay_Netpayonlinepayments_Model_Source_PaymentMode::PAYMENT_TEST:
    			$url = $this->apiIntegartionTestUrl;
   			break;
    	}
		return $url;
	}	
	
	/**
	  * @Purpose : Function to return Merchant Id set from configuration section
	  * @Outputs : Return Merchant Id 
	  * @author  : NetPay Development Team
	 */
	public function getMerchantId() {
		return Mage::getStoreConfig('payment/netpaysettings/merchantid');
	}

	/**
	  * @Purpose : Function to return Api User name set from configuration section
	  * @Outputs : Return Api User name 
	  * @author  : NetPay Development Team
	 */
	public function getApiUsername() {
		return Mage::getStoreConfig('payment/netpaysettings/username');
	}

	/**
	  * @Purpose : Function to return Api Password set from configuration section
	  * @Outputs : Return Api Password 
	  * @author  : NetPay Development Team
	 */
	public function getApiPassword() {
		return Mage::getModel('netpayonlinepayments/netpayapi')->getConfigData('password');
	}
	
	/**
	  * @Purpose : Function to return response url where hosted from method response will be submitted from gateway after transaction
	  * @Outputs : return Response Url 
	  * @author  : NetPay Development Team
	 */
	public function getResponseUrl() {
		
		$url = Mage::getBaseUrl() . 'netpayonlinepayments/payment/response';
		$url =  $this->getEncryptStr($url);
		return $url;
	}
	
	/**
	  * @Purpose : Function to return Encryption Key set from configuration section
	  * @Outputs : Return Encryption Key for hosted from method
	  * @author  : NetPay Development Team
	 */
	public function getBackendResponseMode() {
		
		$responseMode = Mage::getModel('netpayonlinepayments/direct')->getConfigData('backendresponse');
		$encResponse =  $this->getEncryptStr($responseMode);
		return $encResponse;
	}	
	
	/**
	  * @Purpose : Function to return Encryption Key set from configuration section
	  * @Outputs : Return Encryption Key for hosted from method
	  * @author  : NetPay Development Team
	 */
	public function getEncryptionKey() {
		
		$key = Mage::getModel('netpayonlinepayments/direct')->getConfigData('encryption');
		return $key;
	}
	
	/**
	  * @Purpose : Function to return Encryption Vector set from configuration section
	  * @Outputs : Return Encryption Vector for hosted from method
	  * @author  : NetPay Development Team
	 */
	public function getEncryptionVector() {
		
		$vector = Mage::getModel('netpayonlinepayments/direct')->getConfigData('encryption_vector');
		return $vector;
	}
	
	/**
	  * @Purpose : Function to encrypt string
	  * @Inputs  : String 
	  * @Outputs : Return encrypted string
	  * @author  : NetPay Development Team
	 */
	public function getEncryptStr($str) {
		
		$key = $this->getEncryptionKey();
		$vector = $this->getEncryptionVector();
		
		$str = $this->mcrypt_encrypt_cbc($str, $key, $vector);
		return $str;
	}
	
	/**
	  * @Purpose : Function to decrypt string
	  * @Inputs  : String 
	  * @Outputs : Return decrypted string
	  * @author  : NetPay Development Team
	 */
	public function getDecryptStr($str) {
		
		$key = $this->getEncryptionKey();
		$vector = $this->getEncryptionVector();
		
		$str = $this->mcrypt_decrypt_cbc($str, $key, $vector);
		return $str;
	}	
	
	/**
	  * @Purpose : Function to encrypt token and other data for API payment method
	  * @Inputs  : String 
	  * @Outputs : Return encrypted string
	  * @author  : NetPay Development Team
	 */
	public function getEncryptToken($token) {
		
		//Used constant Key and vector as in API method this will be not provided by gateway.
		//$key 	= "8353aa1cff921b54f7aaea97a7c06e18";
		//$vector = "6a2ed0ff2e01c61ce2032d01c91d9b8c";
		$keyDaya = $this->readEncryptionKeys();
		$key 	= $keyDaya['encryption_key'];
		$vector = $keyDaya['encryption_iv'];

		$str = $this->mcrypt_encrypt_cbc($token, $key, $vector);
		return $str;
	}
	
	/**
	  * @Purpose : Function to decrypt token and other data for API payment method
	  * @Inputs  : String 
	  * @Outputs : Return decrypted string
	  * @author  : NetPay Development Team
	 */
	public function getDecryptToken($token) {
		
		//Used constant Key and vector as in API method this will be not provided by gateway.
		//$key 	= "8353aa1cff921b54f7aaea97a7c06e18";
		//$vector = "6a2ed0ff2e01c61ce2032d01c91d9b8c";
		$keyDaya = $this->readEncryptionKeys();
		$key 	= $keyDaya['encryption_key'];
		$vector = $keyDaya['encryption_iv'];
		
		$str = $this->mcrypt_decrypt_cbc($token, $key, $vector);
		return $str;
	}
	
	/**
	  * @Purpose : Create a config file for security keys
	  * @Outputs : generate encryption_key and encryption_iv for payment method
	  * @Action When Called: from set up file
	  * @author  : NetPay Development Team
	*/	
	public function generateUniqueKey() {
		
		$modelDirectory = Mage::getModuleDir('', 'NetPay_Netpayonlinepayments') .DS. 'Model';
		$configFilePath = $modelDirectory .DS. 'Configkey.php';
		try {
		   $fp = fopen($configFilePath,'w');
		   $enc_key = '<?php $security_key="'.md5(uniqid(rand(), TRUE)).'";';
		   $enc_iv  = '$security_iv="'.md5(uniqid(rand(), TRUE)).'";?>';
		   fwrite($fp,trim($enc_key));
		   fwrite($fp, PHP_EOL);
		   fwrite($fp,trim($enc_iv));
		   fclose($fp);
		}
		catch ( Exception $e ) {
			$errorMsg = $e->getMessage();
			Mage::log('Error in generating config file for NetPay Extension');
			Mage::throwException($errorMsg);	
		}
	}	
	
	 /**
	  * @Purpose : Read config file for security keys
	  * @Outputs : set encryption_key and encryption_iv for payment method
	  * @Action When Called : Used only in API integration method only
	  * @author  : NetPay Development Team
	 */
	 public function readEncryptionKeys(){
		$modelDirectory = Mage::getModuleDir('', 'NetPay_Netpayonlinepayments') .DS. 'Model';
		$configFilePath = $modelDirectory .DS. 'Configkey.php';
		include($configFilePath); 
		
		$key = array();
		$key['encryption_key']  = (isset($security_key))?$security_key:''; 
		$key['encryption_iv']  = (isset($security_iv))?$security_iv:''; 
		
		return $key;
		
	 }


		
	

/*----- Fucntions Start Related to encryption & Decryption-----*/	

	/*	MCRYPT ENCRYPTION
	*	MODE CBC
	*/
	public function mcrypt_encrypt_cbc($input, $key, $iv) {
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC); 
		$input = $this->add_pkcs5_padding($input, $size); 
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, ''); 

		mcrypt_generic_init($td, pack('H*',$key), pack('H*', $iv)); 
		$data = mcrypt_generic($td, $input); 
		mcrypt_generic_deinit($td); 
		mcrypt_module_close($td); 
		$data = bin2hex($data);

		return $data; 
	}

	/*	MCRYPT DECRYPTION
	*	MODE CBC
	*/
	public function mcrypt_decrypt_cbc($input, $key, $iv) {
		$decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128, pack('H*', $key), pack('H*', $input), MCRYPT_MODE_CBC, pack('H*', $iv));
		
		return $this->remove_pkcs5_padding($decrypted);
	}

	/*	OPENSSL ENCRYPTION
	*	MODE CBC
	*/
	public function openssl_encrypt_cbc($input, $key, $iv, $method) {
		$encrypted = openssl_encrypt($input, $method, pack('H*',$key), TRUE, pack('H*',$iv));

		$encrypted_hex = bin2hex($encrypted);

		return $encrypted_hex;
	}

	/*	OPENSSL DECRYPTION
	*	MODE CBC
	*/
	public function openssl_decrypt_cbc($input, $key, $iv, $method) {
		$decrypted = openssl_decrypt(pack('H*', $input), $method, pack('H*',$key), true, pack('H*',$iv));

		return $decrypted;
	}

	/*
	*	ADD PKCS5 PADDING
	*/
	private function add_pkcs5_padding($text, $blocksize) { 
		$pad = $blocksize - (strlen($text) % $blocksize); 
		return $text . str_repeat(chr($pad), $pad); 
	} 

	/*
	*	REMOVE PKCS5 PADDING
	*/
	private function remove_pkcs5_padding($decrypted) { 
		$dec_s = strlen($decrypted); 
		$padding = ord($decrypted[$dec_s-1]); 
		$decrypted = substr($decrypted, 0, -$padding);

		return $decrypted;
	}

	/*
	*	Add timestamp to transaction id
	*/
	public function create_unique_transaction_id($transaction_id) {
		return strtolower($transaction_id) . time();
	}

	/*
	*	Create token with combination of merchant_id, timestamp and transaction_id
	*/
	public function create_unique_session_token($merchant_id, $transaction_id) {
		return strtolower($merchant_id) . time() . strtolower($transaction_id);
	}

	public function parse_response_url($response) {
		preg_match_all('/\{([^}]*)\}/', $response, $matches);

		$parsed_url = array();
		foreach($matches[1] as $match) {
		    list($key, $value) = explode('|', $match);
		    $parsed_url[$key] = $value;
		}

		return $parsed_url;
	}

/*----- Fucntions End Related to encryption & Decryption-----*/	
	
	function getErrorMessageByCode($code) {
		
			$messages = array(
				//PURCHSE RESPONSE
				'APPROVED' => 'Transaction Approved',
				'SUBMITTED' => 'Transaction submitted - response has not yet been received',
				'PENDING' => 'Transaction is pending',
				'APPROVED_PENDING_SETTLEMENT' => 'Transaction Approved - pending batch settlement',
				'UNSPECIFIED_FAILURE' => 'Transaction could not be processed',
				'DECLINED' => ' Transaction declined by issuer',
				'TIMED_OUT' => ' Response timed out',
				'EXPIRED_CARD' => 'Transaction declined due to expired card',
				'INSUFFICIENT_FUNDS' => 'Transaction declined due to insufficient funds',
				'ACQUIRER_SYSTEM_ERROR' => 'Acquirer system error occurred processing the transaction',
				'SYSTEM_ERROR' => 'Internal system error occurred processing the transaction',
				'NOT_SUPPORTED' => 'Transaction type not supported',
				'DECLINED_DO_NOT_CONTACT' => 'Transaction declined - do not contact issuer',
				'ABORTED' => 'Transaction aborted by payer',
				'BLOCKED' => 'Transaction blocked due to Risk or 3D Secure blocking rules',
				'CANCELLED' => 'Transaction cancelled by payer',
				'DEFERRED_TRANSACTION_RECEIVED' => 'Deferred transaction received and awaiting processing',
				'REFERRED' => 'Transaction declined - refer to issuer',
				'AUTHENTICATION_FAILED' => '3D Secure authentication failed',
				'INVALID_CSC' => 'Invalid card security code',
				'LOCK_FAILURE' => 'Order locked - another transaction is in progress for this order',
				'NOT_ENROLLED_3D_SECURE' => 'Card holder is not enrolled in 3D Secure',
				'EXCEEDED_RETRY_LIMIT' => 'Transaction retry limit exceeded',
				'DUPLICATE_BATCH' => 'Transaction declined due to duplicate batch',
				'DECLINED_AVS' => 'Transaction declined due to address verification',
				'DECLINED_CSC' => 'Transaction declined due to card security code',
				'DECLINED_AVS_CSC' => 'Transaction declined due to address verification and card security code',
				'DECLINED_PAYMENT_PLAN' => 'Transaction declined due to payment plan',
				'UNKNOWN' => 'Response unknown',
				//FOR 3DS RESPONSE
				//For hosted payment
				'CARD_NOT_ENROLLED' => 'The card is not enrolled for 3DS authentication',
				'AUTHENTICATION_NOT_AVAILABLE' => 'Authentication is not currently available',
				'AUTHENTICATION_FAILED' => '3DS authentication failed',
				'AUTHENTICATION_ATTEMPTED' => 'Authentication was attempted but the card issuer did not perform the authentication',
				'CARD_DOES_NOT_SUPPORT_3DS' => 'The card does not support 3DS authentication');
				
			if(array_key_exists($code, $messages)) {
				return $messages[$code];
			} else {
				return '';
			}			
	}



}