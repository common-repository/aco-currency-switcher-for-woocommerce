<?php

/**
 * Load Backend related actions
 *
 * @class   ACOWCS_Backend
 */

if (!defined('ABSPATH')) {
    exit;
}


class ACOWCS_Backend
{


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
     */
    public $hook_suffix = array();

    /**
     * WP DB
     * @var     string
     * @access  private
     * 
     */
    private $wpdb;


    /**
     * Constructor function.
     *
     * @access  public
     * @param string $file plugin start file path.
     * @since   1.0.0
     */
    public function __construct($file = '')
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->version = ACOWCS_VERSION;
        $this->token = ACOWCS_TOKEN;
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));
        $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $plugin = plugin_basename($this->file);


        if($this->isWoocommerceActivated() === true):
            // add action links to link to link list display on the plugins page.
            add_filter("plugin_action_links_$plugin", array($this, 'pluginActionLinks'));

            // reg activation hook.
            register_activation_hook($this->file, array($this, 'acowcs_install'));

            // reg deactivation hook.
            register_deactivation_hook($this->file, array($this, 'deactivation'));

            // reg admin menu.
            add_action('admin_menu', array($this, 'registerRootPage'), 30);

            // enqueue scripts & styles.
            add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'), 10, 1);
            add_action('admin_enqueue_scripts', array($this, 'adminEnqueueStyles'), 10, 1);

            // Add additionl tab on woocommerce product tab
            add_filter( 'woocommerce_product_data_tabs', array($this, 'acowcs_fixed_price_product_data_tab') );
            add_filter( 'woocommerce_product_data_panels', array($this, 'acowcs_options_product_tab_content') ); // WC 2.6 and up

            // Add additional tab on woocommerce product variation tab
            add_action('woocommerce_product_after_variable_attributes', array($this, 'acowcs_options_product_tab_content'), 9999, 3);
            

            // Add additional tab on woocommerce coupon tab
            add_filter( 'woocommerce_coupon_data_tabs', array($this, 'acowcs_fixed_price_product_data_tab') );
            add_filter( 'woocommerce_coupon_data_panels', array($this, 'acowcs_options_product_tab_content') ); // WC 2.6 and up
            
            // Add additional field on shipping class for shipping fixed price
            add_filter('woocommerce_shipping_instance_form_fields_flat_rate', array($this, 'acowcs_flat_rate_additional_field'), 9999, 1);
            add_filter('woocommerce_shipping_instance_form_fields_free_shipping', array($this, 'acowcs_add_fixed_rate_for_free_shipping'), 9999, 1);
            add_filter('woocommerce_shipping_instance_form_fields_local_pickup', array($this, 'acowcs_flat_rate_additional_field'), 9999, 1);

            // Add product to suffix
            $this->hook_suffix[] = 'product';
            $this->hook_suffix[] = 'shop_coupon';
        else:
            add_action( 'admin_notices', array($this, 'noticeNeedWoocommerce') );
        endif;
    }



    public function acowcs_options_product_tab_content_variation(){
        require_once(ACOWCS_PATH . 'view/variation-metabox.php');   
    }


    public function acowcs_add_fixed_rate_for_free_shipping($fields){
        $fields = $this->acowcs_flat_rate_additional_field($fields, 'free');

        wc_enqueue_js("
        		jQuery( function( $ ) {
                function wcowcsFreeShippingShowHideMinAmountFields( el ) {
                    var form = $( el ).closest( 'form' );
                    
                    var min_amount_field = $( 'input[id^=woocommerce_free_shipping_acowcs_fixed_]', form ).closest( 'tr' );

                    if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
                        min_amount_field.hide();
                    } else {
                        min_amount_field.show();
                    }
			    }

                $( document.body ).on( 'change', '#woocommerce_free_shipping_requires', function() {
                    wcowcsFreeShippingShowHideMinAmountFields( this );
                });

                // Change while load.
                $( '#woocommerce_free_shipping_requires' ).trigger('change');
                
                $( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
                        if ( 'wc-modal-shipping-method-settings' === target ) {
                            wcowcsFreeShippingShowHideMinAmountFields( $( '#wc-backbone-modal-dialog #woocommerce_free_shipping_requires', evt.currentTarget ) );
                        }
                    });
                });
	    ");

        return $fields;
    }

    

    /**
     * @param method fields
     * @return $fields
     * Description: add additional field for currency switcher
     */
    public function acowcs_flat_rate_additional_field($fields, $type = false){
        $curriencies = acowcs_default_currency(true);

        if(!$curriencies)
            return $fields;
        
        if(!is_array($curriencies))
            return $fields;

        foreach ($curriencies as $k => $data) {
            $fields['acowcs_fixed_' . $data['currency']] = array(
                'title' => $type == 'free' ? sprintf(esc_html__('Minimum order amount in %s', 'aco-currency-switcher'), $data['currency']) : sprintf(esc_html__('Fixed cost for %s', 'aco-currency-switcher'), $data['currency']),
                'type' => 'number',
                'placeholder' => esc_html__("auto", 'aco-currency-switcher'),
                'description' => $data['currency'],
                'default' => '',
                'desc_tip' => true
            );
        }
        return $fields;
    }




    /**
     * Contents of the gift card options product tab.
     */
    public function acowcs_options_product_tab_content() {

        global $post;
        // Note the 'id' attribute needs to match the 'target' parameter set above
        ?><div id='currency_fixed_price_options' class='panel woocommerce_options_panel'>
            <div class='options_group'>
                <div id="<?php esc_attr_e( $this->token ); ?>_ui_product">
                    <div class="<?php esc_attr_e( $this->token ); ?>_loader"><p><?php esc_attr_e('Loading User Interface...', 'aco-currency-switcher'); ?></p></div>
                </div>
            </div>
        </div><?php
    }


    /**
     * @param string tab
     * @return tabs
     * @static
     */
    public function acowcs_fixed_price_product_data_tab($tabs){
        global $post;
        $post_type = get_post_type( $post->ID );
        $acowcs_settings = acowcs_settings(); 
        $additional = true;
        //Product
        if($post_type == 'product'){
            if(!isset($acowcs_settings['fixed_product_price']) || ( isset($acowcs_settings['fixed_product_price']) && !$acowcs_settings['fixed_product_price'] )){
                $additional = false;
            }
        }

        // Coupon
        if($post_type == 'shop_coupon'){
            if(!isset($acowcs_settings['fixed_coupon_price']) || ( isset($acowcs_settings['fixed_coupon_price']) && !$acowcs_settings['fixed_coupon_price'] )){
                $additional = false;
            }
        }

        if(!$additional){
            return $tabs;
        }

        $tabs['currency_fixed_price'] = array(
            'label'		=> $post_type != 'shop_coupon' ? __( 'Currency Fixed Price', 'aco-currency-switcher' ) : __( 'Currency Fixed Amount', 'aco-currency-switcher' ),
            'target'	=> 'currency_fixed_price_options',
            'class'		=> array(  ),
        );
    
        return $tabs;
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
     * Show action links on the plugin screen.
     *
     * @param mixed $links Plugin Action links.
     *
     * @return array
     */
    public function pluginActionLinks($links)
    {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=' . $this->token . '-switcher/') . '">'
                . __('Configure', 'aco-currency-switcher') . '</a>'
        );

        return array_merge($action_links, $links);
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
     * Installation. Runs on activation.
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public static function acowcs_install($fourse = false)
    {
        // Default Currency switcher settings to option
        $action = false;
        if(!get_option( 'acowcs_settings', false ))
            $action = true;    
        if($fourse === true)
            $action = true;

        if($action === true){
            $base_country = wc_get_base_location();
            $acowcs_settings = array(
                'enable_currency_switcher' => 1, 
                'curriencies' => array(
                    array(
                        'default' => 1,
                        'currency' => get_woocommerce_currency(), 
                        'flag' => $base_country['country'], 
                        'symbol' => get_woocommerce_currency(), 
                        'decimal' => 2, 
                        'rate' => 1,
                        'fee' => 0, 
                        'position' => 'left'
                    )
                    ), 
                'aggregator' => 'yahoo', 
                'show_switcher_flug' => true, 
                'default_currency_based_on_location' => true, 
                'enable_currency_bar' => true, 
                'currency_storage_to' => '', 
                'show_switcher_status' => 'include', 
                'swithcer_on_user_role_status' => 'include'
            );

            update_option( 'acowcs_settings', $acowcs_settings, true );
        }
    }

    /**
     * WooCommerce not active notice.
     *
     * @access  public
     * @return void Fallack notice.
     */
    public function noticeNeedWoocommerce()
    {
        printf(
            sprintf(
                /* translators: %s: Plugin Name. */
                __(
                    '<div class="error"><p>%s requires <a href="%s">WooCommerce</a> to be installed & activated!</p></div>',
                    'aco-currency-switcher'
                ),
                ACOWCS_PLUGIN_NAME,
                esc_url( 'http://wordpress.org/extend/plugins/woocommerce/' )
            )
        );   
    }




    /**
     * Creating admin pages
     */
    public function registerRootPage()
    {
        $this->hook_suffix[] = add_menu_page(
            __('Currency Switcher', 'aco-currency-switcher'),
            __('Currency Switcher', 'aco-currency-switcher'),
            'manage_woocommerce',
            $this->token . '-switcher',
            array($this, 'adminUi'), 
            'dashicons-money-alt', 
            56
        );
    }




    /**
     * Calling view function for admin page components
     */
    public function adminUi()
    {
        printf(
            '<div id="' . esc_attr($this->token) . '_ui_root">
              <div class="' . esc_attr($this->token) . '_loader"><p>' . __('Loading User Interface...', 'aco-currency-switcher') . '</p></div>
            </div>'
        );
    }


    /**
     * Load admin CSS.
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function adminEnqueueStyles()
    {
        $screen = get_current_screen();
        
        wp_register_style($this->token . '-admin', esc_url($this->assets_url) . 'css/backend.css', array(), $this->version);
        wp_register_style($this->token . '-product', esc_url($this->assets_url) . 'css/product.css', array(), $this->version);


        if(isset($screen->base) && $screen->base == 'toplevel_page_acowcs-switcher')  wp_enqueue_style($this->token . '-admin');
        if(isset($screen->post_type) &&  in_array($screen->post_type, array('product', 'shop_coupon') ) )  wp_enqueue_style($this->token . '-product');
    }



    /***
     * Get Active Payment Methods */    
    public function acowcs_get_payment_methods(){
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $gateways = array_map(function($v) use($gateways){
            $object = new stdClass();
            $object->method_id = $v;
            $object->method_title = $gateways[$v]->title;
            return $object;
        }, array_keys($gateways));
        return $gateways;
    }




    /**
     * Load admin Javascript.
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function adminEnqueueScripts()
    {
        if (!isset($this->hook_suffix) || empty($this->hook_suffix)) {
            return;
        }

        $screen = get_current_screen();

        if (in_array($screen->id, $this->hook_suffix, true)) {
            // Enqueue WordPress media scripts.
            
            if (!did_action('wp_enqueue_media')) {
                wp_enqueue_media();
            }

            if (!wp_script_is('wp-i18n', 'registered')) {
                wp_register_script('wp-i18n', esc_url($this->assets_url) . 'js/i18n.min.js', array(), $this->version, true);
            }


            // For backend switcher
            if('toplevel_page_acowcs-switcher' == $screen->id){
                wp_enqueue_editor();
                // Enqueue custom backend script.
                wp_enqueue_script($this->token . '-backend', esc_url($this->assets_url) . 'js/backend.js', array('wp-i18n'), $this->version, true);
                // Localize a script.
                wp_localize_script(
                    $this->token . '-backend',
                    $this->token . '_object',
                    array(
                        'api_nonce' => wp_create_nonce('wp_rest'),
                        'root' => rest_url($this->token . '/v1/'),
                        'assets_url' => $this->assets_url,
                        'payment_methods' => $this->acowcs_get_payment_methods()
                    )
                );
            }

            // For product tab
            if(in_array($screen->id, array('shop_coupon', 'product'))){
                global $post;
                // Enqueue custom backend script.
                wp_enqueue_script($this->token . '-product', esc_url($this->assets_url) . 'js/product.js', array('wp-i18n'), $this->version, true);
                // Localize a script.
                wp_localize_script(
                    $this->token . '-product',
                    $this->token . '_object',
                    array(
                        'api_nonce' => wp_create_nonce('wp_rest'),
                        'root' => rest_url($this->token . '/v1/'),
                        'assets_url' => $this->assets_url,
                        'post_id' => $post->ID
                    )
                );
            }



        }
    }

    /**
     * Deactivation hook
     */
    public function deactivation()
    {
        $this->wpdb->query( $this->wpdb->prepare( "DELETE FROM ".$this->wpdb->options." WHERE `option_name` LIKE %s", '%_ccod'));
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
