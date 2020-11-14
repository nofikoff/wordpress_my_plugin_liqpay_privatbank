<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ELiq_Pay_Field
 *
 * @author vanilium
 */
abstract class ELiq_Pay_Field {
    #TODO: in atts add row_class
    protected $type = '';
    
    protected $id = null;
    protected $label = null;
    protected $name = null;
    protected $value = '';
    protected $help_text = '';
    
    protected $atts = array();
    protected $args = array();

    public function __construct() {
        $args = func_get_args();
        $name = (string) $args[0];
        
        $this->type = strtolower(static::class);
        $this->id = 'eliqpay-'.$this->type.'-'.$name;
        $this->label = (string) $args[1];
        $this->value = ELiq_Pay::get($name);
        $this->name = ELiq_Pay_Backend::getFieldName($name);
        
        $args = array_slice($args, 2);
        
        $this->args['label_for'] = $this->id;
        
        $this->init(...$args);
    }

    final public static function __callStatic($name, $args) {
        if(file_exists(ELIQPAY_CORE.'/fields/'.$name.'.php')) {
            include_once ELIQPAY_CORE.'/fields/'.$name.'.php';
            $class_name = ucwords($name);
            
            $field = new $class_name(...$args);
            
            return $field;
        } else {
            throw new ELiq_Pay_Exception(sprintf(__('Not define the fields "%s"', $name), ELIQPAY_TEXTDOMAIN));
        }        
    }
    
    private function filterAtts() {
        foreach($this->atts as $attr_name => $attr_value) {
            if(in_array($attr_name, array('id', 'name', 'value'))) {
                unset($this->atts[$attr_name]);
            }
        }
    }


    final public function registerFor($page, $section = 'default') {
        if(array_key_exists('row_class', $this->atts)) {
            $this->args['class'] = $this->atts['row_class'];
            unset($this->atts['row_class']);
        }
        add_settings_field($this->id, $this->label, array($this, 'render'), $page, $section, $this->args);
    }
    
    final public function render() {
        $this->filterAtts();
        
        $this->html();
        if($this->help_text) {
            echo '<p class="eliqpay-help-text" style="color: #999;"><i>'.$this->help_text.'</i></p>';
        }
    }
    
    final protected function fieldAttributes() {
        array_walk($this->atts, function(&$value, $name) {
            $value = $name.'="'.$value.'"';
        });
        
        return implode(' ', $this->atts);
    }

    abstract protected function init();
    abstract protected function html();
}
