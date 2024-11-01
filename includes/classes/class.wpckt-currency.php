<?php
/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

/**
 * Wpckt Currency Formatter class.
 * Based on http://www.thefinancials.com/Default.aspx?SubSectionID=curformat
 *
 */

 /* 
 For Paypal Individual payments to your recipients cannot exceed 
 
 $12,500.00 AUD,
 $12,500.00 CAD, 
 13,000.00 CHF,
 240,000.00 CZK, 
 60,000.00 DKK,
 €8,000.00 EUR, 
 £5,550.00 GBP,
 $80,000.00 HKD,
 2,000,000 HUF, 
 ¥1,000,000 JPY, 
 70,000.00 NOK, 
 R$20,000.00 BRL (only for Brazilian members), 
 $15,000.00 NZD,  
 32,000.00 PLN,
 80,000.00 SEK, 
 $16,000.00 SGD,
 $10,000.00 USD 
 */
 

class WPCKT_currency
{
	
		/* Different types so far
		   # ###.##
		   #'###.##
		   #.###,##
		   #,###.##
		      #.###
		*/
		
		public $code;
		public $all;

	   /**
	   * Constructor.
	   */
        public function __construct($code)
        {
             $this->code = $code;
			 $this->all = $this->get_all();
        }
		
		public function get_all(){
			  /*
		         numeric_code: HTML Character Entities Decimal usig http://code.cside.com/3rdpage/us/unicode/converter.html
			  */
		      return array(		                    
							// $ # ###.##
							'AUD' => array(
							  'code' => 'AUD', 
							  'symbol' => 'AU$', 
							  'name' => 'Australian Dollar', 
							  'numeric_code' => 'AU&#36;',
							  'thousands_separator' => ' ',
							  'minor_unit' => 'Cent', 
							  'major_unit' => 'Dollar',
							), 
						

							// $ # ###.##						
							'CAD' => array(
							  'code' => 'CAD', 
							  'symbol' => 'CA$', 
							  'name' => 'Canadian Dollar', 
							  'numeric_code' => 'CA&#36;', 
							  'minor_unit' => 'Cent', 
							  'major_unit' => 'Dollar',
							),							
													

                            // Fr. #'###.##
							'CHF' => array(
							  'code' => 'CHF', 
							  'symbol' => 'Fr.', 
							  'name' => 'Swiss Franc', 
							  'rounding_step' => '0.05', 
							  'numeric_code' => 'Fr.', 
							  'thousands_separator' => "'",
							  'minor_unit' => 'Rappen', 
							  'major_unit' => 'Franc',
							), 							
													
													
							// #.###,## Kč						
							'CZK' => array(
							  'code' => 'CZK', 
							  'symbol' => 'Kč', 
							  'name' => 'Czech Republic Koruna', 
							  'numeric_code' => 'K&#269;', 
							  'thousands_separator' => '.', 
							  'decimal_separator' => ',', 
							  'symbol_placement' => 'after', 
							  'code_placement' => '', 
							  'minor_unit' => 'Haléř', 
							  'major_unit' => 'Koruna',
							),							
													

							// #.###,## kr.						
							'DKK' => array(
							  'code' => 'DKK', 
							  'symbol' => 'kr.', 
							  'name' => 'Danish Krone', 
							  'numeric_code' => 'kr.', 
							  'thousands_separator' => ' ', 
							  'decimal_separator' => ',', 
							  'symbol_placement' => 'after', 
							  'code_placement' => '', 
							  'minor_unit' => 'Øre', 
							  'major_unit' => 'Kroner',
							), 							
													

							// #,###.## €						
							'EUR' => array(
							  'code' => 'EUR', 
							  'symbol' => '€', 
							  'name' => 'Euro', 
							  'symbol_placement' => 'after', 
							  'code_placement' => '', 
							  'numeric_code' => '&#8364;', 
							  'minor_unit' => 'Cent', 
							  'major_unit' => 'Euro',
							), 							
													

							// £ #,###.## 						
							'GBP' => array(
							  'code' => 'GBP', 
							  'symbol' => '£', 
							  'name' => 'British Pound Sterling', 
							  'numeric_code' => '&#163;', 
							  'code_placement' => '', 
							  'minor_unit' => 'Penny', 
							  'major_unit' => 'Pound',
							),							
													
                            // $ #,###.## 
							'HKD' => array(
							  'code' => 'HKD', 
							  'symbol' => 'HK$', 
							  'name' => 'Hong Kong Dollar', 
							  'numeric_code' => 'HK&#36;', 
							  'minor_unit' => 'Cent', 
							  'major_unit' => 'Dollar',
							),							
													
													
							// #.### Ft						
							'HUF' => array(
							  'code' => 'HUF', 
							  'symbol' => 'Ft', 
							  'name' => 'Hungarian Forint', 
							  'numeric_code' => 'Ft', 
							  'decimals' => 0, 
							  'symbol_placement' => 'after', 
							  'code_placement' => '', 
							  'major_unit' => 'Forint',
							),							
													
													
							// ¥ #,###						
							'JPY' => array(
							  'code' => 'JPY', 
							  'symbol' => '¥', 
							  'name' => 'Japanese Yen', 
							  'decimals' => 0, 
							  'numeric_code' => '&#165;', 
							  'minor_unit' => 'Sen', 
							  'major_unit' => 'Yen',
							), 							
													

							// #.###,##						
							'NOK' => array(
							  'code' => 'NOK', 
							  'symbol' => 'Nkr', 
							  'name' => 'Norwegian Krone', 
							  'thousands_separator' => '.', 
							  'decimal_separator' => ',', 
							  'numeric_code' => 'Nkr', 
							  'minor_unit' => 'Øre', 
							  'major_unit' => 'Krone',
							), 							
													
													
							// $ #,###.## 						
							'NZD' => array(
							  'code' => 'NZD', 
							  'symbol' => 'NZ$', 
							  'name' => 'New Zealand Dollar', 
							  'numeric_code' => 'NZ&#36;', 
							  'minor_unit' => 'Cent', 
							  'major_unit' => 'Dollar',
							), 							
													
                            // zł # ###,##
							'PLN' => array(
							  'code' => 'PLN', 
							  'symbol' => 'zł', 
							  'name' => 'Polish Złoty', 
							  'numeric_code' => 'z&#322;',
							  'thousands_separator' => ' ', 
							  'decimal_separator' => ',', 							   
							  'minor_unit' => 'Grosz', 
							  'major_unit' => 'Złotych',
							), 							
													
													
							// #.###,##	kr						
							'SEK' => array(
							  'code' => 'SEK', 
							  'symbol' => 'kr', 
							  'name' => 'Swedish Krona', 
							  'numeric_code' => 'kr', 
							  'thousands_separator' => '.', 
							  'decimal_separator' => ',', 
							  'symbol_placement' => 'after', 
							  'code_placement' => '', 
							  'minor_unit' => 'Öre', 
							  'major_unit' => 'Kronor',
							),							
													

							// $ #,###.## 
							'SGD' => array(
							  'code' => 'SGD', 
							  'symbol' => 'S$', 
							  'name' => 'Singapore Dollar', 
							  'numeric_code' => 'S&#36;', 
							  'minor_unit' => 'Cent', 
							  'major_unit' => 'Dollar',
							),							
													
													
							// $ #,###.## 
							'USD' => array(
							  'code' => 'USD', 
							  'symbol' => '$', 
							  'name' => 'United States Dollar', 
							  'numeric_code' => '&#36;', 
							  'code_placement' => '', 
							  'minor_unit' => 'Cent', 
							  'major_unit' => 'Dollar',
							)							
							

							);				  
		}


        public function render_symbol()
        {
			 return $this->all[$this->code]['numeric_code'];
        }
		
        public function get_attr($attr,$code) 
		{
			if (!isset($code)){
				$code = $this->code;
			}

			if (array_key_exists ($code, $this->all)) {
				if(isset($this->all[$code][$attr])){				
			        return $this->all[$code][$attr];
				}
			}
		}
		
		
     	public function sanitize($amount)
		{
			$currency_array = $this->all[$this->code];
			$pref_formated_amount = $amount;
			
			if ( strrpos ( "." , $amount ) !== false && strlen ($amount) > 3 ) {
			   $pref_formated_amount = 	str_replace('.', '', $amount);
			}
			$pref_formated_amount = floatval(str_replace(',', '.' , $pref_formated_amount));
	
			
			return $pref_formated_amount;
		}
				

     	public function format($amount)
		{
			
			$currency_array = $this->all[$this->code];
			$decimal_separator = ".";
			if ( isset ( $currency_array["decimal_separator"] ) ) {
				$decimal_separator = $currency_array["decimal_separator"];
			}			
			$thousands_separator = ",";
			if ( isset ( $currency_array["thousands_separator"] ) ) {
				$thousands_separator = $currency_array["thousands_separator"];
			}
			$decimals = 2;
			if ( isset ( $currency_array["decimals"] ) ) {
				$decimals = $currency_array["decimals"];
			}
					 
			$currency = number_format($this->sanitize($amount), $decimals, $decimal_separator, $thousands_separator);
			
			return $currency;
		}
		
		
		public function format_full($amount) {
			
			$currency_array = $this->all[$this->code];
			$before_placement = true;
			$symbol = $this->render_symbol();
			$value = $this->format($this->sanitize($amount));
			$currency = "";
			
			if ( isset ( $currency_array["symbol_placement"] ) && $currency_array["symbol_placement"] == "after") {
				$before_placement = false;
			}
			if ($before_placement){
				$currency = $symbol." ".$value;
			} else {
				$currency = $value." ".$symbol;
			}
			
			return $currency;
		}
	

}

?>
