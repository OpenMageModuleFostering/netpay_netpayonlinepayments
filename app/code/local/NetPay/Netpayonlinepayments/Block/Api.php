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
 * API Payment Method Block
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author		NetPay Development Team
 * @copyright  	Copyright (c) 2014
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
 
class NetPay_Netpayonlinepayments_Block_Api extends Mage_Payment_Block_Form_Cc
{

	/**
	 * @Purpose : construct funnction to set template for method
	 * @author  : NetPay Development Team
	 */
 	protected function _construct()
    {
        parent::_construct();
		
		//$head = $this->getLayout()->getBlock('head');
		//$head->addJs('NetPay/netpay-api.js');
		
        $this->setTemplate('netpayonlinepayments/api_form.phtml');
    }

	/**
	 * @Purpose : Fucntion to return allowed credit card array for payment block in checkout
	 * @author  : NetPay Development Team
	 */
    public function getCcAvailableTypes()
    {
		$method = $this->getMethod();
		
		$types = Mage::getModel('netpayonlinepayments/source_cctype')->getAllOptions();
		
        $availableTypes = $method->getConfigData('cctypes');
		if ($availableTypes) {
			$availableTypes = explode(',', $availableTypes);
			foreach ($types as $code=>$name) {
				if (!in_array($code, $availableTypes)) {
					unset($types[$code]);
				}
			}
		}
        return $types;
    }
	

	/**
	* @Purpose : Render block HTML If method is not NetPay API payment method  - nothing to return
	* @author  : NetPay Development Team
	*/
    protected function _toHtml()
    {
        if ($this->getMethod()->getCode() != Mage::getSingleton('netpayonlinepayments/netpayapi')->getCode()) {
            return null;
        }

        return parent::_toHtml();
    }

	/**
	* @Purpose : fucntion to set method info
	* @author  : NetPay Development Team
	*/ 
    public function setMethodInfo()
    {
        $payment = Mage::getSingleton('checkout/type_onepage')
            ->getQuote()
            ->getPayment();
        $this->setMethod($payment->getMethodInstance());

        return $this;
    }

	/**
	 * @Purpose : fucntion to check type of request
	 * @return bool
	 * @author  : NetPay Development Team
	 */  
    public function isAjaxRequest()
    {
        return $this->getAction()
            ->getRequest()
            ->getParam('isAjax');
    }	
	
	
    /**
	  * @Purpose : Function to fetch user stored token details from payment gateway 
	  * @Action When Called : called from Api template if user is logged in	  
	  * @author  : NetPay Development Team
	 */
    public function getUserStoredTokenData() {
		
		$customerId = Mage::helper('customer')->getCustomer()->getId();
		
		$tokens = Mage::getModel('netpayonlinepayments/card')->getCollection()
				->addFieldToFilter('customer_id', $customerId);
			
		$response = array(); 	
		$i = 0;
		foreach($tokens as $token) {
			$tokenStr = Mage::helper('netpayonlinepayments')->getDecryptToken($token->getData('token'));
			$responseData = Mage::getModel('netpayonlinepayments/netpayapi')->retieveCardDetails($tokenStr);	
			if(!empty($responseData)) {
				
				$response[$i] = $responseData;	
				$response[$i]['id'] = $token->getData('id');	
			
				$i++;
			}	
		}
		return $response;
    }		
	
	
	
	
	
}