<?php
if (!defined('ABSPATH')) {
    exit;
}


class ACOWCS_Api
{
    


    /**
     * @var     string
     * @access  private
     */
    private $currency_storage_to = '';

    /**
     * @var     string
     * @access  private
     */
    private $version_type = 'free';

    /**
     * @var     string
     * @access  private
     */
    private $default_currency = '';

    /**
     * @var     array
     * @access  private
     */
    private $acowcs_settings = array();

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
     * The token.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $token;

    public function __construct()
    {
        // Start Session 
        if (acowcs_settings('currency_storage_to') == 'php_session') {
            if (!session_id()) {
                @session_start();
            }
        }

        $this->token = ACOWCS_TOKEN;
        $this->version_type = ACOWCS_VERSION_TYPE;
        $this->acowcs_settings = acowcs_settings();
        $this->default_currency = get_option( 'woocommerce_currency', acowcs_default_currency() );


        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    $this->token . '/v1',
                    '/config/',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'getConfig'),
                        'permission_callback' => array($this, 'getPermission'),
                    )
                );


                // Save settings to DB 
                register_rest_route(
                    $this->token . '/v1',
                    '/save/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'acowcsSaveSettings'),
                        'permission_callback' => array($this, 'getPermission'),
                    )
                );


                // Reset settings to DB 
                register_rest_route(
                    $this->token . '/v1',
                    '/reset_app/',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'acowcs_reset_app'),
                        'permission_callback' => array($this, 'getPermission'),
                    )
                );


                // Single product details
                register_rest_route(
                    $this->token . '/v1',
                    '/product_config/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'acowcs_single_wc_post_config'),
                        'permission_callback' => array($this, 'getPermission'),
                    )
                );

                // Save Single product config
                register_rest_route(
                    $this->token . '/v1',
                    '/product_save/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'acowcs_save_single_product_fixed_currencys'),
                        'permission_callback' => array($this, 'getPermission'),
                    )
                );


                // Update currency rate
                register_rest_route(
                    $this->token . '/v1',
                    '/update_currency_rate/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'acowcs_udate_rate'),
                        'permission_callback' => array($this, 'getPermission'),
                    )
                );


                // Change currency by user input
                register_rest_route(
                    $this->token . '/v1',
                    '/change_shop_currency/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'acowcs_change_shop_currency'),
                        'permission_callback' => array($this, 'getGlobalPermission')
                    )
                );

                // Change currency by user input
                register_rest_route(
                    $this->token . '/v1',
                    '/calculator_settings/',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'acowcs_get_setting_for_calculator'), 
                        'permission_callback' => array($this, 'getPermission')
                    )
                );

                // Calclulate currency
                register_rest_route(
                    $this->token . '/v1',
                    '/calculate_currency/',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'acowcs_calculate_currency'), 
                        'permission_callback' => array($this, 'getPermission')
                    )
                );

            }
        );
    }




    /**
     * @access  public
     * @return  bullian 
     * @desc    Return true for get currency change access for all type of user.
     */
    public function getGlobalPermission(){
        return true;
    }


    /**
     * @var     function
     * @param   {$data} as post
     * @return  calculate amount
     * @desc    Calculate currency
     */
    public function acowcs_calculate_currency($data){
        $helper = new ACOWCS_Helper;
        $helper->default_currency = $data['from'];
        $helper->to_currency = $data['to'];
        $rate = $helper->acowcs_get_currency_rate();
        $symbols        = ACOWCS_Helper()->wcowcs_get_symbols_set();
        $symbols = array_map(function($v){
            $filter = preg_replace("/\([^)]+\)/","", $v); // 'ABC '
            $filter = str_replace(' ', '', $filter);
            return html_entity_decode($filter);
        }, $symbols);
        
        $result = 'error';
        if(gettype($rate) !== 'string'){
            $result = $rate * $data['converted_amount'];
            $result = number_format($result, 2);
            $result = sprintf(__('<strong>%s %s%s = %s %s%s</strong>', 'aco-currency-switcher'), $data['from'], $symbols[$data['from']], number_format($data['converted_amount'], 2), $data['to'], $symbols[$data['to']], $result);
        }

        $array = array(
            'msg' => 'success', 
            'result' => $result, 
            'error' => $result == 'error' ? $rate : ''
        );

        $array = apply_filters( 'acowcs_calculate_data', $array );

        return new WP_REST_Response($array, 200);
    }




    /**
     * @param   NULL
     * @return  necessary settings for use in calculator
     * 
     */
    public function acowcs_get_setting_for_calculator(){

        $currencies     = get_woocommerce_currencies();
        $symbols        = ACOWCS_Helper()->wcowcs_get_symbols_set();


        // Map Currencies
        $currencies = array_map(function($v) use ($currencies, $symbols){
            $filter = preg_replace("/\([^)]+\)/","", $symbols[array_search($v, $currencies)]); // 'ABC '
            return array_search($v, $currencies) . '-' . html_entity_decode($v) . ' (' . html_entity_decode($filter) . ')';
        }, $currencies);


        $currencyLists = array(
            'currency' => $this->acowcs_settings['curriencies'], 
            'currencys' => $currencies
        ); 
        
        $currencyLists = apply_filters( 'acowcs_currencylists', $currencyLists );
        return new WP_REST_Response($currencyLists, 200);
    }



    /**
     * @param {$data} user input data
     * @return success message after processed
     */
    public function acowcs_change_shop_currency($data){
        $this->currency_storage_to = isset($this->acowcs_settings['currency_storage_to']) && $this->acowcs_settings['currency_storage_to'] != '' ? $this->acowcs_settings['currency_storage_to'] : 'transient';
        $currency = $data['currency'];
        $client_ip = ACOWCS_Helper()->get_client_ip();
        $client_ip = str_replace('.', '', $client_ip);
        if($data['user_id'] && $data['user_id'] != '') $client_ip = $data['user_id'];

        switch($this->currency_storage_to){
            case 'transient':
                set_transient( $client_ip . '_acowcs_currency', $currency );
            break;
            case 'php_session': 
                $_SESSION[$client_ip . '_acowcs_currency'] = $currency;
            break;
            case 'cookies':
                setcookie($client_ip . '_acowcs_currency', $currency, time() + (86400 * 360), "/"); // 86400 = 1 day
            break;
        }
        
        
        $return = array(
            'msg' => 'success'
        );
        
        return new WP_REST_Response($return, 200);
    }

    
    /**
     * @var     array | post data
     * @return  acowcs settings after update
     * @access  public
     */
    public function acowcs_udate_rate($data){
        $curriencies = $this->acowcs_settings;
        $helper = new ACOWCS_Helper;
        $rates_array = array();
        $errors = array();
        
        if(is_array($data['to']) && isset($data['all']) && $data['all'] === true){
            foreach($data['to'] as $s){
                $helper->default_currency = $this->default_currency;
                $helper->to_currency = $s;
                $rate = $helper->acowcs_get_currency_rate();
                if(gettype($rate) == 'string')
                    array_push($errors, $rate);

                $to_key = is_array($curriencies['curriencies']) ? array_search($s, array_column($curriencies['curriencies'], 'currency')) : false;
                if($to_key && gettype($rate) != 'string')
                    $curriencies['curriencies'][$to_key]['rate'] = $rate;   
            }

            //Send Mail
            $body = $this->acowcs_email_template();  
            if($body){
                $to = get_option( 'admin_email' );
                $subject = sprintf(__('Currency rate update successfully on %s', 'aco-currency-switcher'), date('Y-m-d H:i:s')); 
                if(isset($this->acowcs_settings['email_subject']) && $this->acowcs_settings['email_subject'] != '')
                    $subject = $this->acowcs_settings['email_subject'];
            
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $headers[] = 'From: '.get_option( 'blogname' ).'<wordpress@'. strtolower( get_option( 'blogname' ) ) .'.com>';
                wp_mail( $to, $subject, $body, $headers );
            }
        }else{
            $helper->default_currency = $this->default_currency;
            $helper->to_currency = $data['to'];
            $rate = $helper->acowcs_get_currency_rate();
            if(gettype($rate) == 'string')
                    array_push($errors, $rate);

            $to_key = is_array($curriencies['curriencies']) ? array_search($data['to'], array_column($curriencies['curriencies'], 'currency')) : false;

            if($to_key === false){
                if($this->version_type == 'free' && count($curriencies['curriencies']) >= 2){
                    $addInstantCurrency = false;
                }
                    
                $curriencies['curriencies'][$data['index']]['currency'] = $data['to'];
                $to_key = $data['index'];
            }

            if($to_key !== false)
                $curriencies['curriencies'][$to_key]['rate'] = $rate;
                
        }
        
        update_option( 'acowcs_settings', $curriencies, 'yes' );

        $config = $this->acowcs_get_settings();
        return new WP_REST_Response(array(
            'config' => $config, 
            'errors' => $errors, 
            'default' => $helper->default_currency, 
            'to' => $helper->to_currency, 
            'to_key' => $to_key
        ), 200);  
    }

   
   


    /**
     * @var     function 
     * @access  public
     * @return  email templaet
     */
    public function acowcs_email_template(){
        if(!isset($this->acowcs_settings['currency_mail_notification_body']))
            return false;
        
        if(isset($this->acowcs_settings['currency_mail_notification_body']) && $this->acowcs_settings['currency_mail_notification_body'] == '')
            return false;
        
        if(!isset($this->acowcs_settings['update_email_notification']))
            return false;
        
        if(isset($this->acowcs_settings['update_email_notification']) && $this->acowcs_settings['update_email_notification'] == '')
            return false;

        $body = $this->acowcs_settings['currency_mail_notification_body']; 


            $body = str_replace(
                array(
                    'base_currencies',
                    'currencies_list',
                    '{',
                    '}'
                ), 
                array(
                    $this->default_currency,
                    $this->acowcs_email_template_body(),
                    '', 
                    ''
                ), 
                $body
            );

        return $body;
    }



    /**
     * @var     function 
     * @access  protected 
     * @return  currency table for email body
     */
    public function acowcs_email_template_body(){
        if(!isset($this->acowcs_settings['curriencies']) || !is_array($this->acowcs_settings['curriencies']))
            return false;

        $table_body = '';
        foreach($this->acowcs_settings['curriencies'] as $single){            
            $table_body .= '
                <tr>
                    <td style="border: 1px solid #555; padding: 5px;">'.$single['currency'].'</td>
                    <td style="border: 1px solid #555; padding: 5px;">'.$this->default_currency.'</td>
                    <td style="border: 1px solid #555; padding: 5px;">'.$single['rate'].'</td>
                </tr>
            ';
        }

        $table = '
            <table style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #555; padding: 5px;">Currency</th>
                            <th style="border: 1px solid #555; padding: 5px;">Base Currency</th>
                            <th style="border: 1px solid #555; padding: 5px;">Exchange Rate</th>  
                        </tr>
                    </thead>
                    <tbody> 
                        '.$table_body.'               
                    </tbody>
            </table>
        ';
        return $table;
    }



 

    /**
     * @access  public
     * @purpose reset app settings
     * @method  get
     */
    public function acowcs_reset_app(){
        ACOWCS_Backend()->acowcs_install(true);
        return new WP_REST_Response('success', 200);   
    }


    /**
     * @param post data from react
     * return success message
     * 
     */
    public function acowcsSaveSettings($data){
        $postData = $data['settings'];
        $default_key = is_array($postData['curriencies']) ? array_search(true, array_column($postData['curriencies'], 'default')) : false;

        update_option( 'acowcs_settings', $postData, true );

        // change wc default currencys
        if($default_key !== false) update_option( 'woocommerce_currency', $postData['curriencies'][$default_key]['currency'], 'yes' );

        // Clear previous cronjob event for create new one with credientials
        wp_clear_scheduled_hook('acowcs_cron_event');
        
        return new WP_REST_Response('success', 200);   
    }



    /**
     * @param {posts} data as array
     * @return success message after complete
    */
    public function acowcs_save_single_product_fixed_currencys($data){
        $fixed_currency_price = $data['fixed_currency_price'];
        $fixed_userrole_price = $data['fixed_userrole_price'];

        update_post_meta( (int)$data['post_id'], 'fixed_currency_price', $fixed_currency_price );
        update_post_meta( (int)$data['post_id'], 'fixed_userrole_price', $fixed_userrole_price );
        return new WP_REST_Response('success', 200);   
    }
     
    


    /**
     * @param {$data} string from get
     * @return single product config
     */
    public function acowcs_single_wc_post_config($data){
        $currencies     = get_woocommerce_currencies();
        $settings = acowcs_settings();

        $curriencies = $settings['curriencies'];
        $key = array_search(1, array_column($curriencies, 'default'));
        unset($settings['curriencies'][$key]);
        $settings['curriencies'] = array_values($settings['curriencies']);
        
        $product = 'shop_coupon' == get_post_type( $data['post_id'] ) ? false : wc_get_product($data['post_id']);

        $post_meta = get_post_meta( (int)$data['post_id'], 'fixed_currency_price', true );
        $post_meta = $post_meta ? $post_meta : array();

        $userrolePrice = get_post_meta( (int)$data['post_id'], 'fixed_userrole_price', true ) ? get_post_meta( (int)$data['post_id'], 'fixed_userrole_price', true ) : array();
        

        $return = array(
            'post_id' => $data['post_id'],
            'settings' => $settings,
            'currencies' => $currencies, 
            'post_type' => get_post_type( $data['post_id'] ),
            'userroles' => $this->acowcs_user_roles(), 
            'base_price' => $product ? $product->get_price() : get_post_meta( $data['post_id'], 'coupon_amount', true ),
            'fixed_currency_price' => $post_meta, 
            'fixed_userrole_price' => $userrolePrice
        );

        if(get_post_type( $data['post_id'] ) == 'product'){
            $return['product_type'] = $product->get_type();
        
            if($product->get_type() == 'variable'){
                $variations = $product->get_variation_prices();
                // $variations_id = wp_list_pluck( $variations, 'variation_id' );
                $variations_id = array_keys($variations['regular_price']);

                $variations_ids = array(
                    '' => __('Select Variation', 'aco-currency-switcher')
                );
                foreach($variations_id as $s){
                    $variations_ids[$s] = html_entity_decode(get_the_title( $s ));
                }

                $return['product_variation_ids'] = $variations_ids;
            }
        }
            
        return new WP_REST_Response($return, 200);   
    }



    /**
     * @param null
     * return all shipping country as list
     */
    public function acowcs_get_country_lists(){
        $countries = WC()->countries->countries;
        $countriesArray = array_map(function($v) use($countries){
            $object = new stdClass();   
            $object->code = $v;
            $object->name = $countries[$v];
            return $object;
        }, array_keys($countries));
        return $countriesArray;
    }






    /**
     * @param null
     * return all shipping method as list
     */
    public function acowcs_get_shipping_method_lists(){
        $shipping_methods = WC()->shipping->load_shipping_methods();
        $shipping_methods = array_map(function($v) use($shipping_methods){
            $object = new stdClass();   
            $object->method_id = $shipping_methods[$v]->id;
            $object->method_title = $shipping_methods[$v]->method_title;
            return $object;
        }, array_keys($shipping_methods));
        return $shipping_methods;
    }



    /**
     * @param NULL
     * return user roles
     */
    public function acowcs_user_roles(){
        global $wp_roles;
        $roles = $wp_roles->get_names();

        $rolesd = array_map(function($v) use ($roles){
            $object = new stdClass();   
            $object->name = array_search( $v, $roles );
            $object->label = $v;
            return $object;
        }, $roles);

        return apply_filters( 'acowcs_api_user_role', array_values($rolesd) );
    }



    /**
     * @param NULL
     * return all pages id and title
     */
    public function acowcs_all_pages(){
        $page_ids= get_all_page_ids();
        $pages = array_map(function($v){
            $object = new stdClass();   
            $object->id = $v;
            $object->title = get_the_title( $v );
            return $object;
        }, $page_ids);

        return $pages;
    }


    public function acowcs_get_settings(){
        $currencies     = get_woocommerce_currencies();
        $symbols        = ACOWCS_Helper()->wcowcs_get_symbols_set();
        


        // Map Currencies
        $currencies = array_map(function($v) use ($currencies, $symbols){
            return array_search($v, $currencies) . '-' . html_entity_decode($v) . ' (' . html_entity_decode($symbols[array_search($v, $currencies)]) . ')';
        }, $currencies);

        // Add initial value
        $currencies = array_merge(array(
            '' => __('Currency', 'aco-currency-switcher')
        ), $currencies);


        // Map Symbols
        $symbols = array_map(function($v){
            return html_entity_decode($v);
        }, $symbols);

        // Get Settings 
        $settings = acowcs_settings();
        


        // Add initial value
        
        $symbols = array_merge(array(
            '' => __('Symbol', 'aco-currency-switcher')
        ), $symbols);

        if(isset($settings['custom_currency_symbol'])){
            $custom_symbol = explode("\n", $settings['custom_currency_symbol']);
            if(is_array($custom_symbol) && count($custom_symbol) > 0){
                $custom_symbol = array_combine($custom_symbol, $custom_symbol);
                $symbols = array_merge($symbols, $custom_symbol);
            }
        }     

        $config = array(
            'currencies' => $currencies, 
            'symbols' => $symbols, 
            'pages' => $this->acowcs_all_pages(),
            'userroles' => $this->acowcs_user_roles(), 
            'shipping_methods' => $this->acowcs_get_shipping_method_lists(), 
            'countries' => $this->acowcs_get_country_lists(), 
            'settings' => $settings
        );
        return $config;
    }


    public function getConfig()
    {
        $config = $this->acowcs_get_settings();
        return new WP_REST_Response($config, 200);
    }




    /**
     *
     * Ensures only one instance of APIFW is loaded or can be loaded.
     *
     * @param string $file Plugin root path.
     * @return Main APIFW instance
     * @see WordPress_Plugin_Template()
     * @since 1.0.0
     * @static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Permission Callback
     **/
    public function getPermission()
    {
        if(is_user_logged_in(  )){
            if (current_user_can('administrator') || current_user_can('manage_woocommerce')) {
                return true;
            } else {
                return false;
            }
        }else{
            if(!is_admin(  )){
                return true;
            }
        }
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }
}
