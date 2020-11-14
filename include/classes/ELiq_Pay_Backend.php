<?php

class ELiq_Pay_Backend {
    const CAPABILITY = 'manage_options';
    static private $instance = null;
    
    private $page = null;
    
    private $sections = array();
    private $section = null;
    private $option_page = null;
    
    private $option_name = '';
    
    private $subpage_store = [];
    private $subpages_relation = [];
    
    private function __construct() {
        require_once ELIQPAY_CLASSES_PATH .'/ELiq_Pay_Field.php';
        
        add_action( 'admin_menu', array( $this, '_settingPage' ) );
    }
    
    static public function init() {
        return self::getInstance();
    }
    
    static public function getInstance() {
        if(null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    static public function getFieldName(string $name) {
        return sprintf('%1$s[%2$s]', self::getInstance()->option_name, $name);
    }
    
    static public function page(string $page_id, string $title) {
        $instance = self::getInstance();
        
        $instance->option_page = $page_id;
        $instance->option_name = ELiq_Pay::OPTION_PREFIX.'_'.$page_id;
        if(!isset($instance->subpage_store[$page_id])) {
            if(empty($title)) {
                throw new ELiq_Pay_Exception(__('Not pass title for page', ELIQPAY_TEXTDOMAIN));
            }
            
            add_option($instance->option_name, array());
            
            $instance->subpage_store[$page_id] = $title;
            
            register_setting( $instance->settingId(), $instance->option_name );
            
            ELiq_Pay::getInstance()->setOptionPage($page_id);
        }
        
        return self::$instance;
    }
    
    public function section(string $title, array $fields, string $help_text = null) {        
        if(!$this->setSection($title)) {
            $this->registerSection($title, $help_text);
        }
        
        $this->fields($fields);
        
        return $this;
    }
    
    public function fields(array $fields) {
        $section = !empty($this->section) ? $this->section : 'default';
        
        foreach($fields as $field) {
            $field->registerFor($this->settingId(), $section);
        }
        
        return $this;
    }
    
    public function _sectionRender($args) {
        if(isset($this->sections[$args['id']])) {
            list($title, $description) = $this->sections[$args['id']];
            echo '<p class="section-description">'.$description.'</p>';
        }
    }
    
    public function _settingPage() {
        do_action( 'eliqpay.setting.init', $this );
        
        if(null !== $this->page) {
            throw new ELiq_Pay_Exception(__('Setting page already exists', ELIQPAY_TEXTDOMAIN));
        }
        
        $this->option_page = 'general';
        
        $eLiqPay = ELiq_Pay::getInstance();
        
        $eLiqPay->setOptionPage($this->option_page);
        
        $this->page = add_menu_page(
            __( 'LiqPay Setting', ELIQPAY_TEXTDOMAIN ),
            __( 'LiqPay', ELIQPAY_TEXTDOMAIN ),
            self::CAPABILITY,
            'eliqpay-settings',
            array($this, '_settingsPage'),
            plugins_url('easyliqpay/img/menu-icon.png' ),
            85
        );
        
        $this->option_name = $eLiqPay->getPageOptionName();
        
        register_setting( $this->settingId(), $this->option_name );        
        
        $this->add_general_fields();
        
        if(false === get_option($this->option_name)) {
            add_option($this->option_name, array());
        }
        
        if(!empty($this->subpage_store)) {
            #Make submenu for rename to General
            add_submenu_page(
                'eliqpay-settings',
                __('General', ELIQPAY_TEXTDOMAIN),
                __('General', ELIQPAY_TEXTDOMAIN),
                self::CAPABILITY,
                'eliqpay-settings',
                array($this, '_settingsPage')
            );
            
            foreach($this->subpage_store as $_id => $_title) {
                $this->subpages_relation[$_id] = add_submenu_page(
                    'eliqpay-settings',
                    $_title,
                    $_title,
                    self::CAPABILITY,
                    'eliqpay-'.$_id,
                    array($this, '_settingsPage')
                );
            }
        }
    }
    
    public function _settingsPage() {
        $title = __( 'LiqPay Setting', ELIQPAY_TEXTDOMAIN );
        $current_screen = get_current_screen();
        
        if($page_id = array_search($current_screen->id, $this->subpages_relation)) {
            $this->option_page = $page_id;
            $title = $this->subpage_store[$page_id];
        }
        ?>
        <div class="wrap">
            <h2><?php echo $title; ?></h2>
            <form method="POST" action="options.php">
            <?php settings_fields( $this->settingId() );
            
            $this->print_nonsection_fields();
            
            do_settings_sections( $this->settingId() );
            
            submit_button();
            ?>
            </form>
        </div>
        <?php
    }
    
    private function print_nonsection_fields() {
        ?>
        <table class="form-table">
            <?php do_settings_fields( $this->settingId(), 'default' ); ?>
        </table>
        <?php
    }
    
    private function add_general_fields() {
        $liqpay_lang_options = eliqpay_language_list();
        
        $this->fields(array(
            ELiq_Pay_Field::text('public_key', __('Public key', ELIQPAY_TEXTDOMAIN)),
            ELiq_Pay_Field::text('private_key', __('Private key', ELIQPAY_TEXTDOMAIN), array('row_class' => 'row-class', 'class' => 'field-class', 'data-extra'=> 'data')),
            ELiq_Pay_Field::checkbox('currency', __('Currency', ELIQPAY_TEXTDOMAIN), ELiq_Pay_Request::CURRENCIES, array()),
            ELiq_Pay_Field::select('language', __('Language', ELIQPAY_TEXTDOMAIN), $liqpay_lang_options),
            ELiq_Pay_Field::textarea('payment_description', __('Default Description', ELIQPAY_TEXTDOMAIN))
        ));
    }
    
    private function settingId() {
        return 'eliqpay_settings_'.$this->option_page;
    }
    
    private function registerSection($title, $help_text = null) {
        $this->section = $this->sectionId($title);
        
        $this->sections[$this->section] = array($title, $help_text);
        
        add_settings_section($this->section, $title, array($this, '_sectionRender'), $this->settingId());
    }
    
    private function setSection($title) {
        foreach($this->sections as $section_id => $section_data) {
            list($section_title, $section_description) = $section_data;
            if($section_title === $title) {
                $this->section = $section_id;
                
                break;
            }
        }
        
        return (bool) $this->section;
    }
    
    private function sectionId(string $section_title) {
        return md5($section_title);
    }
}
