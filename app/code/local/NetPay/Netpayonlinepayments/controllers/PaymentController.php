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
 * Payment Contrller
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author		NetPay Development Team
 * @copyright  	Copyright (c) 2014
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
 
class NetPay_Netpayonlinepayments_PaymentController extends Mage_Core_Controller_Front_Action{

	
	/**
	  * @Purpose : Fucntion to submit form to payment gateway for proceeding transaction in Hosted Payment Method 
	  * @Action When Called : The redirect action is triggered when someone places an order with Hosted Payment Method
	  * @author  : NetPay Development Team
	 */
	public function redirectAction() {
		Mage::getModel('netpayonlinepayments/direct')->_intiateHostedPaymentTransaction();
		$this->getResponse()->setBody($this->getLayout()->createBlock('netpayonlinepayments/redirect')->toHtml());
	}
	
	
	/**
	  * @Purpose : Fucntion to recieve response and update order and redircet user on payment success and failure page as per result
	  * @Action When Called : The response action is triggered when NetPay sends a response for Hosted Payment method
	  * @author  : NetPay Development Team
	 */
	public function responseAction() {
	
	
		$data = $this->getRequest()->getPost('response');
		if(isset($data) && !empty($data)){
			$responseStr = Mage::helper('netpayonlinepayments')->getDecryptStr($data);
			$responseData = Mage::helper('netpayonlinepayments')->parse_response_url($responseStr);
			$this->__processOrder($responseData);
			return true;
		}
		
		$params = $this->getRequest()->getParams();
		
		if(isset($params['response']) && $params['response'] != '') {
		
			$responseStr = Mage::helper('netpayonlinepayments')->getDecryptStr($params['response']);
			$responseData = Mage::helper('netpayonlinepayments')->parse_response_url($responseStr);
			
			$orderId = $responseData['orderid']; 
			
			if ( isset($responseData['result']) && $responseData['result'] == 'SUCCESS' )	{
			
				$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
				
				//Checking Order Status if backend response not recieved  
				$allowProcess = false;
				if(Mage::getModel('netpayonlinepayments/direct')->getConfigData('backendresponse')) {
					if($order->getState() == Mage_Sales_Model_Order::STATE_NEW) {
						$allowProcess = true;
					}
				}
				
				if(!Mage::getModel('netpayonlinepayments/direct')->getConfigData('backendresponse') || $allowProcess) {
					//If backend response not enabled else order is already processed 	
					$this->__processOrder($responseData);
				}	
				
				$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
				$order->sendNewOrderEmail();
				$order->setEmailSent( true );
				$order->save();
				
				Mage::getSingleton( 'checkout/session' )->unsQuoteId();
				Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/success', array( '_secure' => true ) );
			
			} else {
			// If Error Occured
			
				$netpayResponse ='Result: '.$responseData['result'].', Error Code: '.$responseData['code'].', Cause: '.$responseData['cause'].', Explanation: '.$responseData['explanation'];
				Mage::getSingleton('core/session')->addError($netpayResponse);
				
				$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
				
				$setCancel = false;
				if($responseData['cause'] == 'REQUEST_FAILED') {
					$setCancel = true;
				}
				
				//Checking Order Status if backend response not recieved  
				$allowProcess = false;
				if(Mage::getModel('netpayonlinepayments/direct')->getConfigData('backendresponse')) {
					if($order->getState() == Mage_Sales_Model_Order::STATE_NEW) {
						$allowProcess = true;
					}
				}
				
				
				if(!Mage::getModel('netpayonlinepayments/direct')->getConfigData('backendresponse') || $allowProcess) {
					//If backend response not enabled else error is already captured
						
						$orderState = Mage_Sales_Model_Order::STATE_NEW;
						
						if($setCancel) {
							$orderState = Mage_Sales_Model_Order::STATE_CANCELED;
							Mage::getSingleton('checkout/session')->setQuoteId($responseData['quoteid']);
						} 
						
						$order->setState($orderState, true, $netpayResponse, false);
						$order->save();
						
						//Capture Gateway Response in response table for future use.
						$dataResponse['method']		= 'hostedform';		
						$dataResponse['response']	= json_encode($responseData);
						$dataResponse['order_id']	= $order->getId();
						Mage::getModel( 'netpayonlinepayments/netpayapi' )->captureResponse($dataResponse);
						

						//Debug Response
						if(Mage::getModel('netpayonlinepayments/direct')->getConfigData('debug')) {
						  Mage::log($responseData, null, Mage::getModel('netpayonlinepayments/direct')->getCode().'.log');
						}
				}	
				
				if($setCancel) {
					$this->_redirect('checkout/cart');
				} else {
					$this->_redirect('checkout/onepage/failure');
				}	
			}
		}
		else
			Mage_Core_Controller_Varien_Action::_redirect( '' );
	}
	
	
	private function __processOrder($responseData) {
		
		
		$orderId = $responseData['orderid']; 
		$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
		
		if ( isset($responseData['result']) && $responseData['result'] == 'SUCCESS' ) {
		
			if($order->canInvoice()) {
				$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
				
				$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
				$invoice->register();
				$transactionSave = Mage::getModel('core/resource_transaction')
				->addObject($invoice)
				->addObject($invoice->getOrder());
				$transactionSave->save();
			}
			
			$netpayResponse = 'Result: '.$responseData['result'].' NetPay Transaction ID: '.$responseData['transaction_id'].' NetPay Order ID: '.$responseData['order_id'];

			$order->setState( Mage_Sales_Model_Order::STATE_PROCESSING, true, $netpayResponse );
			
			$payment = $order->getPayment();
			
			
			$payment->setLastTransId($responseData['transaction_id']);
			$additionalInfo['TransactionType'] = $responseData['operation_type'];
			$additionalInfo['CrossReference'] = $responseData['order_id'];
			$payment->setAdditionalInformation($additionalInfo);
			
			$order->save();
			
			//Capture Gateway Response in response table for future use.
			$dataResponse['method']		= 'hostedform';		
			$dataResponse['response']	= json_encode($responseData);
			$dataResponse['order_id']	= $order->getId();
			Mage::getModel( 'netpayonlinepayments/netpayapi' )->captureResponse($dataResponse);
			
			//Debug Response
			if(Mage::getModel('netpayonlinepayments/direct')->getConfigData('debug')) {
			  Mage::log($responseData, null, Mage::getModel('netpayonlinepayments/direct')->getCode().'.log');
			}
			
		} else {
		
			$netpayResponse ='Result: '.$responseData['result'].', Error Code: '.$responseData['code'].', Cause: '.$responseData['cause'].', Explanation: '.$responseData['explanation'];

			$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
			$order->setState(Mage_Sales_Model_Order::STATE_NEW, true, $netpayResponse, false);
			$order->save();
			
			//Capture Gateway Response in response table for future use.
			$dataResponse['method']		= 'hostedform';		
			$dataResponse['response']	= json_encode($responseData);
			$dataResponse['order_id']	= $order->getId();
			Mage::getModel( 'netpayonlinepayments/netpayapi' )->captureResponse($dataResponse);
			
			//Debug Response
			if(Mage::getModel('netpayonlinepayments/direct')->getConfigData('debug')) {
			  Mage::log($responseData, null, Mage::getModel('netpayonlinepayments/direct')->getCode().'.log');
			}	
		}
	}
	
	
	/**
	  * @Purpose : Fucntion to build and submit form for 3D Secure Authenctication on payment gateway 
	  * @Action When Called : The threedsecure action is triggered when someone places an order with API Payment Method with 3d secure enable
	  * @author  : NetPay Development Team
	 */
	public function threedsecureAction() {
		$this->getResponse()->setBody($this->getLayout()->createBlock('netpayonlinepayments/secureredirect')->toHtml());
	}
	
	
	/**
	  * @Purpose : Fucntion to recieve 3D Secure Authentication response and check response and update order accordingly
	  * @Action When Called : The callbackSecure action is triggered when 3D Secure Authenctication return response
	  * @author  : NetPay Development Team
	 */
	public function callbackSecureAction() {
		$params = $this->getRequest()->getParams();
		$apiModel = Mage::getModel('netpayonlinepayments/netpayapi');	
		
		$tokenData = $apiModel->getSessionTempData();	
		if(!empty($tokenData)) {
			
			$paymentFail = false;
			$orderId = $tokenData['order_id'];	
			$responseData = $apiModel->processAcsResults($params, $tokenData);
			
			if ( isset($responseData['result']) && $responseData['result'] == 'SUCCESS' )	{
				
				$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
				
				if($order->canInvoice()) {
					$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
					
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->register();
					$transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
					$transactionSave->save();
					$invoice->sendEmail(true, '');
				}
				
				$netpayResponse = 'Result: '.$responseData['result'].' NetPay Transaction ID: '.$responseData['transaction_id'].' NetPay Order ID: '.$responseData['order_id'];
	
				$order->setState( Mage_Sales_Model_Order::STATE_PROCESSING, true, $netpayResponse );
				
				$payment = $order->getPayment();
				
				
				$payment->setLastTransId($responseData['transaction_id']);
				$order->sendNewOrderEmail();
				$order->setEmailSent( true );
				
				$order->save();
				
				Mage::getSingleton( 'checkout/session' )->unsQuoteId();
				
				Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/success', array( '_secure' => true ) );
			
			} else if ( isset($responseData['result']) && $responseData['result'] == 'ERROR' )	{
			
			   $netpayResponse ='Result: '.$responseData['result'].', Error Code: '.$responseData['code'].', Message: '.$responseData['message'];

				$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
				$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $netpayResponse, false);
				$order->save();
				$paymentFail = true;
				
				Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/failure', array( '_secure ' => true) );
				
			} else {
				$netpayResponse ='Result: '.$responseData['result'].', 3D Secure Id: '.$responseData['code'].', Message: '.$responseData['message'];

				$order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
				$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $netpayResponse, false);
				$order->save();	
				
				Mage::getSingleton('core/session')->addError($netpayResponse);
				
				$paymentFail = true;
				Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/failure', array( '_secure ' => true) );
			}		
		
			//Delete temp Data 	
			$apiModel->deleteSessionTempData();
			
			//Delete token as Payment Fail
			$isTokenStored = Mage::getSingleton('checkout/session')->getIsToken();
			if($paymentFail == true && $isTokenStored == true) {
			
				//Call to delete From Gateway
				$apiModel->deleteToken($tokenData['token']);
				
				//Call to delete From Database
				$apiModel->deleteTokenFromDb($tokenData['token']);
			}
			Mage::getSingleton('checkout/session')->setIsToken(null);
			
		} else {
			Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/failure', array( '_secure ' => true) );		
		}	
	}

	
	
}
