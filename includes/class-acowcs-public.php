<?php

if (!defined('ABSPATH')) {
    exit;
}

class ACOWCS_Public
{
    /**
     * @var     string
     * @access  private
     */
    private $get_client_ip = '';

    /**
     * @var     string
     * @access  private
     */
    private $version_type = 'free';


    /**
     * @var     string
     * @access  private
     */
    private $currency_storage_to = '';
    /**
     * @var     array
     * @access  protected
     * 
     */
    protected $user_roles;

    /**
     * @var     bullian
     * @access  protected
     */
    protected $is_fixed_product_price = false;
    /**
     * @var     bullian
     * @access  protected
     */
    protected $is_fixed_user_role = false;

    /**
     * @var     class
     * @access  private
     */
    protected $fixed_amount;

    /**
     * @var     class
     * @access  protected
     * 
     */
    protected $fixed_user_role;

    /**
     * @var     bullian
     * @access  public
     */
    public $is_multiple_allowed = true;

    /**
     * @var     string
     * @access  public
     */
    public $thousands_sep = ',';

    /**
     * @var     string
     * @access  private
     */
    private $visitor_default_currency = 'USD';
    
    /**
     * @var     array
     * @access  private
     */
    private $acowcs_settings = array();

    /**
     * @var     string
     * @access  private
     */
    private $enable_currency_switcher = false;

    /**
     * @var     true/false 
     * @access  private
     */
    private $fixed_shipping_price = false;
    /**
     * @var     string
     * @access  private
     */
    private $current_currency_symbol;


    /**
     * @var     string
     * @access  private
     * @since   1.0.0
     */
    private $current_currency;

    /**
     * @var     string
     * @access  private
     */
    private $decimal_sep = '.';


    /**
     * @var     bulian
     * @access  private
     * @return  true / false
     */
    private $fixed_coupon_price = false;


    /**
     * @var     string
     * @access  private
     * @since   1.0.0
     */
    private $default_currency;

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $instance = null;

    /**
     * The version number.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $version;

    /**
     * The main plugin file.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The token.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $token;

    /**
     * The plugin assets URL.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /** 
     * @var     bullian
     * @access  private
    */
    private $current_user_country_code = '';


    /**
     * Constructor function.
     *
     * @access  public
     * @param string $file Plugin root file path.
     * @since   1.0.0
     */
    public function __construct($file = '')
    {
        if (acowcs_settings('currency_storage_to') == 'php_session') {
            if (!session_id()) {
                @session_start();
            }
        }
        $this->version = ACOWCS_VERSION;
        $this->token = ACOWCS_TOKEN;
        $this->version_type = ACOWCS_VERSION_TYPE;
        $this->file = $file;
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        if($this->isWoocommerceActivated() === true){
            // Load frontend CSS.
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_styles'), 10);

            // Register Widget 
            add_action( 'widgets_init', array($this, 'acowcs_currency_switcher_widget') );

            add_action('init', array($this, 'init'));
        }

    }




    /** Handle Post Typ registration all here
     */
    public function init()
    {
        $this->set_initial_config();

        if($this->enable_currency_switcher){
            add_action( 'wp_footer', array($this, 'acowcs_render_html') );
            add_filter('woocommerce_currency', array($this, 'acowcs_get_woocommerce_currency'), 9999);
            add_filter('woocommerce_currency_symbol', array($this, 'woocommerce_currency_symbol'), 9999);
            
            //woo >= v.2.7
            add_filter('woocommerce_product_get_price', array($this, 'acowcs_raw_woocommerce_price'), 9999, 2);
            add_filter('woocommerce_product_variation_get_price', array($this, 'acowcs_raw_woocommerce_price'), 9999, 2);
            add_filter('woocommerce_product_variation_get_regular_price', array($this, 'acowcs_raw_woocommerce_price'), 9999, 2);
            add_filter('woocommerce_product_variation_get_sale_price', array($this, 'acowcs_raw_sale_price_filter'), 9999, 2);
            // add_filter('woocommerce_product_get_sale_price', array($this, 'acowcs_raw_sale_price_filter'), 9999, 2);
            add_filter('woocommerce_product_get_regular_price', array($this, 'acowcs_raw_woocommerce_price'), 9999, 2);

            add_filter('woocommerce_before_mini_cart', array($this, 'acowcs_woocommerce_before_mini_cart'), 9999);

            // shipping
            add_filter('woocommerce_package_rates', array($this, 'acowcs_woocommerce_package_rates'), 9999, 2);

            // Cart update hook for cutomize shipping method based on currency 
            // add_filter( 'woocommerce_shipping_methods', array( $this, 'acowcs_filter_cart_shipping_methods'), 10, 1 );
            
            //Filter dicimal 
            add_filter('wc_get_price_decimals', array($this, 'acowcs_fix_decimals'), 999);

            //price formate
            add_filter('woocommerce_price_format', array($this, 'acowcs_woocommerce_price_format'), 9999);

            if(isset($this->acowcs_settings['converter_on_cart']) && $this->acowcs_settings['converter_on_cart'] != ''){
                add_action( 'woocommerce_cart_collaterals', array($this, 'acowcs_action_woocommerce_before_cart'), 20 );
            }
            if(isset($this->acowcs_settings['converter_on_product_page']) && $this->acowcs_settings['converter_on_product_page'] != ''){
                add_action( 'woocommerce_before_add_to_cart_form', array($this, 'acowcs_action_woocommerce_before_cart'), 10, 1 ); 
            }

            //Price info icon
            add_action('woocommerce_get_price_html', array($this, 'acowcs_woocommerce_price_html'), 1, 2);
            add_action('woocommerce_variable_sale_price_html', array($this, 'acowcs_woocommerce_price_html'), 1, 2);
            add_action('woocommerce_sale_price_html', array($this, 'acowcs_woocommerce_price_html'), 1, 2);

            // Shortcode 
            add_shortcode( 'acowcs_currency_switcher', array($this, 'acowcs_currency_switcher_shortcode_callback') );

            //Fixed coupon price 
            if($this->fixed_coupon_price && !is_admin(  )){
                add_action('woocommerce_coupon_loaded', array($this, 'acowcs_woocommerce_coupon_loaded'), 9999);
            }

            // Filter payment method based on currency
            add_filter('woocommerce_available_payment_gateways', array($this, 'acowcs_conditional_payment_gateways'), 10, 1);
        }
    }




    /**
     * @access  public
     * @return  script 
     * @perpose reload page if set default currency based on user location
     */
    public function acowcs_render_header(){
        if(isset($_COOKIE['default_currency'])){
            wp_add_inline_script( $this->token . '_switcher', $this->acowcs_inline_scripts_refresh() );
        }
    }


     /**
    * @var  function
    * @return   conditional scripts 
    */
    public function acowcs_inline_scripts_refresh(){
        ob_start(); ?>
            setTimeout(() => {
                jQuery(document.body).trigger('wc_fragment_refresh');
                document.cookie = "default_currency=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";    
            }, 500);
        <?php 
        $output = ob_get_clean();
        return $output;
    }
    /**
     * @param   available payment gateway 
     * @return  customized payment getway as array
     * @desc    customize payment getway based on currency switcher condition
     * 
     */
    public function acowcs_conditional_payment_gateways($available_gateways ){
        $newGetway = array();
        $thisCurrency_key = isset($this->acowcs_settings['payment_settings']) && is_array($this->acowcs_settings['payment_settings']) ? array_search( $this->current_currency['currency'], array_column($this->acowcs_settings['payment_settings'], 'paysettings_currencies')) : false;
        
        if($thisCurrency_key !== false){
            foreach($available_gateways as $k => $sPaymentMehtod){
                $status = $this->acowcs_conditional_payment_methods($this->acowcs_settings['payment_settings'][$thisCurrency_key], $k);
                if($status === false){
                    unset($available_gateways[$k]);
                }
            }   
        }
        return $available_gateways;
    }




    /**
     * Check if woocommerce is activated
     *
     * @access  public
     * @return  boolean woocommerce install status
     */
    public function isWoocommerceActivated()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return true;
        }
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins['woocommerce/woocommerce.php'])) {
                return true;
            }
        }
        return false;
    }


    /**
     * @var     function 
     * @access  public
     * @return  max / min coupon value
     */
    public function acowcs_exchange_value($value) {
        if(isset($this->current_currency['rate'])) 
            $value = floatval($value) * floatval($this->current_currency['rate']);

        $precision = isset($this->current_currency['decimal']) && $this->current_currency['decimal'] != '' ? $this->current_currency['decimal'] : get_option( 'woocommerce_price_num_decimals', 2 );
        $value = number_format(floatval($value), $precision, $this->decimal_sep, '');
        return $value;
    }


    /**
     * @var     function 
     * @access  public
     * @return  fixed coupon value
     */
    public function acowcs_woocommerce_coupon_loaded($coupon) {

        if ($this->current_currency['currency'] == $this->default_currency) {
            return $coupon;
        }
        $convert = false;
        $prices = array();
        $count_id = $coupon->get_id();
        
        $prices['amount'] = $coupon->get_amount();
        $prices['min_spend'] = $coupon->get_minimum_amount();
        $prices['max_spend'] = $coupon->get_maximum_amount();
        if (!$coupon->is_type('percent_product') && !$coupon->is_type('percent')) {
            $convert = true;
        }
     

        //convert
        foreach ($prices as $key => $val) {
            if ('amount' != $key) {
                $prices[$key] = $this->acowcs_exchange_value($val);
            }
            
            $tmp_amount = $this->fixed_amount->get_coupon_value($count_id, $this->current_currency['currency']);
            $tmp_amount = floatval($tmp_amount);

            if ((int) $tmp_amount !== -1 && $key == 'amount' ) {
                $prices[$key] = $tmp_amount;
            }
            
            if ((float) $prices[$key] === 0.0) {
                $prices[$key] == "";
            }
        }
        
        $coupon->set_minimum_amount($prices['min_spend']);
        $coupon->set_maximum_amount($prices['max_spend']);
        $coupon->set_amount($prices['amount']);

        return $coupon;
    }



    /**
     * @var     function 
     * @return  acowcs widget
     * @access  public
     */
    public function acowcs_currency_switcher_widget(){
        register_widget('ACOWCS_Widget');
    }


    /**
     * @var     function 
     * @access  public
     * @return  currency switcher shortcode
     */
    public function acowcs_currency_switcher_shortcode_callback($args){
        

        if(is_checkout() && isset($this->acowcs_settings['hide_swither_on_checkout']) && $this->acowcs_settings['hide_swither_on_checkout'] != '')
            return false;
    

        if(is_user_logged_in(  ) && isset($this->acowcs_settings['show_switcher_on_userrole']) && is_array($this->acowcs_settings['show_switcher_on_userrole']) && is_array( $this->user_roles ) ){
            $swithcer_on_user_role_status = isset($this->acowcs_settings['swithcer_on_user_role_status']) ? $this->acowcs_settings['swithcer_on_user_role_status'] : 'include';
            $show_switcher_on_userrole = array_map(function($s){
                return $s['name'];
            }, $this->acowcs_settings['show_switcher_on_userrole']);

            $role_exists =  !empty(array_intersect($this->user_roles, $show_switcher_on_userrole));

            if(($swithcer_on_user_role_status == 'include' && !$role_exists) || ($swithcer_on_user_role_status == 'exclude' && $role_exists) )
                return false;
        }

    
        if(isset($this->acowcs_settings['show_switcher_page']) && is_array($this->acowcs_settings['show_switcher_page'])){
            $status = isset($this->acowcs_settings['show_switcher_status']) && $this->acowcs_settings['show_switcher_status'] != '' ? $this->acowcs_settings['show_switcher_status'] : 'include'; 
            $pages = array_map(function($v){
                return $v['id'];
            }, $this->acowcs_settings['show_switcher_page']); 


            // show_switcher_status
            global $wp_query, $post;
            if(is_shop()){
                if($status == 'include' && !in_array( get_option( 'woocommerce_shop_page_id' ), $pages ) && count($pages) > 0)
                    return false;
                
                if($status == 'exclude' && in_array( get_option( 'woocommerce_shop_page_id' ), $pages ))
                    return false;
                
            }else{
                if($status == 'include' && !in_array($wp_query->get_queried_object_id(), $pages) && count($pages) > 0)
                    return false;
                
                if($status == 'exclude' && in_array($wp_query->get_queried_object_id(), $pages))
                    return false;
            }
        }

        // CSS 
        wp_enqueue_style( $this->token . '-widgetCSS' );

        // Add JS
        wp_enqueue_script( $this->token . '_switcher' );
        wp_localize_script(
            $this->token . '_switcher',
            $this->token . '_object',
            array(
                'api_nonce' => wp_create_nonce('wp_rest'),
                'root' => rest_url($this->token . '/v1/'),
                'assets_url' => $this->assets_url,
                'user_id' => is_user_logged_in(  ) ? get_current_user_id(  ) : false
            )
        );

        ob_start();
            include ACOWCS_PATH . 'view/shortcode.php';
            $output = ob_get_clean();
        return $output;
    }


    /**
     * @return  price formate filter
     * @access  public
     */
    public function acowcs_woocommerce_price_format($pos = false, $loop = false){
        $currency_pos = 'left';
        $admin_order_page = false;

        if(is_checkout()){
            if(!isset($this->acowcs_settings['checkout_based_on_currency']) || $this->acowcs_settings['checkout_based_on_currency'] == '')
                return apply_filters('acowcs_woocs_price_format', $pos, $loop);
        }

        if(is_admin()){
            $screen = function_exists('get_current_screen') ? get_current_screen() : false;
            if(isset($screen->post_type) && $screen->post_type == 'shop_order'){
                global $post;
                $order = wc_get_order( $post->ID );
                $currency = $order->get_currency();

                $curriencies = isset($this->acowcs_settings['curriencies']) ? $this->acowcs_settings['curriencies'] : array();
                $current_currency_key = array_search($currency, array_column($curriencies, 'currency'));
                if($current_currency_key !== false && isset($curriencies[$current_currency_key]['position']) && $curriencies[$current_currency_key]['position'] != ''){
                    $currency_pos = $curriencies[$current_currency_key]['position'];
                    $admin_order_page = true;
                }
            }
        }

        
        if($loop)
            $currency_pos = $pos;
        
        if ($admin_order_page === false && !$loop && isset($this->current_currency['position']) && $this->current_currency['position'] != '') {
            $currency_pos = $this->current_currency['position'];
        }
        
        $format = '%1$s%2$s';
        switch ($currency_pos) {
            case 'left' :
                $format = '%1$s%2$s';
                break;
            case 'right' :
                $format = '%2$s%1$s';
                break;
            case 'left_space' :
                $format = '%1$s&nbsp;%2$s';
                break;
            case 'right_space' :
                $format = '%2$s&nbsp;%1$s';
                break;
        }

        return apply_filters('acowcs_woocs_price_format', $format, $currency_pos);
    }


    
    /**
     * @param   price 
     * @return  customize price
     * @access  public
     */
    public function wc_price($price, $convert = true, $args = array(), $product = NULL, $decimals = -1) {
        extract(apply_filters('wc_price_args', wp_parse_args($args, array(
            'ex_tax_label' => false,
            'currency' => '',
            'symbol' => '',
            'decimal_separator' => $this->decimal_sep,
            'thousand_separator' => $this->thousands_sep,
            'decimals' => $decimals,
            'price_format' => $this->acowcs_woocommerce_price_format(isset($args['position']) ? $args['position'] : false, $loop = true)
        ))));


        if ($decimals < 0) {
            $decimals = $this->get_currency_price_num_decimals($currency, $this->price_num_decimals);
        }

        $currencies = $this->acowcs_settings['curriencies'];
        if (isset($currencies[$currency])/* AND !isset($_REQUEST['woocs_show_custom_price']) */) {
            if ($currencies[$currency]['hide_cents']) {
                $decimals = 0;
            }
        }


        $negative = $price < 0;
        $special_convert = false;
        $is_price_custom = false;
        try {
            if ($product !== NULL && is_object($product) && $convert){
                $product_id = $product->get_id();
                //***
                if ($this->is_multiple_allowed) {
                    if ($this->is_fixed_product_price) {
                        //$type = $this->fixed->get_price_type($product, $price);
                        if (isset($this->current_currency['rate']) && $this->current_currency['rate'] != '') {
                            $special_convert = true;
                            $is_price_custom = true;
                            
                            $regular_price = $this->fixed_amount->get_value($product_id, $currency, 'sale');
                            if(!$regular_price)
                                $regular_price = $this->fixed_amount->get_value($product_id, $currency, 'regular');  
                                if(!$regular_price)
                                    $regular_price = get_post_meta( $product_id, '_sale_price', true );
                                    if(!$regular_price)
                                        $regular_price = get_post_meta( $product_id, '_regular_price', true );     

                            if (floatval($regular_price) > 0) {
                                $price = floatval($regular_price);
                                if (wc_tax_enabled()) {
                                    $price = $this->acowcs_calc_tax_price($product, $price);
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            
        }

        
        if ($this->is_fixed_user_role && !is_null($product) && $convert){    
            $roles = $this->user_roles;
            $product_id = $product->get_id();
            
            if($this->default_currency == $currency ){
                $price = get_post_meta( $product_id, '_sale_price', true ) ? get_post_meta( $product_id, '_sale_price', true ) : get_post_meta( $product_id, '_regular_price', true );
            }else{
                $price = $this->fixed_user_role->get_value($product_id, $currency, $roles, 'sale');
                if(!$price)
                    $price = $this->fixed_user_role->get_value($product_id, $currency, $roles, 'regular');  
                    if(!$price)
                        $price = get_post_meta( $product_id, '_sale_price', true );
                        if(!$price)
                            $regular_price = get_post_meta( $product_id, '_regular_price', true );     
            }
            
            if (floatval($price) > 0 && $this->default_currency != $currency) {
                $price = floatval($price);
                $price = $this->acowcs_raw_woocommerce_price(floatval($negative ? $price * -1 : $price));
                if (wc_tax_enabled()) {
                    $price = $this->acowcs_calc_tax_price($product, $price);
                }
            }
        }

        

        //***
        $unformatted_price = 0;
        if ($convert  && !$is_price_custom) {
            $price = $this->acowcs_raw_woocommerce_price(floatval($negative ? $price * -1 : $price));
            $unformatted_price = $price;
        }

        
        //***
        $price = apply_filters('formatted_woocommerce_price', number_format($price, $decimals, $decimal_separator, $thousand_separator), $price, $decimals, $decimal_separator, $thousand_separator);
        
        

        if (apply_filters('woocommerce_price_trim_zeros', false) && $decimals > 0) {
            $price = wc_trim_zeros($price);
        }

        $formatted_price = ( $negative ? '-' : '' ) . sprintf($price_format, isset($symbol) ? $symbol : '', $price);
        update_option( '_test', array(
            'price_formate' => $price_format, 
            'formated_price' => $formatted_price
        ) ); 

        $return = '<span class="woocs_amount">' . $formatted_price . '</span>';

        if ($ex_tax_label && wc_tax_enabled()) {
            $return .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
        }


        return apply_filters('acowcs_wc_price', $return, $price, $args, $unformatted_price);
    }


    /**
     * @var     funciton 
     * @access  private 
     * @return  variation price min and max
     */
    private function _get_min_max_variation_prices($product, $current_currency) {
        
        $prices_array = $product->get_variation_prices();
        $var_products_ids = array_keys($prices_array['regular_price']);

        
        $prices_array = array();
        if (!empty($var_products_ids)) {
            foreach ($var_products_ids as $var_prod_id) {

                $is_price_custom = false;
                $regular_price = (float) get_post_meta($var_prod_id, '_regular_price', true);
                $sale_price = (float) get_post_meta($var_prod_id, '_sale_price', true);


                if ($this->is_fixed_product_price) {
                    $type = 'regular';
                    $fixed_regular_price = -1;
                    $fixed_sale_price = -1;

                    $fixed_price = $this->fixed_amount->get_value($var_prod_id, $current_currency, 'sale');
                    if(!$fixed_price)
                        $fixed_price = $this->fixed_amount->get_value($var_prod_id, $current_currency, 'regular');

                    
                    if ($fixed_price) {
                        $prices_array[] = $fixed_price;
                        $is_price_custom = true;
                    } 
                }


                if ($this->is_fixed_user_role) {
                    $roles = $this->user_roles;
                    
                    $price = $this->fixed_user_role->get_value($var_prod_id, $current_currency, $roles, 'sale');
                    if(!$price)
                        $price = $this->fixed_user_role->get_value($var_prod_id, $current_currency, $roles, 'regular'); 

                    $price = floatval($price);
                }
            }
        }


        if (!empty($prices_array)) {
            foreach ($prices_array as $key => $value) {
                if (floatval($value) <= 0) {
                    unset($prices_array[$key]);
                }
            }

            if (!empty($prices_array)) {
                return apply_filters( 'acowcs_veriation_min_max_price', array('min' => min($prices_array), 'max' => max($prices_array)) );
            }
        }

        return array();
    }




    /**
     * @access  public
     * @var     function
     * @desc    get taxable price
     */
    public function acowcs_calc_tax_price($product, $price) {
        if ($product && $product->is_taxable()) {
            return wc_get_price_to_display($product, array("qty" => 1, "price" => $price));
        } else {
            return $price;
        }
    }


    /**
     * @var     public 
     * @param   price html & product
     * @return  customized price html
     */
    public function acowcs_woocommerce_price_html($price_html, $product){
        $currencies = $this->acowcs_settings['curriencies'];
        $product_id = $product->get_id();
        //hide cents on front as html element
        if (isset($this->current_currency['hide_cent']) && $this->current_currency['hide_cent'] != '') {
            $sep = wc_get_price_decimal_separator();
            
            $num_dicimal = isset($this->current_currency['decimal']) && $this->current_currency['decimal'] != '' ? $this->current_currency['decimal'] : get_option( 'woocommerce_price_num_decimals', 2 );
            $zeros = str_repeat('[0-9]', $num_dicimal);
            
            if ( $this->current_currency['hide_cent'] == 1 ) {
                $price_html = preg_replace("/\\{$sep}{$zeros}/", '', $price_html);
            }
        }


        $allow_info_icon = false;
        if(!is_product() && isset($this->acowcs_settings['show_price_info_icon']) && $this->acowcs_settings['show_price_info_icon'] != '' && !is_admin(  ))
            $allow_info_icon = true; 
        
        if(is_product() && isset($this->acowcs_settings['show_price_info_icon_on_single']) && $this->acowcs_settings['show_price_info_icon_on_single'] != '' && !is_admin(  )){
            $primari_id         = get_queried_object_id();
            $Mainproduct        = wc_get_product( $primari_id );
            $children           = 'grouped' == $Mainproduct->get_type() ? $Mainproduct->get_children() : array();

            if(!in_array($product_id, $children))
                $allow_info_icon    = true; 
        }
            
        
        //add additional info in price html
        if($allow_info_icon === true){
            $info = "<ul class='acowcs_price_info_list'>";
            $current_currency = isset($this->current_currency['currency']) ? $this->current_currency['currency'] : false;

            $symbolLists = ACOWCS_Helper()->wcowcs_get_symbols_set();
            $symbolLists = array_map(function($v){
                $filter = preg_replace("/\([^)]+\)/","", $v); // 'ABC '
                return html_entity_decode($filter);
            }, $symbolLists);
            

            foreach ($currencies as $curr) {
                
                if(isset($curr['currency']) && $current_currency && $curr['currency'] == $current_currency) {
                    continue;
                }
                if(!isset($curr['currency'])){
                    continue;
                }
                if(!isset($curr['rate'])){
                    continue;
                }
                

                $value = (float) $product->get_price('edit') * (float) $curr['rate'];

                $precision = isset($curr['decimal']) && $curr['decimal'] != '' ? $curr['decimal'] : get_option( 'woocommerce_price_num_decimals', 2 );
                $value = number_format($value, $precision, $this->decimal_sep, '');


                $product_type = '';
                $product_type = $product->get_type();

                if ($product_type == 'variable') {
                    $min_value = $product->get_variation_price('min', true) * $curr['rate'];
                    $max_value = $product->get_variation_price('max', true) * $curr['rate'];

                    
                    $min_max_values = $this->_get_min_max_variation_prices($product, $curr['currency']);
                    if (!empty($min_max_values)) {

                        $min_value = $min_max_values['min'] /* $currencies[$сurr['name']]['rate'] */;
                        $max_value = $min_max_values['max'] /* $currencies[$сurr['name']]['rate'] */;
                    }
                    if (wc_tax_enabled()) {
                        $min_value = $this->acowcs_calc_tax_price($product, $min_value);
                        $max_value = $this->acowcs_calc_tax_price($product, $max_value);
                    }


                    $var_price = "";
                    if(isset($curr['symbol']) && isset($symbolLists[$curr['symbol']])){
                        $curr['symbol'] = $symbolLists[$curr['symbol']];
                    }
                    $var_price1 = $this->wc_price($min_value, false, $curr, $product, $precision);
                    $var_price2 = $this->wc_price($max_value, false, $curr, $product, $precision);
                    if ($var_price1 == $var_price2) {
                        $var_price = $var_price1;
                    } else {
                        $var_price = sprintf("%s - %s", $var_price1, $var_price2);
                    }

                    
                    $info .= "<li><b>" . $curr['currency'] . "</b>: " . $var_price . "</li>";
                } elseif ($product_type == 'grouped') {

                    $child_ids = $product->get_children();
                    $prices = array();
                    foreach ($child_ids as $prod_id) {
                        $product1 = wc_get_product($prod_id);
                        if (!$product1 OR!is_object($product1)) {
                            continue;
                        }
                        $product_type1 = $product1->get_type();
                        if ($product_type1 == 'variable') {

                            $min_value = $product1->get_variation_price('min', true) * $currencies[$сurr['name']]['rate'];
                            $max_value = $product1->get_variation_price('max', true) * $currencies[$сurr['name']]['rate'];
                            //***
                            $min_max_values = $this->_get_min_max_variation_prices($product1, $сurr['name']);
                            if (!empty($min_max_values)) {

                                $min_value = $min_max_values['min'] /* $currencies[$сurr['name']]['rate'] */;
                                $max_value = $min_max_values['max'] /* $currencies[$сurr['name']]['rate'] */;
                            }
                            if (wc_tax_enabled()) {
                                $prices[] = $this->acowcs_calc_tax_price($product1, $min_value);
                                $prices[] = $this->acowcs_calc_tax_price($product1, $max_value);
                            } else {
                                $prices[] = $min_value;
                                $prices[] = $max_value;
                            }
                        } else {

                            if ($this->is_fixed_product_price && $this->is_multiple_allowed) {
                                    $special_convert = true;
                                    $is_price_custom = true;
                                    $price_tem = $this->fixed_amount->get_value($prod_id, $curr['currency'], 'sale');
                                    if(!$price_tem)
                                        $price_tem = $this->fixed_amount->get_value($prod_id, $curr['currency'], 'regular');

                                    if(!$price_tem){
                                        $price_tem = get_post_meta( $prod_id, '_sale_price', true );
                                        if(!$price_tem)
                                            $price_tem = get_post_meta( $prod_id, '_regular_price', true );   

                                    $curr['decimal'] = isset($curr['decimal']) && $curr['decimal'] != '' ? $curr['decimal'] : get_option( 'woocommerce_price_num_decimals', 2 );
                                    $price_tem = number_format(floatval((float) $price_tem * (float)$curr['rate']), 0, $curr['decimal'], '');         
                                    }
                                    

                                    if ($price_tem && floatval($price_tem) > 0) {    
                                        if (wc_tax_enabled()) {
                                            $prices[] = $this->acowcs_calc_tax_price($product1, floatval($this->fixed->get_value($prod_id, $сurr['name'], $type)));
                                        } else {
                                            $prices[] = floatval( $price_tem );
                                        }
                                    }
                                    
                            } else {
                                if (wc_tax_enabled()) {
                                    $prices[] = $this->acowcs_calc_tax_price($product1, $product1->get_price('edit') * $curr['rate']);
                                } else {
                                    $prices[] = $product1->get_price('edit') * $curr['rate'];
                                }
                            }
                        }
                    }
                    asort($prices);
                    
                    $var_price = "";
                    
                    if(isset($curr['symbol']) && isset($symbolLists[$curr['symbol']])){
                        $curr['symbol'] = $symbolLists[$curr['symbol']];
                    }
                    
                    $var_price1 = $this->wc_price(array_shift($prices), false, $curr, $product, $precision);
                    $var_price2 = $this->wc_price(array_pop($prices), false, $curr, $product, $precision);

                    
                    if ($var_price1 == $var_price2) {
                        $var_price = $var_price1;
                    } else {
                        $var_price = sprintf("%s - %s", $var_price1, $var_price2);
                    }
                    $info .= "<li><b>" . $curr['currency'] . "</b>: " . $var_price . "</li>";

                } else {
                    if (wc_tax_enabled()) {
                        $value = $this->acowcs_calc_tax_price($product, $value);
                    }

                    
                    if(isset($curr['symbol']) && isset($symbolLists[$curr['symbol']])){
                        $curr['symbol'] = $symbolLists[$curr['symbol']];
                    }
                    
                    $info .= "<li><span>" . $curr['currency'] . "</span>: " . $this->wc_price($value, false, $curr, $product, $precision) . "</li>";
                }
            }
            
            $info .= "</ul>";
            $info = '<div class="acowcs_price_info"><span class="acowcs_price_info_icon"></span>' . $info . '</div>';
            $add_icon = strripos($price_html, $info);
            if ($add_icon === false) {
                $price_html .= $info;
            }
        }
        return apply_filters( 'acowcs_filtered_html_price', $price_html );
    }




    /**
     * @var     function 
     * @access  public
     * @desc    Visitor default currency if allow from settings
     */
    public function acowcs_public_init(){
        if(isset($this->acowcs_settings['default_currency_based_on_location']) && $this->acowcs_settings['default_currency_based_on_location'] != '' && !isset($_COOKIE['local_currency'])){        
            $geoDetails = ACOWCS_Helper()->acowcs_geo_details();
            $geo_currency =  isset($geoDetails->currency) ? $geoDetails->currency : get_woocommerce_currency();
            $visitor_default_currency = esc_attr( $geo_currency );
            $this->visitor_default_currency = $visitor_default_currency;
            set_transient( $this->get_client_ip . '_acowcs_currency', $visitor_default_currency );  
            setcookie('default_currency', $visitor_default_currency, time() + 86400, "/"); // 86400 = 1 day
            setcookie('local_currency', $visitor_default_currency, time() + 86400, "/"); // 86400 = 1 day
            $_SESSION[$this->get_client_ip . '_acowcs_currency'] = $visitor_default_currency;
        }
        
    }





    /**
     * @var     function 
     * @access  private
     * @desc    Acowcs Currency Calculator
     */
    public function acowcs_action_woocommerce_before_cart($wccm_before_checkout ){
        wp_enqueue_style( $this->token . '-calculatorCSS' );
        wp_enqueue_style( $this->token . '-calculatorSelect2CSS' );
        
        wp_enqueue_script( $this->token . '_calculatorJS' );
        wp_enqueue_script( $this->token . '_calculatorSelect2' );
        require_once(ACOWCS_PATH . 'view/calculator.php');   
    }



    /**
     * @access  private
     * @filter wc currency decimal
     * @return decimal
     */
    public function acowcs_fix_decimals($decimal){
        if(isset($this->current_currency['decimal']) && $this->current_currency['decimal'] != ''){
            return apply_filters( 'acowcs_decimal', $this->current_currency['decimal'] );
        }
        return $decimal;
    }




/**
     * @var     private function 
     * @return  true / false
     * @desc    check is method status active or not for current currecy
     */
    private function acowcs_conditional_payment_methods($currency_array = array(), $method_id = ''){

        if(!isset($currency_array['payment_method_for_currencis']))
            return true;
        
        $payment_method_for_currencis = array_map(function($v){
                return $v['method_id'];
        }, $currency_array['payment_method_for_currencis']); 
        
        $paysettings_status = isset($currency_array['paysettings_status']) && $currency_array['paysettings_status'] != '' ? $currency_array['paysettings_status'] : 'enable';
        
        if($paysettings_status == 'enable' && !in_array($method_id, $payment_method_for_currencis)){
            return false;
        }
        if($paysettings_status == 'disable' && in_array($method_id, $payment_method_for_currencis)){
            return false;
        }

        return true;
    }




    /**
     * @var     private function 
     * @return  true / false
     * @desc    check is method status active or not for current currecy
     */
    private function acowcs_conditional_shipping_methods($currency_array = array(), $method_id = ''){
        

        $shipping_method_for_currencis = array_map(function($v){
            return $v['method_id'];
        }, $currency_array['shipping_method_for_currencis']);
        

        $paysettings_status = isset($currency_array['shepping_settings_status']) && $currency_array['shepping_settings_status'] != '' ? $currency_array['shepping_settings_status'] : 'enable';
        if($paysettings_status == 'enable' && !in_array($method_id, $shipping_method_for_currencis)){
            return false;
        }

        if($paysettings_status == 'disable' && in_array($method_id, $shipping_method_for_currencis)){
            return false;
        }

        return true;
    }



    public function acowcs_filter_cart_shipping_methods($methods){
        //Shipping method enable / deable based on currency  shipping_method_for_currencis shipping_method_for_currencis
        $thisCurrency_key = isset($this->acowcs_settings['shipping_settings']) && is_array($this->acowcs_settings['shipping_settings']) ? array_search($this->current_currency['currency'], array_column($this->acowcs_settings['shipping_settings'], 'shepping_settings_currencies')) : false;

        if($thisCurrency_key !== false){
            foreach($methods as $k => $s){
                $status = $this->acowcs_conditional_shipping_methods($this->acowcs_settings['shipping_settings'][$thisCurrency_key], $k);
                if($status === false){
                    unset($methods[$k]);
                }   
            }
        }
        return $methods;
    }

    /**
     * @var     function 
     * @access   public 
     * @desc    change woocommerce package rate based on currency settings
     */
    public function acowcs_woocommerce_package_rates($rates, $package) {
            // if ($this->current_currency['currency'] != $this->default_currency) {
                $newRates = array();
                // $currencies = $this->get_currencies();
                foreach ($rates as $rate_id => $rate) {
                    
                    $min_index = false;
                    $max_index = false;

                    if(isset($this->acowcs_settings['payment_shipping_costs'])){
                        $keys = array_keys(array_column($this->acowcs_settings['payment_shipping_costs'], 'shipping_method'), $rate->method_id);
 

                        $payment_shipping_costs = $this->acowcs_settings['payment_shipping_costs'];
                        
                        $payment_shipping_costs = array_map(function($k) use ($payment_shipping_costs){
                            return $payment_shipping_costs[$k];
                        }, $keys);

                        

                        $country_keys = array_keys(array_column($payment_shipping_costs, 'currency'), $this->current_currency['currency']);
                        $country_min_max = array_map(function($v) use ($payment_shipping_costs){
                            return $payment_shipping_costs[$v];
                        }, $country_keys);

                        $min_index = array_search('min', array_column($country_min_max, 'status'));
                        $max_index = array_search('max', array_column($country_min_max, 'status'));
                    }


                    $this->current_currency['rate'] = isset($this->current_currency['rate']) ? $this->current_currency['rate'] : 1;
                    $value = $rate->cost * $this->current_currency['rate'];
                    
                    $shipping_value = false;
                    // if ($this->fixed_shipping_price) {//is fixed shipping cost
                        $shipping = new ACOWCS_Shipping($rate->method_id, $rate->instance_id, $this->current_currency['currency']);
                        $shipping_value = $shipping->get_value();
                        
                        if($shipping_value) 
                            $value = $shipping_value;
                        
                        if(isset($country_min_max[$min_index]) || isset($country_min_max[$max_index]) ){
                            
                            if($max_index !== false && floatval($value) > floatval($country_min_max[$max_index]['cost'])){
                                $value = $country_min_max[$max_index]['cost'];
                            }
                                
                            if($min_index !== false && floatval($value) < floatval($country_min_max[$min_index]['cost'])){
                                $value = $country_min_max[$min_index]['cost'];
                            }
                                
                        }
                    // }

                    $precision = isset($this->current_currency['decimal']) && $this->current_currency['decimal'] != '' ? $this->current_currency['decimal'] : get_option( 'woocommerce_price_num_decimals', 2 );
                    $rate->cost = number_format(floatval($value), $precision, $this->decimal_sep, '');
                   
                   
                    
                    // Calculate Taxes
                    if (isset($rate->taxes)) {
                        $taxes = $rate->taxes;
                     
                        if (!empty($taxes)) {
                            $new_tax = array();               
                            if ($this->fixed_shipping_price && $shipping_value && $value) {
                                if (wc_tax_enabled() && !WC()->customer->is_vat_exempt() && is_array($rate->taxes)) {
                                    $new_tax = WC_Tax::calc_shipping_tax($value, WC_Tax::get_shipping_tax_rates());
                                }
                            } else {   
                                foreach ($taxes as $order => $tax) {
                                    $value_tax = $tax * $this->current_currency['rate'];
                                    $sum = number_format(floatval($value_tax), $precision, $this->decimal_sep, '');
                                    $new_tax[$order] = $sum;
                                }
                            }
                            $rate->set_taxes($new_tax);
                        }
                    }


                    //Shipping method enable / deable based on currency  shipping_method_for_currencis shipping_method_for_currencis
                    $thisCurrency_key = isset($this->acowcs_settings['shipping_settings']) && is_array($this->acowcs_settings['shipping_settings']) ? array_search($this->current_currency['currency'], array_column($this->acowcs_settings['shipping_settings'], 'shepping_settings_currencies')) : false;

                    if($thisCurrency_key !== false){
                        $status = $this->acowcs_conditional_shipping_methods($this->acowcs_settings['shipping_settings'][$thisCurrency_key], $rate->method_id);
                        if($status === true){
                            $newRates[$rate_id] = $rate;
                        }
                    }else{
                        $newRates[$rate_id] = $rate; 
                    }

                }
                $rates = $newRates;
            // }
        return apply_filters( 'acowcs_shipping_rates', $rates );
    }
  

    /**
     * @access  public
     * @desc    filter woocommerce cart
     */
    public function acowcs_woocommerce_before_mini_cart(){
        WC()->cart->calculate_totals();
    }


    /**
     * @return sales price
     * @access  public
     * 
     */
    public function acowcs_raw_sale_price_filter($price, $product = null){
        return ($price == '') ? '' : $this->acowcs_raw_woocommerce_price($price, $product);
    }


    /**
     * @access  private
     * @return  product price based on user role
     */
    private function _get_product_fixed_user_role_price($product, $product_type, $price, $precision = 2, $type = NULL) {

        $product_id = $product->get_id();
        $price = $this->fixed_user_role->get_value($product_id, $this->current_currency['currency'], $this->user_roles, 'sale');
        if(!$price)
            $price = $this->fixed_user_role->get_value($product_id, $this->current_currency['currency'], $this->user_roles, 'regular');

        if($price)
            return apply_filters( 'acowcs_fixed_user_role_price', number_format(floatval($price), $precision, $this->decimal_sep, ''));
        
        return apply_filters( 'acowcs_fixed_user_role_price', false );
    }



    private function _get_product_fixed_price($product, $product_type, $price, $precision = 2, $type = NULL){
        $product_id = $product->get_id();
        $price = $this->fixed_amount->get_value($product_id, $this->current_currency['currency'], 'sale');
        if(!$price)
            $price = $this->fixed_amount->get_value($product_id, $this->current_currency['currency'], 'regular');    
        
        if($price)
            return number_format(floatval($price), $precision, $this->decimal_sep, '');

        return false; 
    } 


    /**
     * @var     function 
     * @param   price, products, min, display
     * @return  calculated price
     */
    public function acowcs_raw_woocommerce_price($price, $product = NULL, $min_max = NULL, $display = NULL){

        $curriencies = $this->acowcs_settings['curriencies'];
        
        $default_currency_key = array_search($this->default_currency, array_column($curriencies, 'currency'));
        $precision = 0;
        $is_price_custom = false;


        // Fixed product price
        if ($this->is_fixed_product_price) {
            if ($this->is_multiple_allowed && $product !== NULL && is_object($product)) {

                if ($product->is_type('variation')) {
                    $tmp_val = $this->_get_product_fixed_price($product, 'variation', $price, $precision);
                } elseif ($product->is_type('variable')) {
                    $tmp_val = false;
                } else {
                    $tmp_val = $this->_get_product_fixed_price($product, 'single', $price, $precision);
                }

                if ($tmp_val !== false) {
                    $price = apply_filters('acowcs_fixed_raw_woocommerce_price', $tmp_val, $product, $price);
                    $is_price_custom = true;
                }
            }
        }


        // Fixed price by user role
        if ($this->is_fixed_user_role && $product !== NULL) {
            if ($product->is_type('variation')) {
                $tmp_val = $this->_get_product_fixed_user_role_price($product, 'variation', $price, $precision);
            } elseif ($product->is_type('variable')) {
                $tmp_val = false;
            } else {
                $tmp_val = $this->_get_product_fixed_user_role_price($product, 'single', $price, $precision);
            }

            if ( $tmp_val !== false) {
                $price = apply_filters('acowcs_fixed_user_role_woocommerce_price', $tmp_val, $product, $price);
                $is_price_custom = true;
            }
        }


        if(is_checkout()){
            if(!isset($this->acowcs_settings['checkout_based_on_currency']) || $this->acowcs_settings['checkout_based_on_currency'] == ''){
                return $price;
            }
        }

        
        // Regular price if user role & fixed price not apply
        if(!$is_price_custom && isset($this->current_currency['currency']) && $this->current_currency['currency'] != $this->default_currency) {
                //Edited this line to set default convertion of currency
                if (isset($this->current_currency['rate']) && $this->current_currency['rate'] != '') {
                    $price = number_format(floatval((float) $price * (float) $this->current_currency['rate']), $precision, $this->decimal_sep, '');
                } else {
                    if(!isset($curriencies[$default_currency_key]['rate']))
                        return $price;

                    $price = number_format(floatval((float) $price * (float) $curriencies[$default_currency_key]['rate']), $precision, $this->decimal_sep, '');
                }
        }

        //compatibility  with memberships
        if (function_exists("wc_memberships") && $product !== NULL) {
            if (wc_memberships()->get_member_discounts_instance()->applying_discounts()) {
                if (doing_action('woocommerce_add_cart_item_data')) {
                    $price = wc_memberships()->get_member_discounts_instance()->get_discounted_price($price, $product);
                }
            }
        }
        return apply_filters('acowcs_raw_woocommerce_price', $price, $this->default_currency, $this->current_currency);
    }





    /**
     * @return customized currency symbol
     */
    public function woocommerce_currency_symbol($currency_symbol) {
        
        $change_to_default_currency = false;
        // Admin order list and single order
        if(is_admin()){
            $screen = function_exists('get_current_screen') ? get_current_screen() : false;
            if(isset($screen->post_type) && $screen->post_type == 'shop_order'){
                global $post;
                $order = wc_get_order( $post->ID );
                $change_to_default_currency = true;
                $this->default_currency = $order->get_currency();
            }
        }

        // Checkout page
        if(is_checkout()){
            $change_to_default_currency = true;
        }

        if($change_to_default_currency === true){
            if(!isset($this->acowcs_settings['checkout_based_on_currency']) || $this->acowcs_settings['checkout_based_on_currency'] == ''){
                $symbols = get_woocommerce_currency_symbols();
                $this->current_currency_symbol = $symbols[$this->default_currency];
            }
        }
        return $this->current_currency_symbol ? $this->current_currency_symbol : $currency_symbol;
    }


    public function acowcs_hide_currency_user_role($hidecurrencyusrerole){
            $newHidecurrencyusrerole = array();
            foreach($hidecurrencyusrerole as $k){
                $newHidecurrencyusrerole[$k['hidecurrency']] = array();
                foreach($k['hide_cr_user_roles'] as $s){
                    $newHidecurrencyusrerole[$k['hidecurrency']][] = $s['name'];
                }
            }
            return $newHidecurrencyusrerole;
        
    }






    /**
     * @access  private
     * @return  initial settings
     */
    private function acowcs_set_user_location(){
        $ipaddress  = ACOWCS_Helper()->get_client_ip();
        $ip_replace = str_replace('.', '', $ipaddress);
        
        if(get_option( $ip_replace.'_ccod' ))
            $this->current_user_country_code = get_option( $ip_replace.'_ccod' );

        if(!get_option( $ip_replace.'_ccod' )){
            $geoDetails = ACOWCS_Helper()->acowcs_geo_details();
            if(isset($geoDetails->countryCode)){
                update_option( $ip_replace.'_ccod', $geoDetails->countryCode );
                $this->current_user_country_code = $geoDetails->countryCode;
            }
        }
    }


    /**
     * @param NULL
     * @return NULL
     * @purpose set initial values
     */
    private function set_initial_config(){
        // Current user roles
        $client_ip = '';
        $client_ip = ACOWCS_Helper()->get_client_ip();
        $client_ip = str_replace('.', '', $client_ip);

        //User current location 
        $this->acowcs_set_user_location();

        
        //Visitor IP
        if(is_user_logged_in(  )) $client_ip = get_current_user_id(  );
        $this->get_client_ip = $client_ip;

        // Acowcs Settings From DB
        $this->acowcs_settings = acowcs_settings();

        // If default currency baed on user location 
        $this->acowcs_public_init();

        $user = wp_get_current_user();
        $this->user_roles = ( array ) $user->roles;
        $this->currency_storage_to = isset($this->acowcs_settings['currency_storage_to']) && $this->acowcs_settings['currency_storage_to'] != '' ? $this->acowcs_settings['currency_storage_to'] : 'transient';

        
        $current_currency = ACOWCS_Helper()->get_current_currency($this->currency_storage_to, $this->get_client_ip);

        
        
        
        $curriencies = isset($this->acowcs_settings['curriencies']) ? $this->acowcs_settings['curriencies'] : array();
        $current_currency_key = array_search($current_currency, array_column($curriencies, 'currency'));

        if(count($curriencies) <= 0)
            return;

        if($current_currency_key === false){
            $current_currency_key = array_search(1, array_column($curriencies, 'default'));
            $current_currency = $curriencies[$current_currency_key]['currency'];
        }
            

        
        if($current_currency && $current_currency_key === false)
            return;

        // Currency Sybmol Lists
        
        $symbolList = ACOWCS_Helper()->wcowcs_get_symbols_set();

        $symbol = get_woocommerce_currency_symbol();
        if(isset($curriencies[$current_currency_key]['symbol']) && isset( $symbolList[$curriencies[$current_currency_key]['symbol']]) )
            $symbol = get_woocommerce_currency_symbol($curriencies[$current_currency_key]['symbol']);
        if(isset($curriencies[$current_currency_key]['symbol']) && !isset( $symbolList[$curriencies[$current_currency_key]['symbol']]) )    
            $symbol = $curriencies[$current_currency_key]['symbol'];
        


        
        $this->enable_currency_switcher = isset($this->acowcs_settings['enable_currency_switcher']) && $this->acowcs_settings['enable_currency_switcher'] != '' ? $this->acowcs_settings['enable_currency_switcher'] : false;
        $this->current_currency         = $curriencies[$current_currency_key];
        $this->default_currency         = get_option( 'woocommerce_currency', acowcs_default_currency() );
        $this->current_currency_symbol  = $symbol;
        $this->fixed_shipping_price     = isset($this->acowcs_settings['fixed_shipping_price']) && $this->acowcs_settings['fixed_shipping_price'] != '' ? $this->acowcs_settings['fixed_shipping_price'] : false;
        $this->is_fixed_product_price   = isset($this->acowcs_settings['fixed_product_price']) && $this->acowcs_settings['fixed_product_price'] != '' ? true : false;
        $this->is_fixed_user_role       = isset($this->acowcs_settings['fixed_price_for_user_role']) && $this->acowcs_settings['fixed_price_for_user_role'] != '' ? true : false;
        $this->fixed_coupon_price       = isset($this->acowcs_settings['fixed_coupon_price']) && $this->acowcs_settings['fixed_coupon_price'] != '' ? true : false;
        if($this->is_fixed_product_price || $this->fixed_coupon_price){
            $this->fixed_amount         = new ACOWCS_Fixedamount;
        }

        
        if ($this->is_fixed_user_role) {
            $this->fixed_user_role = new ACOWCS_Fixeduser;
        }
    }


    /**
     * Change woocommerce currency from currency switcher db
     */
    public function acowcs_get_woocommerce_currency($currency){
        if(is_checkout()){
            if(!isset($this->acowcs_settings['checkout_based_on_currency']) || $this->acowcs_settings['checkout_based_on_currency'] == '')
                return $this->default_currency;
        }
        return isset($this->current_currency['currency']) && $this->current_currency['currency'] != '' ? $this->current_currency['currency'] : $currency;
    }


    /**
     * @var     function 
     * @return  forntend switcher html 
     * @access  public
     */
    public function acowcs_render_html(){
        if(is_admin(  )) 
            return false;
        //Return if currency bar not active
        if(!isset($this->acowcs_settings['enable_currency_bar']) || ( isset($this->acowcs_settings['enable_currency_bar']) && $this->acowcs_settings['enable_currency_bar'] == ''))
            return false; 

        
        if(is_checkout() && isset($this->acowcs_settings['hide_swither_on_checkout']) && $this->acowcs_settings['hide_swither_on_checkout'] != '')
            return false;

        
        if(is_user_logged_in(  ) && isset($this->acowcs_settings['show_switcher_on_userrole']) && is_array($this->acowcs_settings['show_switcher_on_userrole']) && is_array( $this->user_roles ) && count($this->acowcs_settings['show_switcher_on_userrole']) > 0 ){
            $swithcer_on_user_role_status = isset($this->acowcs_settings['swithcer_on_user_role_status']) ? $this->acowcs_settings['swithcer_on_user_role_status'] : 'include';
            $show_switcher_on_userrole = array_map(function($s){
                return $s['name'];
            }, $this->acowcs_settings['show_switcher_on_userrole']);

            $role_exists =  !empty(array_intersect($this->user_roles, $show_switcher_on_userrole));

            if(($swithcer_on_user_role_status == 'include' && !$role_exists) || ($swithcer_on_user_role_status == 'exclude' && $role_exists) )
                return false;
        }

        if(isset($this->acowcs_settings['show_switcher_page']) && is_array($this->acowcs_settings['show_switcher_page']) && count($this->acowcs_settings['show_switcher_page']) > 0){
            $status = isset($this->acowcs_settings['show_switcher_status']) && $this->acowcs_settings['show_switcher_status'] != '' ? $this->acowcs_settings['show_switcher_status'] : 'include'; 
            $pages = array_map(function($v){
                return $v['id'];
            }, $this->acowcs_settings['show_switcher_page']); 

            // show_switcher_status
            global $wp_query, $post;
            
            if(is_shop()){
                if($status == 'include' && !in_array( get_option( 'woocommerce_shop_page_id' ), $pages ))
                    return false;
                
                if($status == 'exclude' && in_array( get_option( 'woocommerce_shop_page_id' ), $pages ))
                    return false;
                
            }else{
                if($status == 'include' && !in_array($wp_query->get_queried_object_id(), $pages))
                    return false;
                
                if($status == 'exclude' && in_array($wp_query->get_queried_object_id(), $pages))
                    return false;
            }
        }

        

        // Add CSS
        wp_enqueue_style($this->token . '-switcherCSS');
        wp_add_inline_style( $this->token . '-switcherCSS', $this->acowcs_inline_css() );

        // Add JS
        wp_enqueue_script( $this->token . '_switcher' );
        wp_localize_script(
            $this->token . '_switcher',
            $this->token . '_object',
            array(
                'api_nonce' => wp_create_nonce('wp_rest'),
                'root' => rest_url($this->token . '/v1/'),
                'assets_url' => $this->assets_url,
                'user_id' => is_user_logged_in(  ) ? get_current_user_id(  ) : false
            )
        );

        require_once(ACOWCS_PATH . 'view/switcher.php');    
        $this->acowcs_render_header();
    }




    /**
     * Ensures only one instance of APIFW_Front_End is loaded or can be loaded.
     *
     * @param string $file Plugin root file path.
     * @return Main APIFW_Front_End instance
     * @since 1.0.0
     * @static
     */
    public static function instance($file = '')
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($file);
        }
        return self::$instance;
    }





    /**
    * @var  function
    * @return   conditional scripts 
    */
    public function acowcs_inline_scripts(){
        ob_start(); ?>
            jQuery(document).ready(function(){
                jQuery(document.body).on('mouseover', 'span.acowcs_price_info_icon', function(){
                    jQuery(this).next('ul.acowcs_price_info_list').animate({
                        right: 0
                    }, '500');
                }).on('mouseleave', 'span.acowcs_price_info_icon', function(){
                    jQuery(this).next('ul.acowcs_price_info_list').animate({
                        right: '-100%'
                    }, '500')
                });
            });
        <?php 
        $output = ob_get_clean();
        return $output;
    }


    /**
     * @var     function 
     * @access  public
     * @return  custom css and DB css
     */
    public function acowcs_inline_css(){
        if(!isset($this->acowcs_settings['custom_css']))
            return false;
        if(isset($this->acowcs_settings['custom_css']) && $this->acowcs_settings['custom_css'] == '')
            return false;
    
        return $this->acowcs_settings['custom_css'];
    }


    /**
     * Load Front End CSS.
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function frontend_enqueue_styles()
    {
        //Register css
        
        wp_register_style($this->token . '-switcherCSS', esc_url($this->assets_url) . 'css/switcher.css', array(), $this->version);
        wp_register_style($this->token . '-calculatorSelect2CSS', esc_url($this->assets_url) . 'css/select2.min.css', array(), $this->version);
        wp_register_style($this->token . '-calculatorCSS', esc_url($this->assets_url) . 'css/acowcs-calculator.css', array($this->token . '-calculatorSelect2CSS'), $this->version);
        wp_register_style($this->token . '-widgetCSS', esc_url($this->assets_url) . 'css/acowcs-widget.css', array(), $this->version);
        wp_register_style($this->token . '-priceInfoCSS', esc_url($this->assets_url) . 'css/switcher-price-info.css', array(), $this->version);
        
        if(isset($this->acowcs_settings['show_price_info_icon']) && $this->acowcs_settings['show_price_info_icon'] != '')
            wp_enqueue_style( $this->token . '-priceInfoCSS' );

        //js
        if (!wp_script_is('wp-i18n', 'registered')) {
            wp_register_script('wp-i18n', esc_url($this->assets_url) . 'js/i18n.min.js', array(), $this->version, true);
        }
        wp_register_script($this->token . '_switcher' , esc_url($this->assets_url) . 'js/switcher.js', array(), $this->version, true );
        
        wp_register_script($this->token . '_calculatorSelect2' , esc_url($this->assets_url) . 'js/select2.min.js', array(), $this->version, true );
        wp_register_script($this->token . '_calculatorJS' , esc_url($this->assets_url) . 'js/acowcs-calculator.js', array($this->token . '_calculatorSelect2'), $this->version, true );
        

        wp_add_inline_script( $this->token . '_switcher', $this->acowcs_inline_scripts() );

        wp_enqueue_script( 'wp-i18n' );

        if(isset($this->acowcs_settings['show_price_info_icon']) && $this->acowcs_settings['show_price_info_icon'] != '')
            wp_enqueue_script( $this->token . '_switcher' );
    }
}