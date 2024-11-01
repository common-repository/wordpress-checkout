<?php
/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

class WPCHKT_Orders_Engine {

    public $table_id = "wpckt_orders";	
	
	public function get_attributes(){
				
		     return array(
						  array( "title"=>__('Date'),
						         "name"=>"process_time",
								 "prop"=>"datetime  NOT NULL"
								),
								
						  array( "title"=>__('Order ID'),
						         "name"=>"order_id",
								 "prop"=>"tinytext  NULL"
								),

						  array( "title"=>__('Quantity'),
						         "name"=>"quantity",
								 "prop"=>"tinytext  NULL"
								),
								
						  array( "title"=>__('Items'),
						         "name"=>"items",
								 "prop"=>"tinytext  NULL"
								),
								
						  array( "title"=>__('Sub Total'),
						         "name"=>"sub_total",
								 "prop"=>"tinytext  NULL"
								),
								
						  array( "title"=>__('Gross'),
						         "name"=>"gross",
								 "prop"=>"tinytext  NULL"
								),
								
						  array( "title"=>__('First Name'),
						         "name"=>"firstname",
								 "prop"=>"tinytext  NULL"
								),
						  array( "title"=>__('Last Name'),
						         "name"=>"lastname",
								 "prop"=>"tinytext  NULL"
								),
						  array( "title"=>__('Email'),
						         "name"=>"email",
								 "prop"=>"tinytext  NULL"
								),
						  array( "title"=>__('Address'),
						         "name"=>"address",
								 "prop"=>"tinytext  NULL"
								),
								
						  array( "title"=>__('Status'),
						         "name"=>"status",
								 "prop"=>"tinytext  NULL"
								),																							
											 
						  array( "title"=>__('Verify Sign'),
						         "name"=>"verify_sign",
								 "prop"=>"text NULL"
								),
						  array( "title"=>__('Gateway'),
						         "name"=>"gateway",
								 "prop"=>" tinytext  NULL"
								),
						  array( "title"=>__('Type'),
						         "name"=>"type",
								 "prop"=>" tinytext  NULL"
								),

						  array( "title"=>__('Shipping'),
						         "name"=>"shipping",
								 "prop"=>"tinytext  NULL"
								),
						  array( "title"=>__('Tax'),
						         "name"=>"tax",
								 "prop"=>"tinytext  NULL"
								),																																	

						  array( "title"=>__('Detail'),
						         "name"=>"details",
								 "prop"=>" text  NULL"
								)
						);
	}
	
	public function is_installed() {
	   global $wpdb;
	
	   $table_name = $wpdb->prefix . $this->table_id;		
		
       if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		   return false;		   
	   } else {
		   return true;
	   }
	}
	
	
	public function install() {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->table_id;
		
		if ( defined( 'DB_CHARSET' ) ) $charset = DB_CHARSET;
		else $charset = 'utf8';	
		
		$sql = "CREATE TABLE " . $table_name . " (";
		$sql .= "id mediumint(9) NOT NULL AUTO_INCREMENT,";
		
		foreach ($this->get_attributes() as $column) {
			$sql .= $column['name']." ".$column['prop'].", 
			";
		}
		
		$sql .= " UNIQUE KEY id (id)
		) COLLATE utf8_general_ci CHARACTER SET ".$charset.";";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		dbDelta($sql);
		
		// Doble check
		if($this->is_installed()) {
		  return true;
		} else {
		  return false;
		}
				
	}	
					  
					  
    public function insert( $order ) {

		global $wpdb;
	
	   	$table_name = $wpdb->prefix . $this->table_id;

     	$sql = "INSERT INTO " . $table_name .
					" (";
		foreach ($this->get_attributes() as $column) {			
			$sql .= $column['name'].", ";
		}
		// Remove last comma
		$sql = substr(trim($sql), 0, -1);

		$sql .= ") VALUES (";
					  
		foreach ($this->get_attributes() as $column) {
			$value = (  isset(  $order[$column['name']] )? $order[$column['name']] :""  );			
			$sql .= $wpdb->prepare ("'%s',", $value);
		}						
		// Remove last comma
		$sql = substr(trim($sql), 0, -1); 
				  
		$sql .= ");"; 
  
		$results = $wpdb->query( $sql );
		if ($results == 1) return true;
		else  return false;		

	}
	
    public function update( $order, $check_column, $check_value ) {

		global $wpdb;
	
	   	$table_name = $wpdb->prefix . $this->table_id;
		
     	$sql = "UPDATE " . $table_name . " SET ";
		
		foreach ($order as $column => $value) {			
			$sql .= $column."=".$wpdb->prepare ("'%s',", $value);
		}
		// Remove last comma
		$sql = substr(trim($sql), 0, -1);

		$sql .= " WHERE ".$check_column."=".$wpdb->prepare ("'%s',", $check_value);

		// Remove last comma
		$sql = substr(trim($sql), 0, -1).";";		  
					  
		$results = $wpdb->query( $sql );
		if ($results == 1) return true;
		else  return false;		

	}	

	public function delete( $id ) {
		
		global $wpdb;
	
	   	$table_name = $wpdb->prefix . $this->table_id;
		
			$delete = "DELETE FROM ". $table_name ." WHERE id = '".$id."';";
			$results = $wpdb->query($delete);
			if($results == 1){
				return true; 
			} else {	  
				return false;
			}		

	}
	
	public function get_orders() {
		
		global $wpdb;
	
	   	$table_name = $wpdb->prefix . $this->table_id;
	  
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
	
		  $orders_row = "SELECT * FROM ". $table_name ." ORDER BY id DESC;";
			if($results = $wpdb->get_results( $orders_row,"ARRAY_A")){
			   return $results; 
			} else {	  
			   return false;
			}
		} else {	  
		  return false;	  
		}		

	}
	
	public function get_order($column, $value) {
		
		global $wpdb;
	
	   	$table_name = $wpdb->prefix . $this->table_id;
	  
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
	
		  $orders_row = "SELECT * FROM ". $table_name ." WHERE ".$column."='".$value."' ORDER BY id DESC;";
			if($results = $wpdb->get_results( $orders_row,"ARRAY_A")){
			return $results; 
			} else {	  
			return false;
			}
		} else {	  
		  return false;	  
		}		

	}
	
	public function get_order_status( $order_id ) {
		
		$order_list = $this->get_order('order_id', $order_id);
		
		if( $order_list !== false ) {
            $order = $order_list[0];
			if (isset($order['status'])){
			     return $order['status'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	
	public function set_order_status( $order_id, $status ) {
		
		if( $order = get_order('order_id', $order_id)) {

			global $wpdb;
		
			$table_name = $wpdb->prefix . $this->table_id;
	
			$insert = $wpdb->prepare("UPDATE ". $table_name ." SET status='".$status."';");			
			
			$results = $wpdb->query( $insert );
			
			if ($results == 1) return true;
			
			else  return false;	
			
		} else {
			return false;
		}	
		
		
	}	
	
} // class WPCHKT_Orders

?>
