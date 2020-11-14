<?php

class Checkbox extends ELiq_Pay_Field
{
    private $list_of_ckbx = false;
    
    protected function init() {
        $args = func_get_args();
        
        switch (count($args)) {
            case 3:
                $this->list_of_ckbx = (array) $args[0];
                $this->atts = (array) $args[1];
                $this->help_text = (string) $args[2];
                
                unset($this->args['label_for']);
                
                break;
            
            case 2:
                if(is_array($args[0]) && is_string($args[1])) {
                    $this->help_text = $args[1];
                    $this->atts = $args[0];
                } else if(is_array($args[0]) && is_array($args[1])) {
                    $this->list_of_ckbx = $args[0];
                    $this->atts = $args[1];
                    
                    unset($this->args['label_for']);
                } else {
                    #error
                }
                
                break;
            
            case 1:
                if(is_array($args[0])) {
                    $this->atts = $args[0];
                } else if(is_string($args[0])){
                    $this->help_text = $args[0];
                } else {
                    #error
                }
                
                break;
                
            default:
                #error
                break;
        }
    }
    
    protected function html() {
        if($this->list_of_ckbx) {
            foreach($this->list_of_ckbx as $_key => $label) {
                if(is_int($_key)) {
                    $_key = $label;
                }
                
                $checked = in_array($_key, (array) $this->value) ? 'checked="checked"' : '';
                
                $input = sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" %3$s>', $this->name, $_key, $checked);
                
                echo '<label>'.$input.' '.$label.'</label><br>';
            }
        } else {
            echo '<input type="checkbox" name="'.$this->name.'" value="set" id="'.$this->id.'" '.$this->fieldAttributes().' '.checked('set', $this->value, false).' />';
        }
    }
}
