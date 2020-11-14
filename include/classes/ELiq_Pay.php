<?php
#TODO: Add cron to crean unused callbacks
/* 
 * 1. Guttenberg/Editor button
 * 2. Widget customization
 */
final class ELiq_Pay {
    const VERSION = '0.9';
    const OPTION_PREFIX = 'eliqpay_option';
    
    static private $instance = null;
    
    private $settings = null;
    private $general_settings = null;
    
    private $option_page = 'general';
    
    static public function getInstance() {
        if(null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        require_once ELIQPAY_CLASSES_PATH.'/ELiq_Pay_Request.php';
        
        if(apply_filters('eliqpay.donate.enabled', true)) {
            require_once ELIQPAY_CLASSES_PATH.'/ELiq_Pay_Donate.php';
            ELiq_Pay_Donate::init();
        }

        if(is_admin()) {
            add_action('init', array($this, '_settingPage'), 99);
        }

        add_action( 'init', array($this, '_callbackRewrite') );
        
        add_action( 'wp_enqueue_scripts', array($this, '_sources'));
        
        add_action('wp_ajax_check_answers', array($this, '_check_answers'));
        add_action('wp_ajax_nopriv_check_answers', array($this, '_check_answers'));
        
        $this->general_settings = get_option($this->getPageOptionName());
    }
    
    #Load options
    static public function get($option_name) {
        $instance = self::getInstance();
        $value = null;
        if(isset( $instance->settings[$option_name] )) {
            $value = $instance->settings[$option_name];
        } else if (isset($instance->general_settings[$option_name])) {
            $value = $instance->general_settings[$option_name];
        }
        
        return $value;
    }
    
    static public function request(callable $callback = null, string $request_type = '') {        
        $request = ELiq_Pay_Request::make();
        
        if($callback) {
            call_user_func($callback, $request);
        }
        
        return $request->request($request_type);
    }
    
    public function setOptionPage(string $option_page) {
        $instance = self::getInstance();
        
        $instance->option_page = $option_page;
        
        if(false === ($_settings = get_option($instance->getPageOptionName()))) {
            throw new ELiq_Pay_Exception(sprintf(__('Options "%s" not defined', ELIQPAY_TEXTDOMAIN), $option_page));
        }
        
        $instance->settings = $_settings;
    }
    
    public function getPageOptionName() {
        return self::OPTION_PREFIX.'_'.$this->option_page;
    }
    
    static public function setDefaultOptions() {
        $default_options = array(
            'private_key'	=> '',
            'public_key'	=> '',
            'currency' => array('UAH'),
            'language' => 'ru',
        );
        
        add_option( self::OPTION_PREFIX.'_general', $default_options, false, false );
    }
    
    public function _settingPage() {
        require_once ELIQPAY_CLASSES_PATH .'/ELiq_Pay_Backend.php';
        
        ELiq_Pay_Backend::init();
    }
    
    public function _callbackRewrite() {
        add_rewrite_rule('^easyliqpay/callback$', '/wp-content/plugins/easyliqpay/include/callback\.php', 'top');
    }
    
    public function _sources() {
        wp_enqueue_script('elp.source.scripts', ELIQPAY_PLUGIN_URL.'public/assets/js/scripts.js', array('jquery'), self::VERSION, true);
        
        wp_localize_script( 'elp.source.scripts', 'eliqpay', array(
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }
    
    public function _check_answers() {
        if(empty($_POST['id'])) {
            wp_send_json_error(array(
                'type' => 'miss_id'
            ));
        }
        
        $request_id = $_POST['id'];
        $saved_answers = get_option(ELiq_Pay_Request::ANSWERS_DB_OPTION, array());
        
        if(!empty($saved_answers[$request_id])) {
            wp_send_json_success($saved_answers[$request_id]);
        } else {
            wp_send_json_error();
        }        
    }
    
    static public function parseAmountString(string $amount_with_currency) {
        if(strpos($amount_with_currency, ';')) {
            $_stack_amounts = explode(';', $amount_with_currency);
            $amount_with_currency = $_stack_amounts[0];
        }
        
        $amount_with_currency = trim($amount_with_currency);
        
        $currency_sign = mb_substr($amount_with_currency, -1);
        $amount = filter_var($amount_with_currency, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
        
        $amount = (float) str_replace(',', '.', $amount);
        
        $currency = 'UAH';
        foreach( eliqpay_currency_signs() as $code => $sign) {
            if($currency_sign === $sign) {
                $currency = $code;
            }
        }
        
        return array((string) $amount, $currency);
    }
}