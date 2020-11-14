<?php

class ELiq_Pay_Donate {
    static private $instance = null;
    
    private $template_atts = array();
    
    private function __construct() {        
        add_action( 'widgets_init', array($this, '_widgetRegisters') );
        add_shortcode( 'elp_donate', array( $this, '_shortcode'));
        
        add_action( 'wp_ajax_elp_donate', array($this, '_donateRequest') );
		add_action( 'wp_ajax_nopriv_elp_donate', array($this, '_donateRequest') );
        
        add_action('wp_footer', array($this, '_sources'));
    }
    
    static public function init() {
        $instance = self::getInstance();
    }
    
    static public function getInstance() {
        if(null === self::$instance) {
            self::$instance = new ELiq_Pay_Donate();
        }
        
        return self::$instance;
    }
    
    private function setDefaultOptions() {
        #ELiq_Pay::getInstance()->setOptionPage('donate');
        
        $this->template_atts = array(
            'button_text' => __( 'Donate!', ELIQPAY_TEXTDOMAIN ),
            'text_before' => '',
            'title' => '',
            'default_amount' => '',
            'language' => ELiq_Pay::get('language'),
            'description' => '',
            'result_url' => home_url('/'),
            'currency' => ELiq_Pay::get('currency')
        );
    }
    
    private function registerSources() {
        if ( !wp_script_is( 'eliqpay.donate.scripts', 'registered' ) ) {
            wp_register_script( 'eliqpay.donate.scripts', ELIQPAY_PLUGIN_URL.'/public/widgets/donate/assets/js/scripts.js', array('jquery'), ELiq_Pay::VERSION, true );
        }

        if ( !wp_style_is( 'eliqpay.donate.styles', 'registered' ) ) {
            wp_register_style( 'eliqpay.donate.styles', ELIQPAY_PLUGIN_URL.'/public/widgets/donate/assets/css/styles.css', array(), ELiq_Pay::VERSION );
        }
    }
    
    public function _sources() {
        wp_enqueue_script( 'eliqpay.donate.scripts' );
        wp_enqueue_style('eliqpay.donate.styles');
    }
    
    public function _donateRequest() {
        if(!wp_doing_ajax()) {
            return;
        }
        
        try {
            $result = ELiq_Pay::request(function($request) {
                #ELiq_Pay::getInstance()->setOptionPage('donate');

                $request->set_data(array(
                    'action' => 'paydonate',
                    'description' => ELiq_Pay::get('payment_description'),
                    'result_url' => home_url('/')
                ));
                
                if(!empty($_POST['description'])) {
                    $request->description($_POST['description']);
                }

                if(!empty($_POST['amount'])) {
                    $request->amount($_POST['amount']);
                }

                if(!empty($_POST['currency'])) {
                    $request->currency($_POST['currency']);
                }

                if(!empty($_POST['result_url'])) {
                    $request->result_url($_POST['result_url']);
                }
            }, 'cnb_form_raw');
            
            wp_send_json_success($result);
        } catch (ELiq_Pay_Exception $ex) {
            wp_send_json_error($ex->getMessage());
        }
        
        wp_die();
    }
    
    public function _widgetRegisters() {        
        require_once ELIQPAY_WIDGETS_PATH.'/donate/ELiq_Pay_Donate_Widget.php';
        
        register_widget( 'ELiq_Pay_Donate_Widget' );
    }
    
    public function _shortcode($atts = array()) {
        $atts = shortcode_atts( [
                'button_text' => '',
                'title' => ''
            ],
            $atts,
            'elp_donate'
        );
        
        return self::template($atts);
    }
    
    static public function template($atts) {
        $instance = self::getInstance();
        
        $instance->registerSources();
        
        if(empty($instance->template_atts)) {
            $instance->setDefaultOptions();
        }
        
        if(isset($atts['result_page_id'])) {
            if(is_numeric($atts['result_page_id']) && -1 !== (int) $atts['result_page_id']) {
                $atts['result_url'] = get_permalink($atts['result_page_id']);
            }
            
            unset($atts['result_page_id']);
        }
        
        $instance->template_atts = wp_parse_args($atts, $instance->template_atts );
        
        ob_start();
        ?>
        <div class="elp-conteiner">
            <?php if($instance->template_atts['title']): ?>
            <div class="elp-title"><?php echo $instance->template_atts['title']; ?></div>
            <?php endif; ?>
            <?php if($instance->template_atts['text_before']): ?>
            <p class="elp-pre-text"><?php echo $instance->template_atts['text_before']; ?></p>
            <?php endif; ?>
        <?php
        $output_method = 'html'. ucfirst($instance->template_atts['output_type']);
        
        try {
            if(method_exists($instance, $output_method)) {
                $instance->{$output_method}();
            }
        } catch (ELiq_Pay_Exception $ex) {
            wp_die($ex->getMessage());

	print_r($ex->getMessage());

        }
        
        ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    private function htmlDefault() {
        extract( $this->template_atts);
        
        $amound = !empty( $default_amount ) ? $default_amount : '';
        
        $currency_signs = eliqpay_currency_signs();
        ?>
        <form class="elp-donate-form" method="POST" accept-charset="utf-8" action="<?php echo ELIQPAY_PLUGIN_URL; ?>public/noscripthandler.php">
            <input type="hidden" name="language" value="<?php echo $language; ?>" />
            <input type="hidden" name="result_url" value="<?php echo $result_url; ?>" />
            <div class="elp-input-holder">
                <input type="text" name="amount" value="<?php echo $amound; ?>" required />
                <select name="currency" class="elp-input-currency">
                    <?php foreach($currency as $currency_item) {
                        printf('<option value="%1$s">%2$s</option>', $currency_item, $currency_signs[$currency_item]);
                    } ?>
                </select>
            </div>
            <?php if($show_description && $output_type === 'default'): ?>
            <textarea name="description" cols="30" rows="10"><?php echo $description; ?></textarea>
            <?php else: ?>
            <input type="hidden" name="description" value="<?php echo $description; ?>" />
            <?php endif; ?>
            <button class="elp-button"><?php echo $button_text; ?></button>
        </form>
        <?php
    }
    
//    private function htmlQr() {
//        extract( $this->template_atts );
//        
//        if(empty($qr_amount)) {
//            throw new ELiq_Pay_Exception(__('No set amount for donation', ELIQPAY_TEXTDOMAIN));
//        }
//        
//        list($amount, $currency) = ELiq_Pay::parseAmountString($qr_amount);
//        
//        try {
//            $requst_attr = array(
//                'action' => 'payqr',
//                'description' => $description,
//                'result_url' => $result_url,
//                'currency' => $currency,
//                'amount' => (string) $amount
//            );
//            
//            $qrCode = ELiq_Pay::request(function(ELiq_Pay_Request $request) use ($requst_attr) {
//                $request->set_data($requst_attr);
//            });
//            
//        } catch (ELiq_Pay_Exception $ex) {
//            return '';
//        }
//    }
    
    private function htmlPredefined() {
        extract( $this->template_atts );

        if(empty($predefined_amount)) {
            throw new ELiq_Pay_Exception(__('No set amount for donation', ELIQPAY_TEXTDOMAIN));
        }
        
        $predefined_amount = explode(';', $predefined_amount);
        
        echo '<div class="donate-predefined-values" data-description="'.htmlspecialchars($description).'" data-result-url="'.$result_url.'" data-language="'.$language.'">';
        foreach($predefined_amount as $amount_with_currency) {
            list($amount, $currency) = ELiq_Pay::parseAmountString($amount_with_currency);
            printf('<span class="donate-value" data-amount="%2$s" data-currency="%3$s">%1$s</span>', $amount_with_currency, $amount, $currency);
        }
        
        echo '</div>';
    }
}
