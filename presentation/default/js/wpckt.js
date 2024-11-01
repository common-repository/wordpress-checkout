/**
 * Wordpress Checkout 
 * Presentation: Default 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

(function($){
	       
		   var $wpckt_widget_qty = $(".wpckt_widget_qty");
		   var $wpckt_verifying = $(".wpckt_verifying");
		   
	       $(document).ready(function(){			   
			   
			   wpcktUpdateWidget();
			   
			   setTimeout(wpcktUpdateOrder,5000);
		
			});
			
			function wpcktUpdateWidget(){
				    
					if ($wpckt_widget_qty.size() > 0){
						wpcktSettings.action = "wpckt_get_cart_content";
						wpcktAjax( wpcktSettings, function(response){
							  if (typeof response != "undefined" && typeof response.itemscount != "undefined"){
								  $wpckt_widget_qty.text(response.itemscount);                      
							  }
						});	
					}
				
			}

			function wpcktUpdateOrder(){
				    
					if ($wpckt_verifying.size() > 0){
						wpcktSettings.action = "wpckt_verify_order_status";
						wpcktAjax( wpcktSettings, function(response){
							  if (typeof response != "undefined"){									
								  if (response.status == "Completed") {
									  wpcktUpdateWidget();
									  if (response.thankyou_page != ""){
										  window.location = response.thankyou_page;
									  } else {
										  $(".wpckt_verifying").removeClass("wpckt_warning").html(response.thankyou_msg);
									  }
								  } else {
									  $(".wpckt_verifying").removeClass("wpckt_warning").html(response.not_completed_msg);
								  }
								  
							  } else {
								  $(".wpckt_verifying").removeClass("wpckt_warning").html(response.invalid_msg);
							  }
						});	
					}
				
			}
		
			function wpcktAjax(settings, callBack){
				$.post(
					settings.ajaxUrl,				
	                settings,
					callBack//,
					//"json"
				);		 
	        }
			
					
}(jQuery));
