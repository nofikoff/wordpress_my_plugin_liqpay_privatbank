<?php
class ELiq_Pay_Donate_Widget extends WP_Widget {

	function __construct() {		
		$widget_ops = array( 
			'classname' => 'elp_widgete_donate',
			'description' => __( 'Donate by LiqPay', ELIQPAY_TEXTDOMAIN ),
		);
        
        add_action('admin_enqueue_scripts', array($this, '_backend_sources'));
        
		parent::__construct( 'elp_widget_donate', __( 'Donations', ELIQPAY_TEXTDOMAIN ), $widget_ops );
	}
    
    public function _backend_sources($hook) {
        if('widgets.php' === $hook) {
            wp_enqueue_script('eliqpay.backend.donate.widgets', ELIQPAY_PLUGIN_URL.'/js/backend.widgets.js', array('jquery'), ELiq_Pay::VERSION);
        }
    }

	function widget( $args, $instance ) {

		echo $args['before_widget'];
        
		echo ELiq_Pay_Donate::template( $instance );

		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = !empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['text_before'] = ! empty( $new_instance['text_before'] ) ? strip_tags( $new_instance['text_before'] ) : '';
        $instance['output_type'] = ! empty( $new_instance['output_type'] ) ? $new_instance['output_type'] : '';
        
		$instance['default_amount'] = !empty( $new_instance['default_amount'] ) ? $new_instance['default_amount']  : '';
		$instance['description'] = !empty( $new_instance['description'] ) ? strip_tags( $new_instance['description'] ) : '';
        $instance['show_description'] = '1' == $new_instance['show_description'] ? '1' : false;
        
		$instance['currency'] = !empty( $new_instance['currency'] ) ? $new_instance['currency'] : array();
		$instance['language'] = !empty( $new_instance['language'] ) ? $new_instance['language'] : '';
		$instance['result_page_id'] = !empty( $new_instance['result_page_id'] ) ? strip_tags( $new_instance['result_page_id'] ) : '';
        
        #$instance['qr_amount'] = !empty( $new_instance['qr_amount'] ) ? $new_instance['qr_amount'] : '';
		$instance['predefined_amount'] = !empty( $new_instance['predefined_amount'] ) ? $new_instance['predefined_amount'] : '';

		return $instance;
	}
    
    private function defineFormValues($instance) {
        $title = !empty( $instance['title'] ) ? $instance['title'] : __('Donations', ELIQPAY_TEXTDOMAIN ) ;
		$text_before = !empty( $instance['text_before'] ) ? $instance['text_before'] : '';
        $output_type = !empty( $instance['output_type'] ) ? $instance['output_type'] : 'default';
		$default_amount = isset( $instance['default_amount'] ) ? $instance['default_amount'] : '';
		$description = !empty( $instance['description'] ) ? $instance['description'] : '';
        $show_description = !empty( $instance['show_description'] ) ? $instance['show_description'] : false;
		$currency = !empty( $instance['currency'] ) ? $instance['currency'] : array_keys( ELiq_Pay::get('currency') );
		$language = !empty( $instance['language'] ) ? $instance['language'] : ELiq_Pay::get('language');
		$result_page_id = !empty( $instance['result_page_id'] ) ? $instance['result_page_id'] : '-1';
        
        #$qr_amount = !empty( $instance['qr_amount'] ) ? $instance['qr_amount'] : '';        
        $predefined_amount = !empty( $instance['predefined_amount'] ) ? $instance['predefined_amount'] : '';
        
        return compact(
                'title', 
                'text_before',
                'output_type',
                'default_amount',
                'description',
                'show_description',
                'currency',
                'language',
                'result_page_id',
//                'qr_amount',
                'predefined_amount'
        );
    }

	function form( $instance ) {
        extract($this->defineFormValues($instance));
        
        ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', ELIQPAY_TEXTDOMAIN ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'text_before' ); ?>"><?php _e( 'Subtitle', ELIQPAY_TEXTDOMAIN ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'text_before' ); ?>" name="<?php echo $this->get_field_name( 'text_before' ); ?>" type="text" value="<?php echo esc_attr( $text_before ); ?>">
		</p>
        <p>
            <label for="<?php echo $this->get_field_id('output_type') ?>"><?php _e('Output type:', ELIQPAY_TEXTDOMAIN); ?></label>
            <select name="<?php echo $this->get_field_name('output_type') ?>" id="<?php echo $this->get_field_id('output_type') ?>" class="widget-donate-output-type">
                <option value="default" <?php selected('default', $output_type); ?>><?php _e('Form', ELIQPAY_TEXTDOMAIN); ?></option>
                <!--<option value="qr" <?php selected('qr', $output_type); ?>><?php _e('QR-code', ELIQPAY_TEXTDOMAIN); ?></option>-->
                <option value="predefined" <?php selected('predefined', $output_type); ?>><?php _e('Predefined', ELIQPAY_TEXTDOMAIN); ?></option>
            </select>
        </p>
        <div class="output-type-panel" id="output_default">
            <p>
                <label for="<?php echo $this->get_field_id( 'default_amount' ); ?>"><?php _e( 'Default amount', ELIQPAY_TEXTDOMAIN ); ?>:</label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'default_amount' ); ?>" name="<?php echo $this->get_field_name( 'default_amount' ); ?>" type="text" value="<?php echo esc_attr( $default_amount ); ?>">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'currency' ); ?>"><?php _e( 'Currency', ELIQPAY_TEXTDOMAIN ); ?>:</label>
                <?php
                foreach (eliqpay_currency_list() as $key => $value) {
                    $checked = in_array($key, $currency) ? 'checked="checked"' : '' ;
                    echo "<br><label><input type=\"checkbox\" name=\"{$this->get_field_name( 'currency' )}[]\" value=\"$key\" $checked> $value</label>";
                }
                ?>
            </p>
        </div>
        <?php /*
        <div class="output-type-panel" id="output_qr">
            <p>
                <label for="<?php echo $this->get_field_id( 'qr_amount' ); ?>"><?php _e( 'Amount (type value with currency, example: 100$)', ELIQPAY_TEXTDOMAIN ); ?>:</label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'qr_amount' ); ?>" name="<?php echo $this->get_field_name( 'qr_amount' ); ?>" type="text" value="<?php echo esc_attr( $qr_amount ); ?>">
            </p>
            <p><?php _e('Click to put:', ELIQPAY_TEXTDOMAIN); ?>
                <?php eliqpay_curreny_signs_to_put($this->get_field_id( 'qr_amount' )); ?>
            </p>
        </div>
        */ ?>
        <div class="output-type-panel" id="output_predefined">            
            <p>
                <label for="<?php echo $this->get_field_id( 'predefined_amount' ); ?>"><?php _e( 'Amount (type value with currency, example: 100$;20€)', ELIQPAY_TEXTDOMAIN ); ?>:</label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'predefined_amount' ); ?>" name="<?php echo $this->get_field_name( 'predefined_amount' ); ?>" type="text" value="<?php echo esc_attr( $predefined_amount ); ?>">
            </p>
            <p><?php _e('Click to put:', ELIQPAY_TEXTDOMAIN); ?>
                <?php eliqpay_curreny_signs_to_put($this->get_field_id( 'predefined_amount' )); ?>
            </p>
        </div>
        <p>
            <label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Payment description', ELIQPAY_TEXTDOMAIN ); ?>:</label> 
            <input class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" type="text" value="<?php echo esc_attr( $description ); ?>">
        </p>
        <p class="controll--show_description" <?php if($output_type !== 'default') echo 'style="display:none;"'; ?>>
            <input type="checkbox" name="<?php echo $this->get_field_name( 'show_description' ); ?>" id="<?php echo $this->get_field_id( 'show_description' ); ?>" value="1" <?php checked('1', $show_description); ?>>
            <label for="<?php echo $this->get_field_id( 'show_description' ); ?>"><?php _e('Show description field', ELIQPAY_TEXTDOMAIN); ?></label>
        </p>
		<p>
			<label for="<?php echo $this->get_field_id( 'language' ); ?>"><?php _e( 'Language', ELIQPAY_TEXTDOMAIN ); ?>:</label>
            <?php echo eliqpay_language_select($this->get_field_name( 'language' ), $language, $this->get_field_id( 'language' )); ?>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'result_page_id' ); ?>"><?php _e( 'Result URL', ELIQPAY_TEXTDOMAIN ); ?>:</label>
            <?php wp_dropdown_pages(array(
                'selected' => $result_page_id,
                'name' => $this->get_field_name( 'result_page_id' ),
                'id' => $this->get_field_id( 'result_page_id' ),
                'show_option_no_change' => __('Home', ELIQPAY_TEXTDOMAIN)
            )); ?>
		</p>
		<?php
	}
}