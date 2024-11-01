<?php
/**
 * Wordpress Checkout 
 * Presentation: Default 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

/*
Based on Webforce Cart v.1.5
*/

class WPCKT_CART {
	
	var $id = "";
	var $itemscount = 0;
	var $items = array();
    var $itemsprice = array();
	var $itemsqt = array();
    var $itemsname = array();
	var $itemsurl = array();
	var $itemshipping = array();
	var $itemsweight = array();
	var $user_info = array();
	var $total = 0;
	var $total_plus = 0;
	var $total_shipping = 0;
	var $total_weight = 0;
	
	public function __construct($id) {
		$this->id = $id;
	}

	function get_content()
	{ // gets cart contents
		$items = array();
		foreach($this->items as $tmp_item)
		{
		    $item = FALSE;

			$item['id'] = $tmp_item;
            $item['qty'] = $this->itemsqt[$tmp_item];
			$item['price'] = $this->itemsprice[$tmp_item];
			$item['shipping'] = $this->itemshipping[$tmp_item];
			$item['weight'] = $this->itemsweight[$tmp_item];
			$item['name'] = $this->itemsname[$tmp_item];
			$item['url'] = $this->itemsurl[$tmp_item];
			$item['subtotal'] = $item['qty'] * $item['price'];
			
            $items[] = $item;
		}
		return $items;
	} // end of get_contents


	function add_item($itemid, $name = FALSE, $url = FALSE, $qty=1, $price = FALSE, $shipping = 0, $weight = 0 )
	{ // adds an item to cart                

		if(isset($this->itemsqt[$itemid]))
                { // the item is already in the cart..
		  // so we'll just increase the quantity
			$this->itemsqt[$itemid] = $qty + $this->itemsqt[$itemid];
			$this->update_total();
		} else {
			$this->items[]=$itemid;
			$this->itemsqt[$itemid] = $qty;
			$this->itemsprice[$itemid] = $price;
			$this->itemsname[$itemid] = $name;
			$this->itemsurl[$itemid] = $url;
			$this->itemshipping[$itemid] = $shipping;
			$this->itemsweight[$itemid] = $weight;
		}
		$this->update_total();
	} // end of add_item


	function edit_item($itemid,$qty)
	{ // changes an items quantity

		if($qty < 1) {
			$this->del_item($itemid);
		} else {
			$this->itemsqt[$itemid] = $qty;
			// uncomment this line if using 
			// the wf_get_price function
			// $this->itemsprice[$itemid] = wf_get_price($itemid,$qty);
		}
		$this->update_total();
	} // end of edit_item


	function del_item($itemid)
	{ // removes an item from cart
        
		foreach($this->items as $i => $item)
		{
			if($item == $itemid)
			{
				unset($this->items[$i]);
			}
		}
        unset($this->itemsprice[$itemid]);
		unset($this->itemsqt[$itemid]);
		unset($this->itemsname[$itemid]);
		unset($this->itemsurl[$itemid]);
		unset($this->itemshipping[$itemid]);
		unset($this->itemsweight[$itemid]);

		$this->update_total();
	} //end of del_item


    function empty_cart()
	{ // empties / resets the cart
	    $this->id = "";		
		$this->total = 0;
		$this->itemscount = 0;
		$this->items = array();
		$this->itemsprice = array();
		$this->itemsqt = array();
		$this->itemsname = array();
		$this->itemsurl = array();
		$this->itemshipping = array();
		$this->itemsweight = array();
	} // end of empty cart


	function update_total()
	{ // internal function to update the total in the cart
	    $this->itemscount = 0;
		$this->total = 0;
		$this->total_plus = 0;
		$this->total_shipping = 0;
		$this->total_weight = 0;
		global $wpckt_currency;	
		
        if(sizeof($this->items > 0)){ 
		   foreach($this->items as $item) {
			   
			  $this->total = $this->total + 
			                 ($this->itemsprice[$item] * $this->itemsqt[$item]);
							 
			  $this->total_plus = $this->total_plus + 
			                      ($this->itemsprice[$item] * $this->itemsqt[$item]) + 
								  ($this->itemshipping[$item] * $this->itemsqt[$item]);
								  
			  $this->total_shipping = $this->total_shipping + 
			                          ($this->itemshipping[$item] * $this->itemsqt[$item]);
									  
			  $this->total_weight = $this->total_weight + 
			                        ($this->itemsweight[$item] * $this->itemsqt[$item]);
									
		      $this->itemscount++;
		   }
		}
	} // end of update_total    
	
}
?>
