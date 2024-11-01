<?php
/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

/* Wordpress Checkout- Some modifications added to handle curl and text report

/**
 *  PayPal IPN Listener
 *
 *  A class to listen for and handle Instant Payment Notifications (IPN) from 
 *  the PayPal server.
 *
 *  https://github.com/Quixotix/PHP-PayPal-IPN
 *
 *  @package    PHP-PayPal-IPN
 *  @author     Micah Carrick
 *  @copyright  (c) 2012 - Micah Carrick
 *  @version    2.1.0
 */
class IpnListener {
    
    /**
     *  If true, the recommended cURL PHP library is used to send the post back 
     *  to PayPal. If flase then fsockopen() is used. Default true.
     *
     *  @var boolean
     */
    public $use_curl = true;     
    
    /**
     *  If true, explicitly sets cURL to use SSL version 3. Use this if cURL
     *  is compiled with GnuTLS SSL.
     *
     *  @var boolean
     */
    public $force_ssl_v3 = true;     
   
    /**
     *  If true, cURL will use the CURLOPT_FOLLOWLOCATION to follow any 
     *  "Location: ..." headers in the response.
     *
     *  @var boolean
     */
    public $follow_location = false;     
    
    /**
     *  If true, an SSL secure connection (port 443) is used for the post back 
     *  as recommended by PayPal. If false, a standard HTTP (port 80) connection
     *  is used. Default true.
     *
     *  @var boolean
     */
    public $use_ssl = true;      
    
    /**
     *  If true, the paypal sandbox URI www.sandbox.paypal.com is used for the
     *  post back. If false, the live URI www.paypal.com is used. Default false.
     *
     *  @var boolean
     */
    public $use_sandbox = false; 
    
    /**
     *  The amount of time, in seconds, to wait for the PayPal server to respond
     *  before timing out. Default 30 seconds.
     *
     *  @var int
     */
    public $timeout = 30;       
    
    private $post_data = array();
    private $post_uri = '';     
    private $response_status = '';
    private $response = '';

    const PAYPAL_HOST = 'www.paypal.com';
    const SANDBOX_HOST = 'www.sandbox.paypal.com';
    
    /**
     *  Post Back Using cURL
     *
     *  Sends the post back to PayPal using the cURL library. Called by
     *  the processIpn() method if the use_curl property is true. Throws an
     *  exception if the post fails. Populates the response, response_status,
     *  and post_uri properties on success.
     *
     *  @param  string  The post data as a URL encoded string
     */
    protected function curlPost($encoded_data) {
	
        if ($this->use_ssl) {
            $uri = 'https://'.$this->getPaypalHost().'/cgi-bin/webscr';
            $this->post_uri = $uri;
        } else {
            $uri = 'http://'.$this->getPaypalHost().'/cgi-bin/webscr';
            $this->post_uri = $uri;
        }
        
/*
        $ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, 
		            dirname(__FILE__)."/cert/api_cert_chain.crt");
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->follow_location);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        if ($this->force_ssl_v3) {
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        }
*/
 

		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
       
        $this->response = curl_exec($ch);
        $this->response_status = strval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        
        if ($this->response === false || $this->response_status == '0') {
            $errno = curl_errno($ch);
            $errstr = curl_error($ch);
            throw new Exception("cURL error: [$errno] $errstr");
        }
    }
    
    /**
     *  Post Back Using fsockopen()
     *
     *  Sends the post back to PayPal using the fsockopen() function. Called by
     *  the processIpn() method if the use_curl property is false. Throws an
     *  exception if the post fails. Populates the response, response_status,
     *  and post_uri properties on success.
     *
     *  @param  string  The post data as a URL encoded string
     */
    protected function fsockPost($encoded_data) {
    
        if ($this->use_ssl) {
            $uri = 'ssl://'.$this->getPaypalHost();
            $port = '443';
            $this->post_uri = $uri.'/cgi-bin/webscr';
        } else {
            $uri = $this->getPaypalHost(); // no "http://" in call to fsockopen()
            $port = '80';
            $this->post_uri = 'http://'.$uri.'/cgi-bin/webscr';
        }

        $fp = fsockopen($uri, $port, $errno, $errstr, $this->timeout);
        
        if (!$fp) { 
            // fsockopen error
            throw new Exception("fsockopen error: [$errno] $errstr");
        } 

        $header = "POST /cgi-bin/webscr HTTP/1.1\r\n";
        $header .= "Host: ".$this->getPaypalHost()."\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: ".strlen($encoded_data)."\r\n";
        $header .= "Connection: Close\r\n\r\n";
        
        fputs($fp, $header.$encoded_data."\r\n\r\n");
        
        while(!feof($fp)) { 
            if (empty($this->response)) {
                // extract HTTP status from first line
                $this->response .= $status = fgets($fp, 1024); 
                $this->response_status = trim(substr($status, 9, 4));
            } else {
                $this->response .= fgets($fp, 1024); 
            }
        } 
        
        fclose($fp);
    }
    
    private function getPaypalHost() {
        if ($this->use_sandbox) return self::SANDBOX_HOST;
        else return self::PAYPAL_HOST;
    }
    
    /**
     *  Get POST URI
     *
     *  Returns the URI that was used to send the post back to PayPal. This can
     *  be useful for troubleshooting connection problems. The default URI
     *  would be "ssl://www.sandbox.paypal.com:443/cgi-bin/webscr"
     *
     *  @return string
     */
    public function getPostUri() {
        return $this->post_uri;
    }
    
    /**
     *  Get Response
     *
     *  Returns the entire response from PayPal as a string including all the
     *  HTTP headers.
     *
     *  @return string
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     *  Get Response Status
     *
     *  Returns the HTTP response status code from PayPal. This should be "200"
     *  if the post back was successful. 
     *
     *  @return string
     */
    public function getResponseStatus() {
        return $this->response_status;
    }
    
    /**
     *  Get Text Report
     *
     *  Returns a report of the IPN transaction in plain text format. This is
     *  useful in emails to order processors and system administrators. Override
     *  this method in your own class to customize the report.
     *
     *  @return string
     */
    public function getTextReport() {
        
        $r = '';
        
        // date and POST url
        for ($i=0; $i<80; $i++) { $r .= '-'; }
        $r .= "\n[".date('m/d/Y g:i A').'] - '.$this->getPostUri();
        if ($this->use_curl) $r .= " (curl)\n";
        else $r .= " (fsockopen)\n";
        
        // HTTP Response
        for ($i=0; $i<80; $i++) { $r .= '-'; }
        $r .= "\n{$this->getResponse()}\n";
        
        // POST vars
        for ($i=0; $i<80; $i++) { $r .= '-'; }
        $r .= "\n";
        
        foreach ($this->post_data as $key => $value) {
            $r .= str_pad($key, 25)."$value\n";
        }
        $r .= "\n\n";
        
        return $r;
    }
	
	
    public function getOrderDetails() {
        
        $order = $this->post_data;		
		
		//Display Details
		$details = "<h3>".__("PAYMENT INFO","wpckt")."</h3>";
		
		$details .= "<strong>".__("payment_status:","wpckt")." </strong>".$order['payment_status']."<br />";
		$details .= "<strong>".__("payment_gross:","wpckt")." </strong>".$order['payment_gross']."<br />";
		$details .= "<strong>".__("payment_fee:","wpckt")." </strong>".$order['payment_fee']."<br />";
		$details .= "<strong>".__("payment_date:","wpckt")." </strong>".$order['payment_date']."<br />";
		$details .= "<strong>".__("payment_type:","wpckt")." </strong>".$order['payment_type']."<br /><br />";
		
		$details .= "<strong>".__("receiver_email:","wpckt")." </strong>".$order['receiver_email']."<br />";
		$details .= "<strong>".__("receiver_id:","wpckt")." </strong>".$order['receiver_id']."<br />";	
		$details .= "<strong>".__("verify_sign:","wpckt")." </strong>".$order['verify_sign']."<br />";	
		$details .= "<strong>".__("business:","wpckt")." </strong>".$order['business']."<br />";
		$details .= "<strong>".__("test_ipn:","wpckt")." </strong>".$order['test_ipn']."<br />";
		$details .= "<strong>".__("transaction_subject:","wpckt")." </strong>".$order['transaction_subject']."<br />";
		$details .= "<strong>".__("txn_type:","wpckt")." </strong>".$order['txn_type']."<br />";
		$details .= "<strong>".__("txn_id:","wpckt")." </strong>".$order['txn_id']."<br />";	
		$details .= "<strong>".__("protection_eligibility:","wpckt")." </strong>".$order['protection_eligibility']."<br />";
		$details .= "<strong>".__("address_status:","wpckt")." </strong>".$order['address_status']."<br />";
		$details .= "<strong>".__("charset:","wpckt")." </strong>".$order['charset']."<br />";
	
		
		$details .= "<h3>PAYER INFO</h3>";
		
		$details .= "<strong>".__("first_name:","wpckt")." </strong>".$order['first_name']."<br />";
		$details .= "<strong>".__("last_name:","wpckt")." </strong>".$order['last_name']."<br />";
		$details .= "<strong>".__("payer_email:","wpckt")." </strong>".$order['payer_email']."<br />";
		$details .= "<strong>".__("payer_id:","wpckt")." </strong>".$order['payer_id']."<br />";
		$details .= "<strong>".__("payer_status:","wpckt")." </strong>".$order['payer_status']."<br /><br />";
		
		$details .= "<strong>".__("address_name:","wpckt")." </strong>".$order['address_name']."<br />";
		$details .= "<strong>".__("address_street:","wpckt")." </strong>".$order['address_street']."<br />";
		$details .= "<strong>".__("address_city:","wpckt")." </strong>".$order['address_city']."<br />";
		$details .= "<strong>".__("address_state:","wpckt")." </strong>".$order['address_state']."<br />";
		$details .= "<strong>".__("address_zip:","wpckt")." </strong>".$order['address_zip']."<br />";
		$details .= "<strong>".__("address_country:","wpckt")." </strong>".$order['address_country']."<br />";
		$details .= "<strong>".__("address_country_code:","wpckt")." </strong>".$order['address_country_code']."<br />";
	
	
		$details .= "<h3>CART INFO</h3>";
		
		$details .= "<strong>".__("gross:","wpckt")." </strong>".$order['mc_gross']."<br />";
		$details .= "<strong>".__("fee:","wpckt")." </strong>".$order['mc_fee']."<br />";
		$details .= "<strong>".__("currency:","wpckt")." </strong>".$order['mc_currency']."<br />";	
		$details .= "<strong>".__("shipping:","wpckt")." </strong>".$order['mc_shipping']."<br />";	
		$details .= "<strong>".__("tax:","wpckt")." </strong>".$order['tax']."<br />";	
		$details .= "<strong>".__("cart_items:","wpckt")." </strong>".$order['num_cart_items']."<br />";
		
		for ( $i = 1; $i <= $order['num_cart_items'] ; $i ++) {
			
			$details .= "<br />";
			$details .= "<strong>".__("ITEM ","wpckt").$i."</strong><br />";
			$details .= "<strong>".__("item_number","wpckt").$i.":"." </strong>".$order['item_number'.$i]."<br />";
			$details .= "<strong>".__("item_name","wpckt").$i.":"." </strong>".$order['item_name'.$i]."<br />";
			$details .= "<strong>".__("gross_","wpckt").$i.":"." </strong>".$order['mc_gross_'.$i]."<br />";
			$details .= "<strong>".__("shipping","wpckt").$i.":"." </strong>".$order['mc_shipping'.$i]."<br />";
			$details .= "<strong>".__("quantity","wpckt").$i.":"." </strong>".$order['quantity'.$i]."<br />";
			
		}
		
		return 	$details;	
		
    }	
    
    /**
     *  Process IPN
     *
     *  Handles the IPN post back to PayPal and parsing the response. Call this
     *  method from your IPN listener script. Returns true if the response came
     *  back as "VERIFIED", false if the response came back "INVALID", and 
     *  throws an exception if there is an error.
     *
     *  @param array
     *
     *  @return boolean
     */    
    public function processIpn($post_data=null) {

        $encoded_data = 'cmd=_notify-validate';
        
        if ($post_data === null) { 
            // use raw POST data 
            if (!empty($_POST)) {
                $this->post_data = $_POST;
                $encoded_data .= '&'.file_get_contents('php://input');
            } else {
                throw new Exception("No POST data found.");
            }
        } else { 
            // use provided data array
            $this->post_data = $post_data;
            
            foreach ($this->post_data as $key => $value) {
                $encoded_data .= "&$key=".urlencode($value);
            }
        }

        if ($this->use_curl) $this->curlPost($encoded_data); 
        else $this->fsockPost($encoded_data);
        

        if (strpos($this->response_status, '200') === false) {
            throw new Exception("Invalid response status: ".$this->response_status);
        }
        
        if (strpos($this->response, "VERIFIED") !== false) {
            return true;
        } elseif (strpos($this->response, "INVALID") !== false) {
            return false;
        } else {
            throw new Exception("Unexpected response from PayPal.");
        }
    }
    
    /**
     *  Require Post Method
     *
     *  Throws an exception and sets a HTTP 405 response header if the request
     *  method was not POST. 
     */    
    public function requirePostMethod() {
        // require POST requests
        if ($_SERVER['REQUEST_METHOD'] && $_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Allow: POST', true, 405);
            throw new Exception("Invalid HTTP request method.");
        }
    }
}

