<?php

/**
 * Plugin Name: Aco Currency Switcher for WooCommerce
 * Version: 2.1.3
 * Description: Currency Switcher for WooCommerce Plugin helps to setup multi currency in WooCommerce Store with an easy to use user interfaces.
 * Author: Acowebs
 * Author URI: http://acowebs.com
 * Requires at least: 4.4.0
 * Tested up to: 6.3
 * Text Domain:  aco-currency-switcher-for-woocommerce
 * WC requires at least: 3.0.0
 * WC tested up to: 6.7.0
 */

define('ACOWCS_TOKEN', 'acowcs');
define('ACOWCS_VERSION_TYPE', 'free');
define('ACOWCS_VERSION', '2.1.3');
define('ACOWCS_FILE', __FILE__);
define('ACOWCS_PATH', realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR );
define('ACOWCS_PLUGIN_NAME', 'Aco Currency Switcher for WooCommerce');

// Helpers.
require_once ACOWCS_PATH . 'includes/helpers.php';

// Init.
add_action('plugins_loaded', 'acowcs_init');
if (!function_exists('acowcs_init')) {
    /**
     * Load plugin text domain
     *
     * @return  void
     */
    function acowcs_init()
    {
        $plugin_rel_path = basename(dirname(__FILE__)) . '/languages'; /* Relative to WP_PLUGIN_DIR */
        load_plugin_textdomain('aco-currency-switcher', false, $plugin_rel_path);
    }
}

// Loading Classes.
if (!function_exists('ACOWCS_autoloader')) {

    function ACOWCS_autoloader($class_name)
    {
        if (0 === strpos($class_name, 'ACOWCS')) {
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
            require_once $classes_dir . $class_file;
        }
    }
}
spl_autoload_register('ACOWCS_autoloader');

// Backend UI.
if (!function_exists('ACOWCS_Backend')) {
    function ACOWCS_Backend()
    {
        return ACOWCS_Backend::instance(__FILE__);
    }
}

if (!function_exists('ACOWCS_Public')) {
    function ACOWCS_Public()
    {
        return ACOWCS_Public::instance(__FILE__);
    }
}

if(!function_exists('ACOWCS_Helper')){
    function ACOWCS_Helper(){
        return ACOWCS_Helper::instance(__FILE__);
    }
}


// Front end.
ACOWCS_Public();
if (is_admin()) {   
    ACOWCS_Backend();
}

//API
new ACOWCS_Api();

