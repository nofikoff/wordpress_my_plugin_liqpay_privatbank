<?php

function eliqpay_build_tag ($array) {
    $tag = '';
    
    foreach ($array as $key => $value) {
      $tag .= $key . '="' . htmlspecialchars($value) . '" ';
    }
    
    return $tag;
}

function eliqpay_currency_list() {
    return array(
        'UAH' => __('UAH', ELIQPAY_TEXTDOMAIN ),
        'RUB' => __('RUB', ELIQPAY_TEXTDOMAIN ),
        'USD' => __('USD', ELIQPAY_TEXTDOMAIN ),
        'EUR' => __('EUR', ELIQPAY_TEXTDOMAIN )
    );
}

function eliqpay_currency_signs() {
    return array(
        'USD' => '$', 
        'EUR' => '€', 
        'RUB' => '₽', 
        'UAH' => '₴'
    );
}

function eliqpay_language_list() {
    return array(
        'uk' => __('Ukranian', ELIQPAY_TEXTDOMAIN ),
        'ru' => __('Russian', ELIQPAY_TEXTDOMAIN ),
        'en' => __('English', ELIQPAY_TEXTDOMAIN )
    );
}

function eliqpay_parse_func_args($func_args) {
    if(count($func_args) < 2) {
        throw new \ELiq_Pay_Exception(__('Pass too low arguments. Must pass 2 or more', ELIQPAY_TEXTDOMAIN));
    }
    
    $name = $func_args[0];
    $atts = array();
    $id = null;
    
    if(is_string($func_args[1])) {
        $current_value = $func_args[1];
    } else {
        throw new \ELiq_Pay_Exception(__('Saved value for input field must be string', ELIQPAY_TEXTDOMAIN));
    }
    
    if(count($func_args) === 3) {
        if(is_array($func_args[2])) {
            $atts = $func_args[2];
        } else {
            $id = (string) $func_args[2];
        }
    }
    
    if(count($func_args) === 4) {
        $id = $func_args[2];
        $atts = $func_args[3];
    }
    
    $_atts = array();
    $_atts['name'] = $name;
    
    if($id) {
        $_atts['id'] = $id;
    }
    
    if($atts) {
        foreach ($atts as $attr_name => $attr_value) {
            if(!isset($_atts[$attr_name])) {
                $_atts[$attr_name] = $attr_value;
            }
        }
    }
    
    return array($current_value, eliqpay_build_tag($_atts));
}

function eliqpay_currency_select() {
    list($current_value, $atts_string) = eliqpay_parse_func_args(func_get_args());
    
    $select = sprintf("<select %s>", $atts_string);
    
    foreach(eliqpay_currency_list() as $currency => $label) {
        $select .= sprintf('<option value="%1$s" %3$s>%2$s</option>', $currency, $label, selected($currency, $current_value, false));
    }
    
    $select .= '<select>';
    
    return $select;
}

function eliqpay_language_select() {
    list($current_value, $atts_string) = eliqpay_parse_func_args(func_get_args());
    
    $select = sprintf("<select %s>", $atts_string);
    
    foreach(eliqpay_language_list() as $language_code => $label) {
        $select .= sprintf('<option value="%1$s" %3$s>%2$s</option>', $language_code, $label, selected($language_code, $current_value, false));
    }
    
    $select .= '<select>';
    
    return $select;
}

function eliqpay_curreny_signs_to_put(string $field_id_to_put) {
    $list_list = array();
    foreach(eliqpay_currency_signs() as $sign) {
        $list_list[] = '<a href="'.$sign.'" onclick="input=document.getElementById(\''.$field_id_to_put.'\');input.value=input.value+this.attributes.href.value;input.focus();return false;">'.$sign.'</a>';
    }
    
    echo implode(', ', $list_list);
}