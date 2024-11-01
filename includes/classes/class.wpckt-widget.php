<?php
/**
 * Wordpress Checkout 
 * Plugin URI: http://wordpress-checkout.com/
 *
*/

// register WPCHKT_Widget
add_action( 'widgets_init', create_function( '', 'register_widget( "WPCHKT_Widget" );' ) );


class WPCHKT_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'wpckt_widget', // Base ID
			'WP Checkout Shopping Cart', // Name
			array( 'description' => __( 'Allows to see the products added and to access the process page','wpckt') ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		
    	global $wpckt_options;		
		if (!isset($wpckt_options)){
		  $wpckt_options = wpckt_get_options();
		}
		
	
		global $wpckt_cart;
		$items = 0;
		
		$checkout_page = $wpckt_options['checkout_page'];			
		
		extract( $args );
		
		if (!isset($wpckt_cart)){
			$wpckt_cart = new WPCKT_CART();			
		} 
		
		$items = $wpckt_cart -> itemscount;
		
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;		
				
		if (count($instance)){
			
			$checkout_page = wpckt_checkout_url();
			
			if (isset($checkout_page)){
								
			  echo "<h3 class='widget-title'>".$instance['title']."</h3>";
			  echo "<p><a href='".$checkout_page."' class='wpckt_widget_btn_link'><span class='wpckt_widget_qty'>".$items."</span> ".$instance['info']."</a></p>";			
			  echo "<a href='".$checkout_page."' class='wpckt_widget_btn_link'><input name='wpckt_widget_btn' class = 'wpckt_widget_btn' type='button' value='".$instance['button']."' /></a>";
			  
			}
			
			// adding js to update widget
			if ( !wp_script_is( 'wpckt_script' ) ) {
			  wp_enqueue_script( 'wpckt_script', WPCKT_PLUGIN_URL.'/presentation/default/js/wpckt.js', array( 'jquery' ) );
			  wp_localize_script( 'wpckt_script', 'wpcktSettings', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
			}
		}
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		foreach ($new_instance as $key => $value){
		  $instance[$key] = strip_tags( $value );
		}

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		
		extract($instance);	
		
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
			$info = $instance[ 'info' ];
			$button = $instance[ 'button' ];			
		}
		else {
			$title = '';
			$info = __('items in your cart','wpckt');
			$button = __('Proceed to checkout','wpckt');			
		}
		?>
        <p>
          <label for="<?php echo $this->get_field_id( 'title' ); ?>">
            <?php _e("Title:","wpckt") ?>
          </label>
          <br />
          <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>    
        <p>
          <label for="<?php echo $this->get_field_id( 'info' ); ?>">
            <?php _e("Cart message:","wpckt") ?>
          </label>
          <br />
          <?php echo __("e.g.","wpckt")." <strong>2</strong>"; ?>
          <input id="<?php echo $this->get_field_id( 'info' ); ?>" name="<?php echo $this->get_field_name( 'info' ); ?>" type="text" value="<?php echo esc_attr( $info ); ?>" />
        </p>
        <p>
        <label for="<?php echo $this->get_field_id( 'button' ); ?>">
          <?php _e("Button info:","wpckt") ?>
        </label>
        <input id="<?php echo $this->get_field_id( 'button' ); ?>" name="<?php echo $this->get_field_name( 'button' ); ?>" type="text" value="<?php echo esc_attr( $button ); ?>" />
        </p>
<?php 
	}

} // class wpckt_Widget

?>
