<?php

class Select extends ELiq_Pay_Field
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
        
        $this->atts = $atts;
        $this->options = $options;
    }
    
    protected function html() {
        $_options = array(
            sprintf('<option value="">%s</option>', __('Select', ELIQPAY_TEXTDOMAIN))
        );
        
        foreach ($this->options as $option_value => $option_label) {
            if(is_int($option_value)) {
                $option_value = $option_label;
            }
            
            $_options[] = sprintf('<option value="%1$s" %3$s>%2$s</option>', $option_value, $option_label, selected($option_value, $this->value, false));
        }
        
        printf('<select name="%1$s" id="%2$s" %3$s>%4$s</select>', $this->name, $this->id, $this->fieldAttributes(), implode("\n", $_options));
    }
}