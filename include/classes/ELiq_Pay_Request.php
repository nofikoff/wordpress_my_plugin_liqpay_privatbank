<?php

class ELiq_Pay_Request {    
    const API_VERSION = '3';
    const LANGUAGES = array('ru', 'uk', 'en');
    const PAYTAYPES = array('card', 'liqpay', 'privat24', 'masterpass', 'moment_part', 'cash', 'invoice', 'qr');
    const CURRENCIES = array('USD', 'EUR', 'RUB', 'UAH');
    const ACTIONS = array('pay', 'hold', 'subscribe', 'paydonate', 'auth');
    const ANSWERS_DB_OPTION = 'elp_request_answers';

    static private $api = null;
    
    private $public_key = null;
    
    private $action = null;
    
    private $amount = null;
    
    private $currency = '';
    
    private $description = '';
    
    private $expired_date = null;
    
    private $order_id = null;
    
    private $language = null;
    
    private $paytypes = null;
    
    private $result_url = '';
    
    private $server_url = null;
    
    protected $requst_params = array(
        'amount', 'currency', 'description', 'expired_date', 'order_id', 'language', 'paytypes', 'result_url', 'action'
    );

    private function __construct($action = null) {
		require_once ELIQPAY_CORE .'/SDK/LiqPay.php';
        
        $this->public_key = ELiq_Pay::get('public_key');
        $private_key = ELiq_Pay::get('private_key');
        
        if(!$this->public_key && !$private_key) {
            throw new ELiq_Pay_Exception(__('Not set public or private keys', ELIQPAY_TEXTDOMAIN), 1);
        }
        
        if(null === self::$api) {
            self::$api = new LiqPay($this->public_key, $private_key);
        }
        
        if($action) {
            $this->action($action);
        }
        
        $this->set_default_options();
	}
    
    static public function make($action = null) {
        return new self($action);
    }
    
    private function set_default_options() {    
        if(is_string(ELiq_Pay::get('currency'))) {
            $this->currency = ELiq_Pay::get('currency');
        }
        $this->order_id = $this->make_order_id();
        $this->language = ELiq_Pay::get('language');
        $this->paytypes = ELiq_Pay::get('paytypes');
        $this->server_url = site_url('easyliqpay/callback');
    }
    
    final public function action(string $action) {
        if(!in_array($action, self::ACTIONS)) {
            throw new ELiq_Pay_Exception(__('Not valid payment action', ELIQPAY_TEXTDOMAIN), 3);
        }
        
        $this->action = $action;
        
        return $this;
    }
    
    final public function currency(string $currency) {
        if(!in_array($currency, self::CURRENCIES)) {
            throw new ELiq_Pay_Exception(__('Not valid payment currency', ELIQPAY_TEXTDOMAIN), 3);
        }
        
        $this->currency = $currency;
        
        return $this;
    }
    
    final public function amount(string $amount) {
        if(!is_numeric($amount)) {
            throw new ELiq_Pay_Exception(__('Not valid amount value', ELIQPAY_TEXTDOMAIN), 3);
        }
        
        $this->amount = $amount;
        
        return $this;
    }
    
    final public function description(string $description) {
        if(empty($description)) {
            throw new ELiq_Pay_Exception(__('Payment description can\'t empty', ELIQPAY_TEXTDOMAIN), 3);
        }
        
        $this->description = $description;
        
        return $this;
    }
    
    final public function order_id(string $order_id = '') {
        $this->order_id = !empty($order_id) ? $order_id : $this->make_order_id() ;
        
        return $this;
    }
    
    final public function language(string $language) {
        if(!in_array($language, self::LANGUAGES)) {
            throw new ELiq_Pay_Exception(__('Not valid LiqPay language', ELIQPAY_TEXTDOMAIN), 3);
        }
        
        $this->language = $language;
        
        return $this;
    }
    
    final public function paytypes(string $paytypes) {
        if(!in_array($paytypes, self::PAYTAYPES)) {
            throw new ELiq_Pay_Exception(__('Not valid paytype', ELIQPAY_TEXTDOMAIN), 3);
        }
        
        $this->paytypes = $paytypes;
        
        return $this;
    }
    
    final public function result_url($page_url) {
        if(is_numeric($page_url)) {
            $_page_url = get_permalink($page_url);
        } else if(filter_var($page_url, FILTER_VALIDATE_URL) && strpos($page_url, 'http') === 0) {
            $_page_url = $page_url;
        } else {
            $_page_url = site_url();
        }
        
        $this->result_url = $_page_url;
        
        return $this;
    }


    final public function set_data(array $data) {
        try {
            foreach($data as $prop => $value) {
                if(method_exists($this, $prop)) {
                    call_user_func(array($this, $prop), $value);
                }
            }
        } catch (ELiq_Pay_Exception $ex) {
            wp_die($ex->getMessage());
        }
        
        return $this;
    }

	final public function request(string $request_type = '') {
        if(null === $this->action) {
            throw new ELiq_Pay_Exception(__('Not set request action', ELIQPAY_TEXTDOMAIN), 2);
        }
        
        $request_id = md5($this->order_id.microtime());
        
        $param = array(
            'version' => self::API_VERSION,
            'public_key' => $this->public_key,
            'server_url' => $this->server_url,
            'info' => $request_id,
        );
        $param = array_merge($this->get_params(), $param);
        
        $this->action = null;
        
        self::saveAnswer($request_id);
        
        if(in_array($request_type, array('cnb_form_raw', 'cnb_form'))) {
            return call_user_func(array(self::$api, $request_type), $param);
        } else {
            $request_result = self::$api->api('request', $param);
            
            return $request_result;
        }
	}
    
    private function make_order_id() {
        return substr( base64_encode(rand(100,999) . rand(100,999) . rand(100,999) ), 0, 8 );
    }
    
    final private function get_params() {
        $_params = array();
        foreach($this->requst_params as $param_name) {
            if(property_exists($this, $param_name) && !empty($this->{$param_name})) {
                $_params[$param_name] = $this->{$param_name};
            }
        }
        
        return $_params;
    }
    
    final static public function saveAnswer($request_id, $answer = '') {
        $request_answers = get_option(self::ANSWERS_DB_OPTION, array());
        
        if(!isset($request_answers[$request_id])) {
            $request_answers[$request_id] = array(
                'time' => time()
            );
        }
        
        $request_answers[$request_id]['answer'] = $answer;
        
        update_option(self::ANSWERS_DB_OPTION, $request_answers);
    }
    
    final static public function getSignature($data) {
        return self::$api->str_to_sign($data);
    }
}