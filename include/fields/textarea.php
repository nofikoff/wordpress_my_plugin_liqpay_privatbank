<?php

class Textarea extends ELiq_Pay_Field
{
    protected function init($atts = array(), string $help_text = '') {
        if(is_string($atts)) {
            $help_text = $atts;
            $atts = array(
                'class' => 'large-text'
            );
        } else if(is_array($atts)) {
            foreach ($atts as $attr_name => &$attr_value) {
                if('class' === $attr_name) {
                    $_value = explode(' ', $attr_value);
                    if(count(array_intersect(array('regular-text', 'large-text', 'small-text'), $_value)) == 0) {
                        $_value[] = 'large-text';
                    }

                    $attr_value = implode(' ', array_unique($_value));
                }
            }
            
            if(empty($atts['class'])) {
                $atts['class'] = 'large-text';
            }
            
            $this->atts = $atts;
            $this->help_text = $help_text;
        }
    }
    
    protected function html() {
        printf('<textarea name="%1$s" id="%2$s" %4$s>%3$s</textarea>', $this->name, $this->id, $this->value, $this->fieldAttributes());
    }
}