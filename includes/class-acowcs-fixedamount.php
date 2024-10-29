<?php

if (!defined('ABSPATH'))
    die('No direct access allowed');

class ACOWCS_Fixedamount {

    protected $key = "";

    public function __construct() {}

    public function get_value($post_id, $currency = false, $price_type = 'regular'){
        $price = false;
        
        if(!$currency) 
            return false;

        $fixed_price = get_post_meta( $post_id, 'fixed_currency_price', true );
        if(!$fixed_price)
            return false;

        $key_search = array_search($currency, array_column($fixed_price, 'currency'));
        if($key_search === false)
            return false;

        if($price_type == 'regular' && isset($fixed_price[$key_search]['regular_price']))
            $price = $fixed_price[$key_search]['regular_price'];

        if($price_type == 'sale' && isset($fixed_price[$key_search]['sale_price']))
            $price = $fixed_price[$key_search]['sale_price'];

        return $price;
    }



    public function get_coupon_value($post_id, $currency = false){
        $price = false;
        
        if(!$currency) 
            return false;
        
        $fixed_price = get_post_meta( $post_id, 'fixed_currency_price', true );
        if(!$fixed_price)
            return false;
        
        $key_search = array_search($currency, array_column($fixed_price, 'currency'));
        if($key_search === false)
            return false;
        
        $price = isset($fixed_price[$key_search]['regular_price']) ? $fixed_price[$key_search]['regular_price'] : false;
        if(!$price)
            return false;

        return $price;
    }

}