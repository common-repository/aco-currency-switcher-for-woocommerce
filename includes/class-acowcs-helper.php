<?php

/**
 * Load Backend related actions
 *
 * @class   ACOWCS_Backend
 */

if (!defined('ABSPATH')) {
    exit;
}


class ACOWCS_Helper
{

    /**
     * @var     string
     * @access  private
     */
    private $acowcs_settings = array();
    /**
     * Class intance for singleton  class
     *
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

    /**
     * The main plugin file.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * Suffix for Javascripts.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;



    /**
     * Default curriencies
     * 
     * @var     string
     * @access  public
     * @since   1.0.0
     * 
     */
    public $default_currency;


    /**
     * Conerted Currency Name
     * 
     * @var     string
     * @access  public
     * @since   1.0.0
     * 
     */
    public $to_currency;

    /**
     * The plugin assets URL.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;
    /**
     * The plugin hook suffix.
     *
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $hook_suffix = array();


    /**
     * Constructor function.
     *
     * @access  public
     * @param string $file plugin start file path.
     * @since   1.0.0
     */
    public function __construct($file = '')
    {
        // Start Session 
        if (acowcs_settings('currency_storage_to') == 'php_session') {
            if (!session_id()) {
                @session_start();
            }
        }
        $this->version = ACOWCS_VERSION;
        $this->token = ACOWCS_TOKEN;
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));
        $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $plugin = plugin_basename($this->file);
        $this->acowcs_settings = acowcs_settings();

        //Cron job
        add_filter('cron_schedules', array($this, 'acowcs_cron_schedules'));
        add_action('wp', array($this, 'acowcs_addScheduleEventCallback') );
        add_action('acowcs_cron_event', array($this, 'acowcs_cron_event_callback'));
    }


    public function acowcs_cron_schedules($schedules){
        if(isset($this->acowcs_settings['auto_update_status']) && $this->acowcs_settings['auto_update_status'] != ''){
            $event_time = isset($this->acowcs_settings['update_duration']) && $this->acowcs_settings['update_duration'] != '' ? $this->acowcs_settings['update_duration'] : 'hourly';
            switch($event_time){
                case 'every12hour':
                    if(!isset($schedules["every12hour"])){
                        $schedules["every12hour"] = array(
                            'interval' => 12*60*60,
                            'display' => __('Once every 12 hours'));
                    }
                break;
                case 'weekly':
                    if(!isset($schedules["weekly"])){
                        $schedules["weekly"] = array(
                            'interval' => 604800,
                            'display' => __('Once every 1 week'));
                    }
                break;
                case 'monthly':
                    if(!isset($schedules["monthly"])){
                        $schedules["monthly"] = array(
                            'interval' => 2635200,
                            'display' => __('Once every 1 month'));
                    }
                break;
                case 'yearly':
                    if(!isset($schedules["yearly"])){
                        $schedules["yearly"] = array(
                            'interval' => 2635200 * 12,
                            'display' => __('Once every 1 year'));
                    }
                break;
            }
        }
        return $schedules;
    }



    public function acowcs_cron_event_callback(){
        $curriencies = array_map(function($v){
            return $v['currency'];
        }, $this->acowcs_settings['curriencies']);

        $api = new ACOWCS_Api;
        $api->acowcs_udate_rate(array(
            'all' => true,
            'to' => $curriencies
        ));
    }


    /**
     * @desc Crone schedule
     */
    public function acowcs_addScheduleEventCallback(){
        if(isset($this->acowcs_settings['auto_update_status']) && $this->acowcs_settings['auto_update_status'] != ''){
            $event_time = isset($this->acowcs_settings['update_duration']) && $this->acowcs_settings['update_duration'] != '' ? $this->acowcs_settings['update_duration'] : 'hourly';
            if(!wp_next_scheduled("acowcs_cron_event")) // acowcs_cron_event
            {
                wp_schedule_event( time(), $event_time,  'acowcs_cron_event' );
            }
        }
    }

    /**
     * Ensures only one instance of Class is loaded or can be loaded.
     *
     * @param string $file plugin start file path.
     * @return Main Class instance
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
     * @param NULL
     * @return list of currency symbol
     */
    public function wcowcs_get_symbols_set() {
        return array(
            'AED' => '&#x62f;.&#x625; (AED)',
            'USD' => '&#36; (USD)',
            'EUR' => '&euro; (EUR)',
            'GBP' => '&pound; (GBP)',
            'UAH' => '&#1075;&#1088;&#1085;. (UAH)',
            'RUB' => '&#1088;&#1091;&#1073;. (RUB)',
            'AFN' => '&#x60b; (AFN)',
            'ALL' => 'L (ALL)',
            'AMD' => 'AMD (AMD)',
            'ANG' => '&fnof; (ANG)',
            'AOA' => 'Kz (AOA)',
            'ARS' => '&#36; (ARS)',
            'AUD' => '&#36; (AUD)',
            'AWG' => 'Afl. (AWG)',
            'AZN' => 'AZN (AZN)',
            'BAM' => 'KM (BAM)',
            'BBD' => '&#36; (BBD)',
            'BDT' => '&#2547; (BDT)',
            'BGN' => '&#1083;&#1074;. (BGN)',
            'BHD' => '.&#x62f;.&#x628; (BHD)',
            'BIF' => 'Fr (BIF)',
            'BMD' => '&#36; (BMD)',
            'BND' => '&#36; (BND)',
            'BOB' => 'Bs. (BOB)',
            'BRL' => '&#82;&#36; (BRL)',
            'BSD' => '&#36; (BSD)',
            'BTC' => '&#3647; (BTC)',
            'BTN' => 'Nu. (BTN)',
            'BWP' => 'P (BWP)',
            'BYR' => 'Br (BYR)',
            'BYN' => 'Br (BYN)',
            'BZD' => '&#36; (BZD)',
            'CAD' => '&#36; (CAD)',
            'CDF' => 'Fr (CDF)',
            'CHF' => '&#67;&#72;&#70; (CHF)',
            'CLP' => '&#36; (CLP)',
            'CNY' => '&yen; (CNY)',
            'COP' => '&#36; (COP)',
            'CRC' => '&#x20a1; (CRC)',
            'CUC' => '&#36; (CUC)',
            'CUP' => '&#36; (CUP)',
            'CVE' => '&#36; (CVE)',
            'CZK' => '&#75;&#269; (CZK)',
            'DJF' => 'Fr (DJF)',
            'DKK' => 'DKK (DKK)',
            'DOP' => 'RD&#36; (DOP)',
            'DZD' => '&#x62f;.&#x62c; (DZD)',
            'EGP' => 'EGP (EGP)',
            'ERN' => 'Nfk (ERN)',
            'ETB' => 'Br (ETB)',
            'FJD' => '&#36; (FJD)',
            'FKP' => '&pound; (FKP)',
            'GEL' => '&#x10da; (GEL)',
            'GGP' => '&pound; (GGP)',
            'GHS' => '&#x20b5; (GHS)',
            'GIP' => '&pound; (GIP)',
            'GMD' => 'D (GMD)',
            'GNF' => 'Fr (GNF)',
            'GTQ' => 'Q (GTQ)',
            'GYD' => '&#36; (GYD)',
            'HKD' => '&#36; (HKD)',
            'HNL' => 'L (HNL)',
            'HRK' => 'Kn (HRK)',
            'HTG' => 'G (HTG)',
            'HUF' => '&#70;&#116; (HUF)',
            'IDR' => 'Rp (IDR)',
            'ILS' => '&#8362; (ILS)',
            'IMP' => '&pound; (IMP)',
            'INR' => '&#8377; (INR)',
            'IQD' => '&#x639;.&#x62f; (IQD)',
            'IRR' => '&#xfdfc; (IRR)',
            'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646; (IRT)',
            'ISK' => 'kr. (ISK)',
            'JEP' => '&pound; (JEP)',
            'JMD' => '&#36; (JMD)',
            'JOD' => '&#x62f;.&#x627; (JOD)',
            'JPY' => '&yen; (JPY)',
            'KES' => 'KSh (KES)',
            'KGS' => '&#x441;&#x43e;&#x43c; (KGS)',
            'KHR' => '&#x17db; (KHR)',
            'KMF' => 'Fr (KMF)',
            'KPW' => '&#x20a9; (KPW)',
            'KRW' => '&#8361; (KRW)',
            'KWD' => '&#x62f;.&#x643; (KWD)',
            'KYD' => '&#36; (KYD)',
            'KZT' => 'KZT (KZT)',
            'LAK' => '&#8365; (LAK)',
            'LBP' => '&#x644;.&#x644; (LBP)',
            'LKR' => '&#xdbb;&#xdd4; (LKR)',
            'LRD' => '&#36; (LRD)',
            'LSL' => 'L (LSL)',
            'LYD' => '&#x644;.&#x62f; (LYD)',
            'MAD' => '&#x62f;.&#x645;. (MAD)',
            'MDL' => 'MDL (MDL)',
            'MGA' => 'Ar (MGA)',
            'MKD' => '&#x434;&#x435;&#x43d; (MKD)',
            'MMK' => 'Ks (MMK)',
            'MNT' => '&#x20ae; (MNT)',
            'MOP' => 'P (MOP)',
            'MRO' => 'UM (MRO)',
            'MUR' => '&#x20a8; (MUR)',
            'MRU' => 'UM (MRU)',
            'MVR' => '.&#x783; (MVR)',
            'MWK' => 'MK (MWK)',
            'MXN' => '&#36; (MXN)',
            'MYR' => '&#82;&#77;',
            'MZN' => 'MT (MZN)',
            'NAD' => '&#36; (NAD)',
            'NGN' => '&#8358; (NGN)',
            'NIO' => 'C&#36; (NIO)',
            'NOK' => '&#107;&#114; (NOK)',
            'NPR' => '&#8360; (NPR)',
            'NZD' => '&#36; (NZD)',
            'OMR' => '&#x631;.&#x639;. (OMR)',
            'PAB' => 'B/. (PAB)',
            'PEN' => 'S/. (PEN)',
            'PGK' => 'K (PGK)',
            'PHP' => '&#8369; (PHP)',
            'PKR' => '&#8360; (PKR)',
            'PLN' => '&#122;&#322; (PLN)',
            'PRB' => '&#x440;. (PRB)',
            'PYG' => '&#8370; (PYG)',
            'QAR' => '&#x631;.&#x642; (QAR)',
            'RMB' => '&yen; (RMB)',
            'RON' => 'lei (RON)',
            'RSD' => '&#x434;&#x438;&#x43d;. (RSD)',
            'RWF' => 'Fr (RWF)',
            'SAR' => '&#x631;.&#x633; (SAR)',
            'SBD' => '&#36; (SBD)',
            'SCR' => '&#x20a8; (SCR)',
            'SDG' => '&#x62c;.&#x633;. (SDG)',
            'SEK' => '&#107;&#114; (SEK)',
            'SGD' => '&#36; (SGD)',
            'SHP' => '&pound; (SHP)',
            'SLL' => 'Le (SLL)',
            'SOS' => 'Sh (SOS)',
            'SRD' => '&#36; (SRD)',
            'SSP' => '&pound; (SSP)',
            'STN' => 'Db (STN)',
            'SYP' => '&#x644;.&#x633; (SYP)',
            'SZL' => 'L (SZL)',
            'THB' => '&#3647; (THB)',
            'TJS' => '&#x405;&#x41c; (TJS)',
            'TMT' => 'm (TMT)',
            'TND' => '&#x62f;.&#x62a; (TND)',
            'TOP' => 'T&#36; (TOP)',
            'TRY' => '&#8378; (TRY)',
            'TTD' => '&#36; (TTD)',
            'TWD' => '&#78;&#84;&#36; (TWD)',
            'TZS' => 'Sh (TZS)',
            'UGX' => 'UGX (UGX)',
            'UYU' => '&#36; (UYU)',
            'UZS' => 'UZS (UZS)',
            'VEF' => 'Bs F (VEF)',
            'VES' => 'B&#36; (VES)',
            'VND' => '&#8363; (VND)',
            'VUV' => 'Vt (VUV)',
            'WST' => 'T (WST)',
            'XAF' => 'CFA (XAF)',
            'XCD' => '&#36; (XCD)',
            'XOF' => 'CFA (XOF)',
            'XPF' => 'Fr (XPF)',
            'YER' => '&#xfdfc; (YER)',
            'ZAR' => '&#82; (ZAR)',
            'ZMW' => 'ZK (ZMW)'
        );
    }


    public function acowcs_escape($value) {
        return esc_html($value);
    }

    private function file_get_contents_curl($url) {
        $response = wp_remote_get( $url );
        $data     = wp_remote_retrieve_body( $response );
        return $data;
    }


    private function object2array($object) {
        return @json_decode(@json_encode($object), 1);
    }

    /**
     * @access  private
     * @return  client IP while script on live
     */
    private function getUserIP()
    {
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        $client  = isset($_SERVER['HTTP_CLIENT_IP']) ? @$_SERVER['HTTP_CLIENT_IP'] : '';
        $forward = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : false;
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP))
        {
            $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {
            $ip = $forward;
        }
        else
        {
            $ip = $remote;
        }

        return $ip;
    }

    /**
     * Client Geo Details
     * @access  private
     */
    public function acowcs_geo_details(){
        $ipapi_url = esc_url("http://ip-api.com/json/?fields=currency,query,countryCode");
        $details = wp_remote_retrieve_body(wp_remote_get( $ipapi_url ));
        $details = json_decode($details);
        return $details;
    }
    

    /**
     * @param NULL
     * @return client ip address $ip = $_SERVER["REMOTE_ADDR"];
     */
    public function get_client_ip() {
        if(isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))){
            if(isset($_COOKIE['userip_while_local'])){
                $ip_address = $_COOKIE['userip_while_local'];
            }else{
                $ip_address = $this->acowcs_geo_details();
                $ip_address =  isset($ip_address->query) ? $ip_address->query : 'localhost';
                setcookie('userip_while_local', $ip_address, time() + (86400 * 30), "/"); // 86400 = 1 day
            }
        }else{
            $ip_address = $this->getUserIP();
        }
        return $ip_address;
    }


    /**
     * @var     function
     * @access  private
     * @return  stored current currency
     */
    public function get_current_currency($currency_storage_to, $get_client_ip){
        $current_currency = get_woocommerce_currency(); 
        
        switch($currency_storage_to){
            case 'transient':
                $current_currency = get_transient( $get_client_ip . '_acowcs_currency' );
                if(!get_transient( $get_client_ip . '_acowcs_currency' ))
                    $current_currency = isset($_COOKIE['local_currency']) ? sanitize_text_field($_COOKIE['local_currency']) : $current_currency;
            break;
            case 'php_session':
                if(isset($_SESSION[$get_client_ip . '_acowcs_currency']))
                    $current_currency = sanitize_text_field( $_SESSION[$get_client_ip . '_acowcs_currency'] );

                if(!isset($_SESSION[$get_client_ip . '_acowcs_currency']))
                    $current_currency = isset($_COOKIE['local_currency']) ? sanitize_text_field($_COOKIE['local_currency']) : $current_currency;
            break;
            case 'cookies': 
                if(isset($_COOKIE[$get_client_ip . '_acowcs_currency'])){
                    $current_currency = sanitize_text_field( $_COOKIE[$get_client_ip . '_acowcs_currency'] );
                }else{
                    $geoDetails = ACOWCS_Helper()->acowcs_geo_details();
                    $current_currency =  isset($geoDetails->currency) ? esc_attr($geoDetails->currency) : $current_currency;
                }  
            break;
        }
        return $current_currency;
        
    }


    /**
     * List of currency aggregator 
     * @access  private
     */
    private function currency_aggregator(){
        return array(
            'yahoo' => 'www.finance.yahoo.com', 
            'ecb' => 'www.ecb.europa.eu', 
            'free_ecb' => 'The Free Currency Converter by European Central Bank', 
            'micro' => 'www.ratesapi.io - published by European Central Bank',
            'rf' => 'www.cbr.ru - russian centrobank', 
            'privatbank' => 'api.privatbank.ua - ukrainian privatbank', 
            'natbank' => 'Ukrainian national bank', 
            'bank_polski' => 'Narodowy Bank Polsky', 
            'free_converter' => 'The Free Currency Converter', 
            'fixer' => 'Fixer', 
            'cryptocompare' => 'CryptoCompare', 
            'ron' => 'www.bnr.ro', 
            'currencylayer' => 'Сurrencylayer', 
            'openexchangerates' => 'Open exchange rates'
        );
    }

    /**
     * @param NULL
     * @return currency rate
     */
    public function acowcs_get_currency_rate() {
        $aggregators = $this->currency_aggregator();
        $mode = isset($this->acowcs_settings['aggregator']) ? $this->acowcs_settings['aggregator'] : 'yahoo';
        
        $request = "";
        $acowcs_use_curl = 1;

        switch ($mode) {
            case 'ron':
                $url = 'https://www.bnr.ro/nbrfxrates.xml';
                if (function_exists('curl_init') && $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($url);
                } else {
                    $res = wp_remote_get($url);
                    $res = wp_remote_retrieve_body($res);
                }
                $currency_data = simplexml_load_string($res);
                $rates = array();
                if (empty($currency_data->Body->Cube)) {
                    $request = false;
                    break;
                }
                foreach ($currency_data->Body->Cube->Rate as $xml) {
                    $att = (array) $xml->attributes();
                    $final['rate'] = (string) $xml;
                    $rates[$att['@attributes']['currency']] = floatval($final['rate']);
                }

                
                //***
                if (!empty($rates) && isset($rates[$this->to_currency])) {
                    if ($this->default_currency != 'RON') {
                        if ($this->to_currency != 'RON') {
                            if (isset($this->to_currency)) {
                                $request = 1 / floatval($rates[$this->acowcs_escape($this->to_currency)] / $rates[$this->default_currency]);
                            } else {
                                $request = false;
                            }
                        } else {
                            $request = 1 * ($rates[$this->default_currency]);
                        }
                    } else {
                        if ($this->to_currency != 'RON') {
                            if ($rates[$this->to_currency] < 1) {
                                $request = 1 / $rates[$this->to_currency];
                            } else {
                                $request = $rates[$this->to_currency];
                            }
                        } else {
                            $request = 1;
                        }
                    }
                } else {
                    $request = false;
                }
                //***

                if (!$request) {
                    $request = sprintf(__("no data for %s", 'aco-currency-switcher'), $this->acowcs_escape($this->to_currency));
                }
                break;
                case 'currencylayer':
                    $from_Currency = urlencode($this->default_currency);
                    $to_Currency = urlencode($this->acowcs_escape($this->to_currency));
    
                    $key = acowcs_settings('currencylayer_apikey');
                    if (!$key) {
                        $request = esc_html__("Please use the API key", 'aco-currency-switcher');
                        break;
                    }
    
                    $url = "http://apilayer.net/api/live?source={$from_Currency}&currencies={$to_Currency}&access_key={$key}&format=1";
    
                    if (function_exists('curl_init') AND $acowcs_use_curl) {
                        $res = $this->file_get_contents_curl($url);
                    } else {
                        $res = wp_remote_get($url);
                        $res = wp_remote_retrieve_body($res);
                    }
    
                    $currency_data = json_decode($res, true);
    
                    $rates = isset($currency_data['quotes']) ? $currency_data['quotes'] : 0;
                    $request = isset($rates[$from_Currency . $to_Currency]) ? $rates[$from_Currency . $to_Currency] : false;
                    if (!$request) {
                        $request = sprintf(__("no data for %s", 'aco-currency-switcher'), $this->acowcs_escape($this->to_currency));
                    }
                break;
                case 'openexchangerates':
                    $from_Currency = urlencode($this->default_currency);
                    $to_Currency = urlencode($this->acowcs_escape($this->to_currency));
    
                    
                    $key = acowcs_settings('openexchangerates_apikey');
                    if (!$key) {
                        $request = esc_html__("Please use the API key", 'aco-currency-switcher');
                        break;
                    }
    
                    $url = "https://openexchangerates.org/api/latest.json?base={$from_Currency}&symbolst={$to_Currency}&app_id={$key}";
    
                    if (function_exists('curl_init') AND $acowcs_use_curl) {
                        $res = $this->file_get_contents_curl($url);
                    } else {
                        $res = wp_remote_get($url);
                        $res = wp_remote_retrieve_body($res);
                    }
    
                    $currency_data = json_decode($res, true);
    
                    $request = isset($currency_data['rates'][$to_Currency]) ? $currency_data['rates'][$to_Currency] : 0;
    
                    if (!$request) {
                        $request = sprintf(__("no data for %s", 'aco-currency-switcher'), $this->acowcs_escape($this->to_currency));
                    }
                break;
                case 'yahoo':
                    $date = current_time('timestamp', true);
                    $yql_query_url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . $this->default_currency . $this->acowcs_escape($this->to_currency) . '=X?symbol=' . $this->default_currency . $this->acowcs_escape($this->to_currency) . '%3DX&period1=' . ( $date - 60 * 86400 ) . '&period2=' . $date . '&interval=1d&includePrePost=false&events=div%7Csplit%7Cearn&lang=en-US&region=US&corsDomain=finance.yahoo.com';

                    if (function_exists('curl_init') && $acowcs_use_curl) {
                        $res = $this->file_get_contents_curl($yql_query_url);
                    } else {
                        $res = wp_remote_get($yql_query_url);
                        $res = wp_remote_retrieve_body($res);
                    }
                    
                    $data = json_decode($res, true);
                    $result = isset($data['chart']['result'][0]['indicators']['quote'][0]['open']) ? $data['chart']['result'][0]['indicators']['quote'][0]['open'] : ( isset($data['chart']['result'][0]['meta']['previousClose']) ? array($data['chart']['result'][0]['meta']['previousClose']) : array() );

                    if (count($result) && is_array($result)) {
                        $request = end($result);
                    }

                    if(!$request)
                        $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));

            break;
            case 'fixer':
                $from_Currency = urlencode($this->default_currency);
                $to_Currency = urlencode($this->acowcs_escape($this->to_currency));

                $key = acowcs_settings('fixer_apikey');
                if (!$key) {
                    $request = esc_html__("Please use the API key", 'aco-currency-switcher');
                    break;
                }
                $url = "http://data.fixer.io/api/latest?base={$from_Currency}&symbolst={$to_Currency}&access_key={$key}";


                if (function_exists('curl_init') AND $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($url);
                } else {
                    $res = wp_remote_get($url);
                    $res = wp_remote_retrieve_body($res);
                }


                $currency_data = json_decode($res, true);

                $request = isset($currency_data['rates'][$to_Currency]) ? $currency_data['rates'][$to_Currency] : false;

                if (!$request) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                }
                break;
                case 'cryptocompare':
                    $from_Currency = urlencode($this->default_currency);
                    $to_Currency = urlencode($this->acowcs_escape($this->to_currency));
                    //https://min-api.cryptocompare.com/data/price?fsym=ETH&tsyms=BTC
                    $query_str = sprintf("?fsym=%s&tsyms=%s", $from_Currency, $to_Currency);
                    $url = "https://min-api.cryptocompare.com/data/price" . $query_str;
                    
                    if (function_exists('curl_init') && $acowcs_use_curl) {
                        $res = $this->file_get_contents_curl($url);
                    } else {
                        $res = wp_remote_get($url);
                        $res = wp_remote_retrieve_body($res);
                    }

                    $currency_data = json_decode($res, true);
                    if (!empty($currency_data[$to_Currency])) {
                        $request = $currency_data[$to_Currency];
                    } else {
                        $request = false;
                    }
                    //***
                    if (!$request) {
                        $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                    }
            break;
            case 'natbank':
                //***
                $natbank_url = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';
                if (function_exists('curl_init') && $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($natbank_url);
                } else {
                    $res = wp_remote_get($natbank_url);
                    $res = wp_remote_retrieve_body($res);
                }

                $data = json_decode($res, true);

                if (!empty($data)) {
                    if ($this->default_currency != 'UAH') {

                        $def_cur_rate = 0;
                        foreach ($data as $item) {
                            if ($item["cc"] == $this->default_currency) {
                                $def_cur_rate = $item["rate"];
                                break;
                            }
                        }
                        if (!$def_cur_rate) {
                            $request = false;
                            break;
                        } elseif ($this->to_currency == 'UAH') {
                            $request = 1 * $def_cur_rate;
                        }
                        foreach ($data as $item) {
                            if ($item["cc"] == $this->to_currency) {
                                if ($this->to_currency != 'UAH') {
                                    if (isset($this->to_currency)) {
                                        $request = 1 / floatval($item["rate"] / $def_cur_rate);
                                    } else {
                                        $request = false;
                                    }
                                } else {
                                    $request = 1 * $def_cur_rate;
                                }
                            }
                        }
                    } else {
                        if ($this->to_currency != 'UAH') {
                            foreach ($data as $item) {
                                if ($item["cc"] == $this->to_currency) {
                                    $request = 1 / $item["rate"];
                                    break;
                                }
                            }
                        } else {
                            $request = 1;
                        }
                    }
                }
                if (!$request) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                }
            break;

            case 'privatbank':
                //https://api.privatbank.ua/#p24/exchange
                $url = 'https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=4'; //4,5

                if (function_exists('curl_init') AND $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($url);
                } else {
                    $res = wp_remote_get($url);
                    $res = wp_remote_retrieve_body($res);
                }

                $currency_data = json_decode($res, true);
                $rates = array();

                if (!empty($currency_data)) {
                    foreach ($currency_data as $c) {
                        if ($c['base_ccy'] == 'UAH') {
                            $rates[$c['ccy']] = floatval($c['sale']);
                        }
                    }
                }

                
                if (!empty($rates) && isset($rates[$this->default_currency])) {
                    if ($this->default_currency != 'UAH') {
                        if ($this->to_currency != 'UAH') {
                            if (isset($this->to_currency) && isset($rates[$this->default_currency] )) {
                                $request = floatval($rates[$this->default_currency] / ($rates[$this->acowcs_escape($this->to_currency)]));
                            } else {
                                $request = false;
                            }
                        } else {
                            $request = 1 / (1 / $rates[$this->default_currency]);
                        }
                    } else {
                        if ($this->to_currency != 'UAH') {
                            $request = 1 / $rates[$this->to_currency];
                        // } else {
                            $request = 1;
                        }
                    }
                } else {
                    $request = false;
                }

                if (!$request) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                }
                break;

            case 'ecb':
                $url = 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

                if (function_exists('curl_init') AND $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($url);
                } else {
                    $res = wp_remote_get($url);
                    $res = wp_remote_retrieve_body($res);
                }

                $currency_data = simplexml_load_string($res);
                $rates = array();
                if (empty($currency_data->Cube->Cube)) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                    break;
                }


                foreach ($currency_data->Cube->Cube->Cube as $xml) {
                    $att = (array) $xml->attributes();
                    $rates[$att['@attributes']['currency']] = floatval($att['@attributes']['rate']);
                }

                
                if (!empty($rates) && empty(array_intersect(array_keys($rates), array( $this->to_currency, $this->default_currency)))){

                    if ($this->default_currency != 'EUR') {
                        if ($this->to_currency != 'EUR') {
                            if (isset($this->to_currency)) {
                                $request = floatval($rates[$this->acowcs_escape($this->to_currency)] / $rates[$this->default_currency]);
                            } else {
                                $request = false;
                            }
                        } else {
                            $request = 1 / $rates[$this->default_currency];
                        }
                    } else {
                        if ($this->to_currency != 'EUR') {
                            if ($rates[$this->to_currency] < 1) {
                                $request = 1 / $rates[$this->to_currency];
                            } else {
                                $request = $rates[$this->to_currency];
                            }
                        } else {
                            $request = 1;
                        }
                    }
                } else {
                    $request = false;
                }


                if ($request === false) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                }
                break;

            case 'rf':
                //http://www.cbr.ru/scripts/XML_daily_eng.asp?date_req=21/08/2015
                $xml_url = 'http://www.cbr.ru/scripts/XML_daily_eng.asp?date_req='; //21/08/2015
                $date = date('d/m/Y');
                $xml_url .= $date;
                
                if (function_exists('curl_init') AND $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($xml_url);
                } else {
                    $res = wp_remote_get($xml_url);
                    $res = wp_remote_retrieve_body($res);
                }

                $xml = simplexml_load_string($res) or die("Error: Cannot create object");
                $xml = $this->object2array($xml);
                $rates = array();
                $nominal = array();

                if (isset($xml['Valute'])) {
                    if (!empty($xml['Valute'])) {
                        foreach ($xml['Valute'] as $value) {
                            $rates[$value['CharCode']] = floatval(str_replace(',', '.', $value['Value']));
                            $nominal[$value['CharCode']] = $value['Nominal'];
                        }
                    }
                }

                
                if (!empty($rates)) {
                    if ($this->default_currency != 'RUB') {
                        if ($this->to_currency != 'RUB') {
                            if (isset($this->to_currency) && isset($nominal[$this->acowcs_escape($this->to_currency)])) {
                                $request = $nominal[$this->acowcs_escape($this->to_currency)] * floatval($rates[$this->default_currency] / $rates[$this->acowcs_escape($this->to_currency)] / $nominal[$this->acowcs_escape($this->default_currency)]);
                            } else {
                                $request = false;
                            }
                        } else {
                            if ($nominal[$this->default_currency] == 10) {
                                $request = (1 / (1 / $rates[$this->default_currency])) / $nominal[$this->default_currency];
                            } else {
                                $request = 1 / (1 / $rates[$this->default_currency]);
                            }
                        }
                    } else {
                        if ($this->to_currency != 'RUB') {
                            $request = $nominal[$this->acowcs_escape($this->to_currency)] / $rates[$this->to_currency];
                        } else {
                            $request = 1;
                        }
                    }
                } else {
                    $request = false;
                }

                if (!$request) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                }

            break;

            case 'free_ecb':
                $apikey = acowcs_settings('free_ecb_apikey');
                
                if($apikey){
                    $ex_currency = $this->acowcs_escape($this->to_currency);
                    $query_url = 'http://api.exchangeratesapi.io/v1/latest?access_key='.$apikey.'&base='.$this->default_currency.'&symbols='.$ex_currency;
                    
                    if (function_exists('curl_init') AND $acowcs_use_curl) {
                        $res = $this->file_get_contents_curl($query_url);
                    } else {
                        $res = wp_remote_get($query_url);
                        $res = wp_remote_retrieve_body($res);
                    }

                    $data = json_decode($res, true);
                    
                    $request = isset($data['rates'][$ex_currency]) ? $data['rates'][$ex_currency] : 0;

                    if (!$request) {
                        $request = false;
                    }
                }
                else{
                    $request = false;
                }

                if(!$request)
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));

            break;
            case 'micro':
                
                $apikey = acowcs_settings('micro_apikey');

                if($apikey){
                    $ex_currency = $this->acowcs_escape($this->to_currency);
                    $query_url = 'http://api.exchangeratesapi.io/v1/latest?access_key='.$apikey.'&base='.$this->default_currency.'&symbols='.$ex_currency;
                    
                    if (function_exists('curl_init') AND $acowcs_use_curl) {
                        $res = $this->file_get_contents_curl($query_url);
                    } else {
                        $res = wp_remote_get($query_url);
                        $res = wp_remote_retrieve_body($res);
                    }

                    $data = json_decode($res, true);
                    
                    $request = isset($data['rates'][$ex_currency]) ? $data['rates'][$ex_currency] : 0;

                    if (!$request) {
                        $request = false;
                    }
                }else{
                    $request = false;
                }
                if(!$request)
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));

            break;
            case 'bank_polski':
                //http://api.nbp.pl/en.html
                $url = 'http://api.nbp.pl/api/exchangerates/tables/A'; //A,B

                if (function_exists('curl_init') AND $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($url);
                } else {
                    $res = wp_remote_get($url);
                    $res = wp_remote_retrieve_body($res);
                }

                $currency_data = json_decode($res, TRUE);
                $rates = array();
                if (!empty($currency_data[0])) {
                    foreach ($currency_data[0]['rates'] as $c) {
                        $rates[$c['code']] = floatval($c['mid']);
                    }
                }

            
                if (!empty($rates) && isset($rates[$this->acowcs_escape($this->to_currency)])) {
                    if ($this->default_currency != 'PLN') {
                        if ($this->to_currency != 'PLN') {
                            if (isset($this->to_currency)) {
                                $request = floatval($rates[$this->default_currency] / ($rates[$this->acowcs_escape($this->to_currency)]));
                            } else {
                                $request = false;
                            }
                        } else {
                            $request = 1 / (1 / $rates[$this->default_currency]);
                        }
                    } else {
                        if ($this->to_currency != 'PLN') {
                            $request = 1 / $rates[$this->to_currency];
                        } else {
                            $request = 1;
                        }
                    }
                } else {
                    $request = false;
                }


                if (!$request) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                }

                break;
            case'free_converter':
                $from_Currency = urlencode($this->default_currency);
                $this->to_currency = urlencode($this->acowcs_escape($this->to_currency));
                $query_str = sprintf("%s_%s", $from_Currency, $this->to_currency);
                $key = acowcs_settings('free_converter_apikey');
                if (!$key) {
                    $request = esc_html__("Please use the API key", 'aco-currency-switcher');
                    break;
                }
                $url = "http://api.currencyconverterapi.com/api/v3/convert?q={$query_str}&compact=ultra&apiKey={$key}";


                if (function_exists('curl_init') AND $acowcs_use_curl) {
                    $res = $this->file_get_contents_curl($url);
                } else {
                    $res = wp_remote_get($url);
                    $res = wp_remote_retrieve_body($res);
                }

                $currency_data = json_decode($res, true);

                if (!empty($currency_data[$query_str]['val'])) {
                    $request = $currency_data[$query_str]['val'];
                } else {
                    $request = false;
                }

            
                if (!$request) {
                    $request = sprintf(__("%s doesn't allow convert from %s to %s", 'aco-currency-switcher'), $aggregators[$mode], $this->default_currency, $this->acowcs_escape($this->to_currency));
                }
                break;
            default:
        }

        // return $request;
        return apply_filters( 'aco_currency_rate', $request, $mode, $this->default_currency, $this->to_currency );
    
    }


     /**
     * @var     function 
     * @access  private
     */
    public function get_geo_location_settings($curriencies){
        $country_status = array();
        foreach($curriencies as $single){
            if(!isset($single['country']))
                break;
                
            if(!isset($country_status[$single['currency']][$single['status']]))
                $country_status[$single['currency']][$single['status']] = array();
            

            $single['country'] = array_map(function($v){
                return $v['code'];
            }, $single['country']);

            if($single['status']){
                $country_status[$single['currency']][$single['status']] = array_merge($country_status[$single['currency']][$single['status']], $single['country']);
            } 
        }
        return $country_status;
    }
} // End Class