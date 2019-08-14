
  function processOnRadio(val)
   {     	 
		 if(val!="new")
		   {
		     blankVals();
		   }
		   
	  var radioGrp = document['forms']['co-payment-form']['payment[card_type]'];
		for(i=0; i < radioGrp.length; i++){
			 
			 var radioValue = radioGrp[i].value;
			 /*if (radioGrp[i].checked == true) {				
				chedval = radioValue;				
			}*/			
				 		
		$$('.validation-advice').each(
		   function (e) {
			  e.setStyle({display:'none'}); 
		   } 
		);
		
		 if(radioValue !="new")
		  {	
		   var fnmm = "netpayapi_cc_cid_"+radioValue;			
		   $(fnmm).removeClassName('required-entry');
		   $(fnmm).value = "";		  
		  }
		  remvoeValidations();
		  
		  if(val==radioValue && val!="new")
		   {
		     $(fnmm).addClassName('required-entry');
		   }	
		  
		   
		   if(val==radioValue && val=="new")
		   { 
		     changeValidations();
		   } 		   
		}			
			
   }
   
   function processOnInput()
   {   
	   var radioGrp = document['forms']['co-payment-form']['payment[card_type]'];
		for(i=0; i < radioGrp.length; i++){
		
		  var radioValue = radioGrp[i].value;				 
		  if(radioValue=="new")
		   {
		     radioGrp[i].checked = true;
			 processOnRadio('new');
		   }	
		}
   }
   
   function processOnTocken(val)
   {
   
	 var radioGrp = document['forms']['co-payment-form']['payment[card_type]'];
		for(i=0; i < radioGrp.length; i++){
		
		  var radioValue = radioGrp[i].value;				 
		  if(val==radioValue)
		   {
		     radioGrp[i].checked = true;
			 processOnRadio(val);
		   }	
		}
		
   }
   
   function changeValidations()
   {
   	 $('netpayapi_cc_owner').addClassName('required-entry');
	 $('netpayapi_cc_type').addClassName('required-entry');
	 $('netpayapi_cc_number').addClassName('required-entry');
	 $('netpayapi_expiration').addClassName('required-entry');
	 $('netpayapi_expiration_yr').addClassName('required-entry');
	 $('netpayapi_cc_cid').addClassName('required-entry');	 
   }
   
   function remvoeValidations()
   {  
     $('netpayapi_cc_owner').removeClassName('required-entry');   
	 $('netpayapi_cc_type').removeClassName('required-entry');
	 $('netpayapi_cc_number').removeClassName('required-entry');
	 $('netpayapi_expiration').removeClassName('required-entry');
	 $('netpayapi_expiration_yr').removeClassName('required-entry');
	 $('netpayapi_cc_cid').removeClassName('required-entry');
   }
   
   function blankVals()
   { 
     $('netpayapi_cc_owner').value = "";
	 $('netpayapi_cc_type').value = "";
	 $('netpayapi_cc_number').value = "";
	 $('netpayapi_expiration').value = "";
	 $('netpayapi_expiration_yr').value = "";
	 $('netpayapi_cc_cid').value = "";   
   }