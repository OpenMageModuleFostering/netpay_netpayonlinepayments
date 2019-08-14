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
 * @category   	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @copyright  	Copyright (c) 2013
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * API Payment Method Payment Model
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author      NetPay Development Team
 */
class NetPay_Netpayonlinepayments_Model_Netpayapi extends Mage_Payment_Model_Method_Cc
{
    protected $_code = 'netpayapi';
 	protected $_formBlockType = 'netpayonlinepayments/api'; 
	
	protected $_canAuthorize		= true;
	protected $_canCapture			= true;
	protected $_isGateway			= true;
    protected $_canOrder			= true;
	protected $_canSaveCc 			= false; //if made try, the actual credit card number and cvv code are stored in database.
	protected $_canUseInternal      = false;
	
	protected $_ssl_path = array(
		'certificate' => '', // Full certificate file path
		'key' => '', // Full certificate file path
		'certificate_pass' => NULL // Optional of TEST MODE is ACCOUNT CODE
	);
	
	
	const OPERATION_TYPE 			= "PURCHASE";
	const GET_TOKEN_OPERATION		= "RETRIEVE_TOKEN";
	const CREATE_TOKEN_OPERATION	= "CREATE_TOKEN";
	const SOURCE_TOKEN				= "TOKEN";
	const CONTENT_TYPE				= "json";
	const SOURCE					= "INTERNET";
	const PAYMENT_TYPE				= "CARD";
	const TOKEN_MODE				= "PERMANENT";
	const TOKEN_MODE_TEMP			= "TEMPORARY";
	const CHECK_3D_ENROLL			= "CHECK_3DS_ENROLLMENT";
	
	/**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
		
		$oldData = $data;
		$dataArray = $data->getData();
		
		$type = $data->getCardType();
		$data = $dataArray[$type];

		if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
		
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear())
            ->setStoreCard($data->getStoreCard())
            ;
 
        return $this;
    }
	
	
	/**
     * Override the core Mage function to validate submitted data
     */ 
	public function validate() {
	
		 return $this;
	}

	
	/**
     * Override the core Mage function to get the URL to be redirected from the Onepage If 3D Secure Enable
     */ 
	public function getOrderPlaceRedirectUrl()
    {
    	$result = false;
       	$session = Mage::getSingleton('checkout/session');
     	$is3dsecureEnable = $this->getConfigData('dsecure');
     	
       	if($session->getDsecureForm() && $is3dsecureEnable) {
       		
	    	$result = Mage::getUrl('netpayonlinepayments/payment/threedsecure', array('_secure' => true));
       	}
       
        return $result;
    }
	
	
	/**
     * Function call when user place order .. Main entry fucntion to process API transaction
     */
	public function order(Varien_Object $payment, $amount)
	{
	
		$order = $payment->getOrder();

		$result = $this->callApi($payment,$amount);

		if(empty($result)) {
			$errorCode = 'Invalid Data';
			$errorMsg = $this->_getHelper()->__('Error Processing the request');
			Mage::throwException($errorMsg);
		
		} else if(!empty($result) && $result['status'] == '3dsecure') {
		
			// Add the comment and save the order
			$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'pending_payment', '3D Secure Auth Now Taking Place')->save();
		
		} else {
		
			//process result here to check status etc as per payment gateway.
			if($result['status'] == 1){
				
				
				$payment = $order->getPayment();
				
				
				if($order->canInvoice()) {
					$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
					
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->register();
					$transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
					$transactionSave->save();
					$invoice->sendEmail(true, '');
					
					$message = 'Result: '.$result['result'].' NetPay Transaction ID: '.$result['transaction_id'].' NetPay Order ID: '.$result['order_id'];
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $message);
				}
				
			} else {
				
				$errorMsg = "Error Processing the request. Error Message: ".$result['message'];
				Mage::throwException($errorMsg);
			}
		}
		return $this;
	}

	
	/**
     * Function to prepare fields to be submited on payment gateway for Purchase call
     */		
	public function _prepareApiFields($order, $secureAPiData = array()){
	
		$payment = $order->getPayment();
		$amount = $order->base_grand_total;
		
		$orderId = $order->getRealOrderId();
	
		$helper = Mage::helper('netpayonlinepayments');
		$fields = array();
	
		$billingAddress = $order->getBillingAddress();
		$billingData = $billingAddress->getData();
		$shippingAddress = $order->getShippingAddress();
		$shippingData = array();
		if ( $shippingAddress )
			$shippingData = $shippingAddress->getData();
	
	//Merchant fields		
		$fields['merchant']['merchant_id'] 		= $helper->getMerchantId();
		$fields['merchant']['operation_type']	= self::OPERATION_TYPE;
		$fields['merchant']['operation_mode']	= $this->getConfigData('mode');

	//Transaction fields				
		$transactionId = $helper->create_unique_transaction_id($orderId);
		$fields['transaction']['transaction_id'] = $transactionId;
        $fields['transaction']['amount'] = number_format(  $amount, 2, '.', '' );
        $fields['transaction']['currency'] = $order->getBaseCurrencyCode();
        //$fields['transaction']['reference'] = '';
        $fields['transaction']['source'] = self::SOURCE; 
        $fields['transaction']['description'] = 'Order Detail'; 
		
	//Payment Source fields		
		
		if(!empty($secureAPiData) && $secureAPiData['isFrom3dSecure'] == true) {
			
			$fields['ddd_secure_id']							= $secureAPiData['ddd_secure_id']; 
			$fields['payment_source']['type']					= self::SOURCE_TOKEN; 
			$fields['payment_source']['token']					= $secureAPiData['token'];
			$fields['payment_source']['card']['security_code']	= $secureAPiData['security_code'];
			
		} else {
		
			$paymentByToken = false;
		
			//Check if payment made by token OR not
			$post = Mage::app()->getRequest()->getParam('payment');
			if(isset($post['card_type']) && $post['card_type'] != 'new') {
				$paymentByToken = true;
				
				$tokenPrimaryId = $post['card_type'];
				$usedToken = $this->getTokenById($tokenPrimaryId);
			
				//$encToken = $post['card_type'];
				//Decrypt Token 
				//$usedToken = $helper->getDecryptToken($encToken);
			}	
		
			$ccMonth = $payment->getCcExpMonth();
			$ccMonth = (strlen($ccMonth) == 1)? '0'.$ccMonth:$ccMonth;
			$ccYear  = substr($payment->getCcExpYear(), 2, 2);
			
			$ccFullName = trim($payment->getCcOwner());
			$ccFullNameArr =  explode(' ', $ccFullName);
			
			if(!empty($ccFullNameArr))
				$ccFirstName = $ccFullNameArr[0];
				
			if(!empty($ccFullNameArr))
				$ccLastName = $ccFullNameArr[count($ccFullNameArr)-1];	
			
			$fields['payment_source']['type']							= self::PAYMENT_TYPE; 
			$fields['payment_source']['card']['card_type']				= $payment->getCcType();
			$fields['payment_source']['card']['number']					= $payment->getCcNumber();
			$fields['payment_source']['card']['expiry_month']			= $ccMonth;
			$fields['payment_source']['card']['expiry_year']			= $ccYear;
			$fields['payment_source']['card']['security_code']			= $payment->getCcCid();
			$fields['payment_source']['card']['holder']['firstname']	= $ccFirstName;
			$fields['payment_source']['card']['holder']['lastname']		= $ccLastName;
			$fields['payment_source']['card']['holder']['fullname']		= $payment->getCcOwner();
			
			if($paymentByToken) {
				//If payment by token set new fields
				$fields['payment_source']['type']	= self::SOURCE_TOKEN; 
				$fields['payment_source']['token']	= $usedToken;
				
				//Unset not required fields
				unset($fields['payment_source']['card']['card_type'], $fields['payment_source']['card']['number'], $fields['payment_source']['card']['expiry_month'], $fields['payment_source']['card']['expiry_year'], $fields['payment_source']['card']['holder']);
			}	
		}	
		

	//Billing fields		
		$fields['billing']['bill_to_company'] = substr(trim($billingData['company']), 0, 100);
		$fields['billing']['bill_to_address'] = substr(trim(preg_replace("#[^A-Za-z0-9\. ',()\-]+#", '', $billingData['street'])), 0, 100);
		$fields['billing']['bill_to_town_city'] = substr(trim(preg_replace("#[^A-Za-z0-9\. ',()\-]+#", '', $billingData['city'])), 0, 50);
		$fields['billing']['bill_to_county'] = substr(trim(preg_replace("#[^A-Za-z0-9\. ',()\-]+#", '', $billingData['region'])), 0, 50);
		$fields['billing']['bill_to_postcode'] = substr(trim(preg_replace("#[^A-Za-z0-9\. ',()\-]+#", '', $billingData['postcode'])), 0, 10);
		$fields['billing']['bill_to_country'] = substr(trim(preg_replace("#[^A-Za-z]{3}#", '', Mage::getModel( 'directory/country' )->load( $billingData['country_id'] )->getData('iso3_code'))), 0, 3);
		
		//Removing empty values from array
		foreach($fields['billing'] as $key=>$value) {
			if($fields['billing'][$key] == '') unset($fields['billing'][$key]);
		}
	
		//Shipping Address
		if(!empty($shippingData)) {		
		
			$fullName = $shippingData['firstname'];
			if($shippingData['middlename'] != '')
				$fullName .= ' '.$shippingData['middlename'];
			
			$fullName .= ' '.$shippingData['lastname'];			
		
			$fields['shipping']['ship_to_title']		= substr(trim(preg_replace("#[^A-Za-z\. ]+#", '', $shippingData['prefix'])), 0, 20);
			$fields['shipping']['ship_to_firstname'] 	= substr(trim(preg_replace("#[^A-Za-z'\.\- ]+#", '', $shippingData['firstname'])), 0, 50);
			$fields['shipping']['ship_to_middlename'] 	= substr(trim(preg_replace("#[^A-Za-z'\.\- ]+#", '', $shippingData['middlename'])), 0, 50);
			$fields['shipping']['ship_to_lastname'] 	= substr(trim(preg_replace("#[^A-Za-z'\.,\- ]+#", '', $shippingData['lastname'])), 0, 50);
			$fields['shipping']['ship_to_fullname'] 	= substr(trim(preg_replace("#[^A-Za-z'\.\- ]+#", '', $fullName)), 0, 100);
			$fields['shipping']['ship_to_company'] 	= substr(trim($shippingData['company']), 0, 100);
			$fields['shipping']['ship_to_address'] 	= substr(trim(preg_replace("#[^A-Za-z0-9\. ',()\-]+#", '', $shippingData['street'])), 0, 100);
			$fields['shipping']['ship_to_town_city'] 		= substr(trim(preg_replace("#[^A-Za-z0-9\. ',()\-]+#", '', $shippingData['city'])), 0, 50);
			$fields['shipping']['ship_to_county'] 		= substr(trim(preg_replace("#[^A-Za-z0-9\. ',()\-]+#", '', $shippingData['region'])), 0, 50);
			$fields['shipping']['ship_to_country'] 	= substr(trim(preg_replace("#[^A-Za-z]{3}#", '', Mage::getModel( 'directory/country' )->load( $shippingData['country_id'] )->getData('iso3_code'))), 0, 3);
			//$fields['shipping']['ship_to_method'] 		= $order->getShippingDescription();
			$fields['shipping']['ship_to_phone']= substr(trim(preg_replace("#[^0-9 \+()\- \.]+#", '', $shippingData['telephone'])), 0, 20);
			
		} else {
			$fullName = $billingData['firstname'];
			if($billingData['middlename'] != '')
				$fullName = ' '.$billingData['middlename'];
			
			$fullName = ' '.$billingData['lastname'];			
		
			$fields['shipping']['ship_to_title'] 		= substr(trim(preg_replace("#[^A-Za-z\. ]+#", '', $billingData['prefix'])), 0, 20);
			$fields['shipping']['ship_to_firstname'] 	= substr(trim(preg_replace("#[^A-Za-z'\.\- ]+#", '', $billingData['firstname'])), 0, 50);
			$fields['shipping']['ship_to_middlename'] 	= substr(trim(preg_replace("#[^A-Za-z'\.\- ]+#", '', $billingData['middlename'])), 0, 50);
			$fields['shipping']['ship_to_lastname'] 	= substr(trim(preg_replace("#[^A-Za-z'\.,\- ]+#", '', $billingData['lastname'])), 0, 50);
			$fields['shipping']['ship_to_fullname'] 	= substr(trim(preg_replace("#[^A-Za-z'\.\- ]+#", '', $fullName)), 0, 100);
			$fields['shipping']['ship_to_phone']		= substr(trim(preg_replace("#[^0-9 \+()\- \.]+#", '', $billingData['telephone'])), 0, 20);	
		}
		
		//Removing empty values from array
		foreach($fields['shipping'] as $key=>$value) {
			if($fields['shipping'][$key] == '') unset($fields['shipping'][$key]);
		}
		
		
	//Customer fields		
		$fields['customer']['customer_email'] = $order->getCustomerEmail();
        
		if(trim($billingData['telephone']) != '')
			$fields['customer']['customer_phone'] = substr(trim(preg_replace("#[^0-9 \+()\- \.]+#", '', $billingData['telephone'])), 0, 20);
		
	//Order Items
		$items = $order->getAllItems();
		$orderedItems = '';
		$i=0;
		foreach($items as $item) {
			$qty = (int) $item->getQtyOrdered();
			$sku = $item->getData('sku');
			$name = $item->getData('name');
			$description = $item->getData('description');
			
			if($description == '')
				$description = $name;
			
			$price = number_format(  $item->getData('price'), 2, '.', '' );
			$taxable = 1;
			
			$fields['order']['order_items'][$i]['item_id'] = $sku;
			$fields['order']['order_items'][$i]['item_name'] = $name;
			$fields['order']['order_items'][$i]['item_description'] = $description;
			$fields['order']['order_items'][$i]['item_quantity'] = $qty;
			$fields['order']['order_items'][$i]['item_price'] = $price;
			$i++;	
		}
		
		return $fields;
	}
	
	
	/**
     * Function to check all possible case to make sure about 3d secure Or direct purchase call
     */
	public function callApi(Varien_Object $payment, $amount){

		$order = $payment->getOrder();
		$orderId = $order->getRealOrderId();
		
		$saveToken = false;
		$paymentByToken = false;
		
		//If user want to save card information for future use
		$post = Mage::app()->getRequest()->getParam('payment');
		if(isset($post['store_card']) && $post['store_card'] == 1) {
			$saveToken = true;
		}
		
		//Check if payment made by token OR not
		if(isset($post['card_type']) && $post['card_type'] != 'new') {
			$saveToken = false;
			$paymentByToken = true;
		}
		
		$fields = $this->_prepareApiFields($order);
		
		$is3dSecureEnable = $this->getConfigData('dsecure');
		
		if($is3dSecureEnable) {
			
			$modePay = "card";
			if($paymentByToken) $modePay = "token";

			$enrollResponse = $this->checkCard3dSecureEnrollment($payment, $fields, $modePay);	
			
			if($enrollResponse == false) {
				
				$errorMsg = $this->_getHelper()->__('Error Processing the request');
				Mage::throwException($errorMsg);	
				
			} else if($enrollResponse == "3d") {
			
				$return['status'] = '3dsecure';
				return $return;
			
			} else if($enrollResponse == "continue_purchase") {

			}
		}		
		
		
		//Process request
		$response = $this->_sendRequest($fields);		
		
		$return = array();
		if($response->result == 'SUCCESS') { //Payment Success
			
			$return['transaction_id'] 		= $response->transaction->transaction_id;
			$return['terminal'] 			= $response->transaction->terminal;
			$return['authorization_code']	= $response->transaction->authorization_code;
			$return['receipt'] 				= $response->transaction->receipt;
			$return['order_id'] 			= $response->order->order_id;
			$return['result']				= $response->result;;
			
			$return['status'] = 1;
			
			//Call create token as user check to store details 
			if($saveToken) { 
				$this->createToken($payment, $fields);
			}
			
			//Capture Gateway Response in response table for future use.
			$dataResponse['method'] = $this->_code;		
			$dataResponse['response'] = json_encode($response);
			$dataResponse['order_id'] = $order->getId();
			$this->captureResponse($dataResponse);
			
			
		
		} else if($response->result == 'ERROR') {  //Payment Error
			
			$return['code'] = $response->error->code;
			$return['cause'] = $response->error->cause;
			$return['message'] = $response->error->explanation;
			
			$return['status'] = 0;
		} else if($response->result == 'FAILURE') {  //Payment FAILURE Error
			
			$errorCode = $response->response->gateway_code;
			
			$return['code'] 	= $errorCode;
			$return['message']	= Mage::helper('netpayonlinepayments')->getErrorMessageByCode($errorCode);
			
			$return['status'] = 0;
		}		
		
		return $return;
	
	}

	
	/**
     * Function to check Card 3dSecure Enrollment
     */		
	public function checkCard3dSecureEnrollment(Varien_Object $payment, $fields, $modePay) {
		//unset not required fields for request	
		$fieldsOri = $fields;	
		$retun = '';
		
		$order = $payment->getOrder();
		$orderId = $order->getRealOrderId();
		
		unset($fields['billing'], $fields['shipping'], $fields['customer'], $fields['order']);
		$transactionId = $fields['transaction']['transaction_id'];
		
		if($modePay == "token") {
			
			$securityCode = $fields['payment_source']['card']['security_code'];			
			unset($fields['payment_source']['card'], $fields['transaction']['transaction_id'], $fields['transaction']['description']);
			
		} else if($modePay == "card") {
			
			unset($fields['transaction']['transaction_id'], $fields['transaction']['description']);
			
			$securityCode = $fields['payment_source']['card']['security_code'];
			
			unset($fields['payment_source']['card']['security_code']);
			unset($fields['payment_source']['card']['holder']);
		}
		
		$fields['merchant']['operation_type'] = self::CHECK_3D_ENROLL;
		$fields['ddd_secure_id'] = 'DDS_'.$transactionId;
		
		$fields['ddd_secure_redirect']['page_generation_mode']	= 'SIMPLE';
       
		$fields['ddd_secure_redirect']['response_url'] = Mage::getBaseUrl() . 'netpayonlinepayments/payment/callbackSecure/';
		
		//Process request
		$response = $this->_sendRequest($fields);	
		
		if($response->result == 'SUCCESS') {
			
			$summaryStatus = $response->ddd_secure->summary_status;
			switch($summaryStatus) {
				case 'CARD_ENROLLED':
					
					if($modePay == "card") {
						
						$cardStorage = 'temporary';
						
						$post = Mage::app()->getRequest()->getParam('payment');
						if(isset($post['store_card']) && $post['store_card'] == 1) {
							$cardStorage = 'permanent';
							Mage::getSingleton('checkout/session')->setIsToken(true);	
						}
						$token = $this->createToken($payment, $fieldsOri, $cardStorage);
					} else {
						$token = $fields['payment_source']['token'];
					}
					
					$encSecurityCode = Mage::helper('netpayonlinepayments')->getEncryptToken($securityCode);
					
					$secureForm = $response->ddd_secure_redirect->simple->html_body_content;
					Mage::getSingleton('checkout/session')->setDsecureForm($secureForm);
					
					$retun = '3d';					
											
					//Saving temp data in DB to use after authentication success
					$data['token']			= $token;
					$data['security_code']	= $securityCode;
					$data['ddd_secure_id']	= $response->ddd_secure_id;
					$data['order_id']		= $orderId;
					$this->saveTempData($data);
					
				break;
				case 'CARD_DOES_NOT_SUPPORT_3DS':
					$retun = "continue_purchase";
				break;
				case 'CARD_NOT_ENROLLED': //Check configuration value if purchase allow 
				
					if($this->getConfigData('securemode')) {
						$retun = "continue_purchase";
					} else {
						$errorMsg = $this->_getHelper()->__('Error Processing the request');
						Mage::throwException($errorMsg);
					}
					
				break;
				default:
					$errorMsg = $this->_getHelper()->__('Error Processing the request');
					Mage::throwException($errorMsg);
				break;
			}	
		} else if($response->result == 'ERROR') {
		
			$errorMsg = 'Error Processing the request. Error: '.$response->error->explanation;
			Mage::throwException($errorMsg);	
			
		} else {
			$errorMsg = $this->_getHelper()->__('Error Processing the request');
			Mage::throwException($errorMsg);	
		}
		
		return $retun;
	}

	/**
     * Function to Make call for Create token for gateway and save in database
     */	
	public function createToken(Varien_Object $payment, $fields, $tokenMode = 'permanent') {
		
		//unset extra fields from data array
		unset($fields['billing'], $fields['shipping'], $fields['customer'], $fields['order'], $fields['transaction'], $fields['payment_source']['card']['security_code']);
		
		//Adding new extra fileds in data array
		$fields['merchant']['operation_type']	= self::CREATE_TOKEN_OPERATION; 
		$fields['transaction']['source']		= self::SOURCE; 
		
		if($tokenMode == 'permanent') {
			$fields['token_mode']					= self::TOKEN_MODE; 
		} else {	
			$fields['token_mode']					= self::TOKEN_MODE_TEMP; 
		}	
		
		
		//Process request
		$response = $this->_sendRequest($fields, true);
		
		
		if($response->result == 'SUCCESS') {
			//Save encrypt token in database
			
			$encToken = Mage::helper('netpayonlinepayments')->getEncryptToken($response->token);
			
			if($tokenMode != 'temporary') {
				
				$obj = Mage::getModel('netpayonlinepayments/card');	
				$obj->setCustomerId($payment->getOrder()->getCustomerId());
				$obj->setToken($encToken);
				$obj->save();
			}
			
			return $response->token;
			
		} else {
		
			if($tokenMode == 'temporary') {
				$errorMsg = $this->_getHelper()->__('Error Processing the request');
				Mage::throwException($errorMsg);	
			}	
			
		} 
	}
	
	/**
     * Function to fetch card details from gateway by stored token
     */
	public function retieveCardDetails($token) {
		
		$helper = Mage::helper('netpayonlinepayments');
		$fields = array();
		
		//Merchant fields		
		$fields['merchant']['merchant_id'] 		= $helper->getMerchantId();
		$fields['merchant']['operation_type']	= self::GET_TOKEN_OPERATION;
		$fields['merchant']['operation_mode']	= $this->getConfigData('mode');
	
		//Transaction fields				
        $fields['transaction']['source'] = self::SOURCE; 

		//Payment fields				
        $fields['payment_source']['type'] = self::SOURCE_TOKEN; 
        $fields['payment_source']['token'] = $token; 
		
		//Process request
		$response = $this->_sendRequest($fields, true);
		
		$return = array();
		if($response->result == 'SUCCESS') {
		
			$return['token']		= $response->token;
            $return['card_type']	= $response->payment_source->card->card_type;
            $return['number']		= $response->payment_source->card->number;
            $return['expiry_month'] = $response->payment_source->card->expiry_month;
            $return['expiry_year']  = $response->payment_source->card->expiry_year;
            $return['fullname']		= $response->payment_source->card->holder->fullname;
            $return['encToken']		= $helper->getEncryptToken($response->token);
		}		
		
		return $return;
	}
	
	/**
     * Function to Delete token from gateway
     */
	public function deleteToken($token) {
		
		$helper = Mage::helper('netpayonlinepayments');
		$fields = array();
		
		//Merchant fields		
		$fields['merchant']['merchant_id'] 		= $helper->getMerchantId();
		$fields['merchant']['operation_type']	= "DELETE_TOKEN";
		$fields['merchant']['operation_mode']	= $this->getConfigData('mode');
	
		//Transaction fields				
        $fields['transaction']['source'] = self::SOURCE; 

		//Payment fields				
        $fields['payment_source']['type'] = self::SOURCE_TOKEN; 
        $fields['payment_source']['token'] = $token; 
		
		//Process request
		$this->_sendRequest($fields, true);
				
		return $return;
	}
	
	/**
     * Function to delete Stored Token from database
     */
	public function deleteTokenFromDb($token) {
		
		$encToken = Mage::helper('netpayonlinepayments')->getEncryptToken($token);
			
		$obj = Mage::getModel('netpayonlinepayments/card')->load($encToken, 'token');
		$obj->delete();
	}
	
	
	/**
     * Function to Process ACS results after user redirect from 3d Secure authentication
     */
	public function processAcsResults($params, $tokenData) {
		
		$helper = Mage::helper('netpayonlinepayments');
		$fields = array();
	
	//Merchant fields		
		$fields['merchant']['merchant_id'] 		= $helper->getMerchantId();
		$fields['merchant']['operation_type']	= "PROCESS_ACS_RESULT";
		$fields['merchant']['operation_mode']	= $this->getConfigData('mode');

	//Transaction fields				
        $fields['transaction']['source'] = self::SOURCE; 
	
	//3D Secure fields
		$fields['ddd_secure_id'] 		= $tokenData['ddd_secure_id']; 	
		$fields['ddd_secure']['pares']	= $params['PaRes']; 	
	
		//Process request
		$response = $this->_sendRequest($fields);
		
		$return = array();
		if($response->result == 'SUCCESS') {
			if($response->ddd_secure->summary_status == 'AUTHENTICATION_SUCCESSFUL') {
				
				
			//As Process ACS result Successful .. Make Purchase
				$orderId = $tokenData['order_id'];
				$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );

				$fields = array();
				$tokenData['isFrom3dSecure'] = true;
				$fields = $this->_prepareApiFields($order, $tokenData);
				
				
				//Process request
				$response = $this->_sendRequest($fields);

				if($response->result == 'SUCCESS') { //Payment Success
					$return['transaction_id']		= $response->transaction->transaction_id;
					$return['terminal']				= $response->transaction->terminal;
					$return['authorization_code']	= $response->transaction->authorization_code;
					$return['receipt']				= $response->transaction->receipt;
					$return['ddd_secure_id']		= $fields['ddd_secure_id'];
					$return['order_id']				= $response->order->order_id;
					
					
					$return['result'] = $response->result;
					
					//Capture Gateway Response in response table for future use.
					$dataResponse['method'] = $this->_code;		
					$dataResponse['response'] = json_encode($response);
					$dataResponse['order_id'] = $order->getId();
					$this->captureResponse($dataResponse);
					
				} else if($response->result == 'ERROR') {  //Payment Error
					
					$return['code'] = $response->error->code;
					$return['cause'] = $response->error->cause;
					$return['message'] = $response->error->explanation;
					
					$return['result'] = $response->result;
				
				} else if($response->result == 'FAILURE') {  //Payment FAILURE Error
							
					$errorCode = $response->response->gateway_code;
					
					$return['code'] 	= $errorCode;
					$return['message']	= Mage::helper('netpayonlinepayments')->getErrorMessageByCode($errorCode);
					
					$return['result'] = 'ERROR';
				}					
			} else {
				$return['code']		= $response->ddd_secure_id;
				$return['cause'] 	= $response->response->gateway_code;
				$return['message']	= 'Payment Failed';
				
				$return['result'] 	= 'Fail';
			}
		} else {
			$return['code'] = $response->error->code;
			$return['cause'] = $response->error->cause;
			$return['message'] = $response->error->explanation;
			
			$return['result'] = $response->result;
		} 
		return $return;	
	}	
	
	/**
     * Main Function to send request nd return response and debug
     */
	public function _sendRequest($fields, $forToken = false) {
		
		$helper = Mage::helper('netpayonlinepayments');
		
		$header = array();
		$header['username'] 	= $helper->getApiUsername();
		$header['password'] 	= $helper->getApiPassword();
		$header['accept']		= self::CONTENT_TYPE;
		$header['content_type'] = self::CONTENT_TYPE;
		
		$gatewayUrl = $helper->getApiIntegartionUrl();
		
		if($forToken) {
			$api_method = 'gateway/token';
		} else {
			$api_method = 'gateway/transaction';
		}

		$rest = new NetPay_Connection($gatewayUrl, $header);
		
		$this->configureSslPaths();
		
		$rest->set_ssl_path($this->_ssl_path);
		
		$response = $rest->put($api_method, $fields);
		
		if($this->getConfigData('debug')) { //Debug Response
			Mage::log($response, null, $this->getCode().'.log');
		}
		
		return $response;
	}
	
	/**
	 * sets variables in ssl path var from magento config
	 */
	public function configureSslPaths() {
		
		$helper = Mage::helper('netpayonlinepayments');
		
		$this->_ssl_path['certificate'] = $helper->getApiCert();
		$this->_ssl_path['key'] = $helper->getApiKey();
		$this->_ssl_path['certificate_pass'] = $helper->getApiCertPass();
	}

	/**
     * fucntion to Save token temp data stored in database with current session relation
     */
	public function saveTempData($data) {
		
		$helper = Mage::helper('netpayonlinepayments');
		
		$token			= $helper->getEncryptToken($data['token']);
		$securityCode	= $helper->getEncryptToken($data['security_code']);
		$dddSecureId	= $helper->getEncryptToken($data['ddd_secure_id']);
		$orderId		= $helper->getEncryptToken($data['order_id']);
		
		$this->deleteSessionTempData();
		
		$sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();	
		
		$obj = Mage::getModel('netpayonlinepayments/temp');	
		$obj->setToken($token);
		$obj->setSecurityCode($securityCode);
		$obj->setDddSecureId($dddSecureId);
		$obj->setSessionId($sessionId);
		$obj->setOrderId($orderId);
		$obj->save();
	}
	
	
	/**
     * fucntion to fetch current session token temp data stored in database
     */
	public function getSessionTempData() {
		
		$sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();	
		
		$helper = Mage::helper('netpayonlinepayments');
		
		$obj = Mage::getModel('netpayonlinepayments/temp')->load($sessionId, 'session_id');	
		$data = $obj->getData();
		$returnData = array();	
		
		if(!empty($data)) {
			$returnData['token']			= $helper->getDecryptToken($data['token']);
			$returnData['security_code']	= $helper->getDecryptToken($data['security_code']);
			$returnData['ddd_secure_id']	= $helper->getDecryptToken($data['ddd_secure_id']);
			$returnData['order_id']			= $helper->getDecryptToken($data['order_id']);
		}

		return $returnData;
	}
	
	/**
     * fucntion to get Token from database by Primary key
     */
	public function getTokenById($id) {
		
		$helper = Mage::helper('netpayonlinepayments');
		
		$obj	= Mage::getModel('netpayonlinepayments/card')->load($id);	
		$data	= $obj->getData();
		$token	= $helper->getDecryptToken($data['token']);
		
		return $token;
	}
	
	
	/**
     * fucntion to delete token temp data stored user session wise in database 
     */
	public function deleteSessionTempData() {
		
		$sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();	
		$obj = Mage::getModel('netpayonlinepayments/temp')->load($sessionId, 'session_id');	
		$obj->delete($sessionId);	
		
		return true;
	}
	
	/**
     * fucntion to Capture Gateway Response in response table for future use.
     */
	public function captureResponse($data) {
		
		$objModel = Mage::getModel('netpayonlinepayments/response');
		$objModel->setMethod($data['method']);
		$objModel->setResponse($data['response']);  	
		$objModel->setOrderId($data['order_id']);
		$objModel->save();
	}	
	
}