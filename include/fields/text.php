<?php

class Text extends ELiq_Pay_Field
{
    protected function init($atts = array(), string $help_text = '') {
        if(is_string($atts) && empty($help_text)) {
            $help_text = $atts;
            $atts = array();
        }
        
        $this->help_text = $help_text;
        
        if(empty($atts)) {
            $this->atts['class'] = 'regular-text';
        }
        
        foreach($atts as $attr_name => $attr_value) {
            if('class' === $attr_name) {
                $_value = explode(' ', $attr_value);
                if(count(array_intersect(array('regular-text', 'large-text', 'small-text'), $_value)) == 0) {
                    $_value[] = 'regular-text';
                }
                
                $attr_value = implode(' ', array_unique($_value));
            }
            
            if('type' === $attr_name && in_array($attr_value, array('email', 'number', 'password', 'tel'))) {
                
                $this->type = $attr_value;
                
                continue;
            }
            
            $this->atts[$attr_name] = $attr_value;
        }
        
        if(empty($this->atts['class'])) {
            $this->atts['class'] = 'regular-text';
        }
    }
    
    public function html() {
        echo '<input type="'.$this->type.'" name="'.$this->name.'" value="'.$this->value.'" id="'.$this->id.'" '.$this->fieldAttributes().'/>';
    }
}