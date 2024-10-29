<?php 
if(!class_exists('ACOWCS_Shipping')){ 
    class ACOWCS_Shipping{

        /**
         * @var     string
         * @access  private
         */
        private $method_settings_name; 

        /**
         * @var     string
         * @access  private
         * 
         */
        private $currency;


        public function __construct($method_id, $instance_id, $currency)
        {
            $this->method_settings_name = 'woocommerce_'.$method_id.'_'.$instance_id.'_settings';
            $this->currency = $currency;
        }   

        /**
         * @access  public
         * @return value
         */
        public function get_value(){
            $settings = get_option( $this->method_settings_name, array() );
            $currency_value = isset($settings['acowcs_fixed_' . $this->currency]) ? $settings['acowcs_fixed_' . $this->currency] : false;
            return $currency_value;
        }
    }
}