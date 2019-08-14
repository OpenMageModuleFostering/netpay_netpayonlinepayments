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
 * Direct Payment Method Payment Model
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author      NetPay Development Team
 */
class NetPay_Netpayonlinepayments_Model_Direct extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'netpayonlinepayments';
 	protected $_formBlockType = 'netpayonlinepayments/form'; 

	/**
     * Return url to redirect after order place
     *
     * @return  url
     */
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl( 'netpayonlinepayments/payment/redirect', array( '_secure' => true ) );
	}

	/**
     * Set data in session to be used to build form for Hosted Payment Method
     *
     * @return  url
     */
	public function _intiateHostedPaymentTransaction() {
		
		//Retieve Order
		$orderId = Mage::getSingleton( 'checkout/session' )->getLastRealOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId( $orderId );
		
		
		
		// Set NetPay Parameters
		$merchantId 		= Mage::helper('netpayonlinepayments')->getMerchantId();
		$encUsername 		= Mage::helper('netpayonlinepayments')->getEncryptStr(Mage::helper('netpayonlinepayments')->getApiUsername());
		$encPassword 		= Mage::helper('netpayonlinepayments')->getEncryptStr($this->getConfigData('password'));
		$encOperationType	= Mage::helper('netpayonlinepayments')->getEncryptStr($this->getConfigData('mode'));
		$sessionToken 		= Mage::helper('netpayonlinepayments')->create_unique_session_token($merchantId, $orderId);
		$encSessionToken	= Mage::helper('netpayonlinepayments')->getEncryptStr($sessionToken);

		$transactionId 		= Mage::helper('netpayonlinepayments')->create_unique_transaction_id($orderId);
		$encTransactionId	= Mage::helper('netpayonlinepayments')->getEncryptStr($transactionId);
	
		// Set Order Details 
		$encDescription		= Mage::helper('netpayonlinepayments')->getEncryptStr('Order Description');
		$encCurrencyCode	= Mage::helper('netpayonlinepayments')->getEncryptStr($order->getOrderCurrency()->getCurrencyCode());
		$encAmount              = Mage::helper('netpayonlinepayments')->getEncryptStr(number_format( $order->base_grand_total, 2, '.', '' ));
                
		
		
		// Retrieve order details
		$billingAddress = $order->getBillingAddress();
		$billingData = $billingAddress->getData();
		$shippingAddress = $order->getShippingAddress();
		$shippingData = array();
		if ( $shippingAddress )
			$shippingData = $shippingAddress->getData();
			
		$encBillingCompany 	= '';
		$encBillingAddress 	= ''; 
		$encBillingCity 	= '';
		$encBillingCounty 	= ''; 
		$encBillingPostcode	= ''; 
		$encBillingCountry 	= ''; 
		
		//Billing Address
		if($billingData['company'] != '')
			$encBillingCompany 	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['company']);
		
		if($billingData['street'] != '')	
			$encBillingAddress 	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['street']);
		
		if($billingData['city'] != '')
			$encBillingCity 	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['city']);
		
		if($billingData['region'] != '')	
			$encBillingCounty 	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['region']);
		
		if($billingData['postcode'] != '')
			$encBillingPostcode	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['postcode']);
		
		if($billingData['country_id'] != '')
			$encBillingCountry 	= Mage::helper('netpayonlinepayments')->getEncryptStr(Mage::getModel( 'directory/country' )->load( $billingData['country_id'] )->getData('iso3_code'));
		

			$encShipTitle 		= '';
			$encShipFirstname 	= '';
			$encShipMiddlename 	= '';
			$encShipLastname 	= '';
			$encShipFullname 	= '';
			$encShipCompany 	= '';
			$encShipAddress 	= '';
			$encShipCity 		= '';
			$encShipCounty 		= '';
			$encShipCountry 	= '';
			$encShipMethod 		= '';
			$encShipPhone 		= '';

		
		
		//Shipping Address
		if(!empty($shippingData)) {		
		
			$fullName = $shippingData['firstname'];
			if($shippingData['middlename'] != '')
				$fullName .= ' '.$shippingData['middlename'];
			
			$fullName .= ' '.$shippingData['lastname'];			
		
			if($shippingData['prefix'] != '')
				$encShipTitle 		= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['prefix']);
			
			if($shippingData['firstname'] != '')	
				$encShipFirstname 	= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['firstname']);
			
			if($shippingData['middlename'] != '')	
				$encShipMiddlename 	= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['middlename']);
				
			if($shippingData['lastname'] != '')
				$encShipLastname 	= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['lastname']);
			
			if($fullName != '')
				$encShipFullname 	= Mage::helper('netpayonlinepayments')->getEncryptStr($fullName);
			
			if($shippingData['company'] != '')
				$encShipCompany 	= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['company']);
			
			if($shippingData['street'] != '')
				$encShipAddress 	= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['street']);
			
			if($shippingData['city'] != '')
				$encShipCity 		= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['city']);
			
			if($shippingData['region'] != '')
				$encShipCounty 		= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['region']);
			
			if($shippingData['country_id'] != '')
				$encShipCountry 	= Mage::helper('netpayonlinepayments')->getEncryptStr(Mage::getModel( 'directory/country' )->load( $shippingData['country_id'] )->getData('iso3_code'));
			
			if($order->getShippingDescription() != '')
				$encShipMethod 		= Mage::helper('netpayonlinepayments')->getEncryptStr($order->getShippingDescription());
			
			if($shippingData['telephone'] != '')
				$encShipPhone 		= Mage::helper('netpayonlinepayments')->getEncryptStr($shippingData['telephone']);
			
		} else {
			$fullName = $billingData['firstname'];
			if($billingData['middlename'] != '')
				$fullName = ' '.$billingData['middlename'];
			
			$fullName = ' '.$billingData['lastname'];			
		
			if($billingData['prefix'] != '')
				$encShipTitle 		= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['prefix']);
			
			if($billingData['firstname'] != '')
				$encShipFirstname 	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['firstname']);
			
			if($billingData['middlename'] != '')
				$encShipMiddlename 	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['middlename']);
			
			if($billingData['lastname'] != '')
				$encShipLastname 	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['lastname']);
			
			if($fullName != '')
				$encShipFullname 	= Mage::helper('netpayonlinepayments')->getEncryptStr($fullName);
			
			$encShipCompany 	= '';
			$encShipAddress 	= '';
			$encShipCity 		= '';
			$encShipCounty 		= '';
			$encShipCountry 	= '';
			$encShipMethod 		= '';
			
			if($billingData['telephone'] != '')
				$encShipPhone 		= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['telephone']);	
		}
		
		
		//Order Items
		$items = $order->getAllItems();
		$orderedItems = '';
		foreach($items as $item) {
			$qty = (int) $item->getQtyOrdered();
			$sku = $item->getData('sku');
			$name = $item->getData('name');
			$description = $item->getData('description');
			
			if($description == '')
				$description = $name;
			
			$price = number_format( $item->getData('price'), 2, '.', '' );
			$taxable = 1;
			$orderedItems.='[{item_id|'.$sku.'}{item_taxable|'.$taxable.'}{item_name|'.$name.'}{item_description|'.$description.'}{item_quantity|'.$qty.'}{item_price|'.$price.'}]';
		}
		
		if($orderedItems != '') {
			$orderedItems 		= Mage::helper('netpayonlinepayments')->getEncryptStr($orderedItems);	
		}
		
		//Customer Details
		$encCustomerEmail	= Mage::helper('netpayonlinepayments')->getEncryptStr($order->getCustomerEmail());	
		
		if($billingData['telephone'] != '')
		$encCustomerPhone	= Mage::helper('netpayonlinepayments')->getEncryptStr($billingData['telephone']);	
		
		
		$encCustomField	= $orderId;	
		
		
		Mage::getSingleton('checkout/session')->setMerchantid($merchantId)
											->setUsername($encUsername)
											->setPassword($encPassword)
											->setOperationMode($encOperationType)
											->setDescription($encDescription)
											->setSessionToken($encSessionToken)
											->setTransactionId($encTransactionId) 
											->setAmount($encAmount)
											->setCurrency($encCurrencyCode)
											->setBillingCompany($encBillingCompany)
											->setBillingAddress($encBillingAddress)
											->setBillingCity($encBillingCity)
											->setBillingCounty($encBillingCounty)
											->setBillingPostcode($encBillingPostcode)
											->setBillingCountry($encBillingCountry)
											->setShipTitle($encShipTitle)	 
											->setShipFirstname($encShipFirstname)
											->setShipMiddlename($encShipMiddlename)
											->setShipLastname($encShipLastname)
											->setShipFullname($encShipFullname)
											->setShipCompany($encShipCompany)
											->setShipAddress($encShipAddress)
											->setShipCity($encShipCity)
											->setShipCounty($encShipCounty)
											->setShipCountry($encShipCountry)
											->setShipMethod($encShipMethod)
											->setShipPhone($encShipPhone) 
											->setOrderedItems($orderedItems) 
											->setCustomerEmail($encCustomerEmail) 
											->setCustomerPhone($encCustomerPhone) 
											->setCustomField($encCustomField); 
											
	}
	
}