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
 * Hosted Payment Form Redirect block
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author		NetPay Development Team
 * @copyright  	Copyright (c) 2014
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
 
class NetPay_Netpayonlinepayments_Block_Redirect extends Mage_Core_Block_Abstract
{
	/**
	  * @Purpose : Return redirect form html to be submitted to the hosted payment form or the transparent redirect page
	  * @author  : NetPay Development Team
	 */ 
	protected function _toHtml()
    {
    	$model = Mage::getModel('netpayonlinepayments/direct');
    	$netpayPaymentMode = $model->getConfigData('method');
		$html = self::_redirectHostedPaymentForm();

        return $html;
    }
    
    /**
	  * @Purpose : Function to build the redirect form for the Hosted Payment type
	  * @return  : return html form with data to be submitted on gateway
	  * @author  : NetPay Development Team
	 */ 
    private function _redirectHostedPaymentForm() {
    	
		$html = '';
		
		$netPayActionURL = Mage::helper('netpayonlinepayments')->getActionUrl();
		$netpayResponseURL = Mage::helper('netpayonlinepayments')->getResponseUrl();
		
		$encResponse = Mage::helper('netpayonlinepayments')->getBackendResponseMode();
		
		$session = Mage::getSingleton('checkout/session');
    	    	
    	// create a Magento form
        $form = new Varien_Data_Form();

		//Basic Form Elements	
		$form->addField("merchant_id", 'hidden', array('name'=>"merchant_id", 'value'=> $session->getMerchantid() ));
        $form->addField("username", 'hidden', array('name'=>"username", 'value'=> $session->getUsername() ));
        $form->addField("password", 'hidden', array('name'=>"password", 'value'=> $session->getPassword() ));
        $form->addField("operation_mode", 'hidden', array('name'=>"operation_mode", 'value'=> $session->getOperationMode() ));
        $form->addField("session_token", 'hidden', array('name'=>"session_token", 'value'=> $session->getSessionToken() ));
        $form->addField("description", 'hidden', array('name'=>"description", 'value'=> $session->getDescription() ));
        $form->addField("amount", 'hidden', array('name'=>"amount", 'value'=> $session->getAmount() ));
        $form->addField("currency", 'hidden', array('name'=>"currency", 'value'=> $session->getCurrency()  ));
        $form->addField("transaction_id", 'hidden', array('name'=>"transaction_id", 'value'=> $session->getTransactionId() ));
        $form->addField("response_url", 'hidden', array('name'=>"response_url", 'value'=> $netpayResponseURL ));
        $form->addField("backend_response", 'hidden', array('name'=>"backend_response", 'value'=> $encResponse ));
        
		
		//Billing Deatil Form Elements
		if($session->getBillingCompany() != '')
			$form->addField("bill_to_company", 'hidden', array('name'=>"bill_to_company", 'value'=> $session->getBillingCompany() ));
		
		if($session->getBillingAddress() != '')
			$form->addField("bill_to_address", 'hidden', array('name'=>"bill_to_address", 'value'=> $session->getBillingAddress() ));
		
		if($session->getBillingCity() != '')
			$form->addField("bill_to_town_city", 'hidden', array('name'=>"bill_to_town_city", 'value'=> $session->getBillingCity() ));
		
		if($session->getBillingCounty() != '')
			$form->addField("bill_to_county", 'hidden', array('name'=>"bill_to_county", 'value'=> $session->getBillingCounty() ));
		
		if($session->getBillingPostcode() != '')
			$form->addField("bill_to_postcode", 'hidden', array('name'=>"bill_to_postcode", 'value'=> $session->getBillingPostcode() ));
		
		if($session->getBillingCountry() != '')
			$form->addField("bill_to_country", 'hidden', array('name'=>"bill_to_country", 'value'=> $session->getBillingCountry() ));

		
		//Shipping Detail form elements
		if($session->getShipTitle() != '')
			$form->addField("ship_to_title", 'hidden', array('name'=>"ship_to_title", 'value'=> $session->getShipTitle() ));	 
		
		if($session->getShipFirstname() != '')
			$form->addField("ship_to_firstname", 'hidden', array('name'=>"ship_to_firstname", 'value'=> $session->getShipFirstname() ));
		
		if($session->getShipMiddlename() != '')
			$form->addField("ship_to_middlename", 'hidden', array('name'=>"ship_to_middlename", 'value'=> $session->getShipMiddlename() ));
		
		if($session->getShipLastname() != '')
			$form->addField("ship_to_lastname", 'hidden', array('name'=>"ship_to_lastname", 'value'=> $session->getShipLastname() ));
		
		if($session->getShipFullname() != '')
			$form->addField("ship_to_fullname", 'hidden', array('name'=>"ship_to_fullname", 'value'=> $session->getShipFullname() ));
		
		if($session->getShipCompany() != '')
			$form->addField("ship_to_company", 'hidden', array('name'=>"ship_to_company", 'value'=> $session->getShipCompany() ));
		
		if($session->getShipAddress() != '')
			$form->addField("ship_to_address", 'hidden', array('name'=>"ship_to_address", 'value'=> $session->getShipAddress() ));
		
		if($session->getShipCity() != '')
			$form->addField("ship_to_town_city", 'hidden', array('name'=>"ship_to_town_city", 'value'=> $session->getShipCity() ));
		
		if($session->getShipCounty() != '')
			$form->addField("ship_to_county", 'hidden', array('name'=>"ship_to_county", 'value'=> $session->getShipCounty() ));
		
		if($session->getShipCountry() != '')
			$form->addField("ship_to_country", 'hidden', array('name'=>"ship_to_country", 'value'=> $session->getShipCountry() ));
		
		if($session->getShipMethod() != '')
			$form->addField("ship_to_method", 'hidden', array('name'=>"ship_to_method", 'value'=> $session->getShipMethod() ));
		
		if($session->getShipPhone() != '')
			$form->addField("ship_to_phone", 'hidden', array('name'=>"ship_to_phone", 'value'=> $session->getShipPhone() ));

 		//Order Items Form elements
		$form->addField("order_items", 'hidden', array('name'=>"order_items", 'value'=> $session->getOrderedItems() ));	 

		//Customer form elements
		if($session->getCustomerEmail() != '')
			$form->addField("customer_email", 'hidden', array('name'=>"customer_email", 'value'=> $session->getCustomerEmail() ));	 
		
		if($session->getCustomerPhone() != '')
			$form->addField("customer_phone", 'hidden', array('name'=>"customer_phone", 'value'=> $session->getCustomerPhone() ));	

		$quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
		
		$fieldName = Mage::helper('netpayonlinepayments')->getEncryptStr('orderid|quoteid');
		$form->addField("orderid", 'hidden', array('name'=>"orderid", 'value'=> $session->getCustomField()));
		$form->addField("quoteid", 'hidden', array('name'=>"quoteid", 'value'=> $quoteId));
		$form->addField("custom_fields", 'hidden', array('name'=>"custom_fields", 'value'=> $fieldName));				
		

        // reset the session items
        Mage::getSingleton('checkout/session')->setMerchantid(null)
			  		   						->setUsername(null)
			  		   						->setPassword(null)
			  		   						->setOperationMode(null)
			  		   						->setSessionToken(null)
			  		   						->setDescription(null)
			  		   						->setAmount(null)
			  		   						->setCurrency(null) 
			  		   						->setTransactionId(null) 
			  		   						->setResponseUrl(null)
											->setBillingCompany(null)
											->setBillingAddress(null)
											->setBillingCity(null)
											->setBillingCounty(null)
											->setBillingPostcode(null)
											->setBillingCountry(null)
											->setShipTitle(null)
											->setShipFirstname(null)
											->setShipMiddlename(null)
											->setShipLastname(null)
											->setShipFullname(null)
											->setShipCompany(null)
											->setShipAddress(null)
											->setShipCity(null)
											->setShipCounty(null)
											->setShipCountry(null)
											->setShipMethod(null)
											->setShipPhone(null) 											
											->setOrderedItems(null) 											
											->setCustomerEmail(null) 											
											->setCustomerPhone(null)
											->setCustomField(null); 											
											
        
		$html = '<html><body>';
        $html.= $this->__("You will be redirected to a secure payment page in a few seconds. Make sure you don't press the 'Back' button in your browser till your order is complete.");
        $html.= "<form action='".$netPayActionURL."' id='NetPayHostedForm' name='NetPayHostedForm' method='POST'>";
        $html.= $form->toHtml();
        $html.= '</form>';
        $html.= '<script type="text/javascript">document.getElementById("NetPayHostedForm").submit();</script>';
        $html.= '</body></html>';
		
		return $html;
    }
	
	
	
    
}