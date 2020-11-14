<?php

class Radio extends ELiq_Pay_Field
{
    protected $options = array();
    
    protected function init($options = array(), $atts = array(), string $help_text = '') {
        if(is_string($options)) {
            $help_text = $options;
            
            $atts = array();
            $options = array();
        }
        
        if(!empty($help_text)){
            $this->help_text = $help_text;
        }
        
        if(empty($options)) {
            throw new ELiq_Pay_Exception(__('No have set options for radio buttons', ELIQPAY_TEXTDOMAIN));
        }
        
        $this->atts = $atts;
        $this->options = $options;
    }
    
    protected function html() {
        foreach($this->options as $_key => $label) {
            if(is_int($_key)) {
                $_key = $label;
            }

            $input = sprintf('<input type="radio" name="%1$s" value="%2$s" %3$s>', $this->name, $_key, checked($_key, $this->value));

            echo '<label>'.$input.' '.$label.'</label><br>';
        }
    }
}