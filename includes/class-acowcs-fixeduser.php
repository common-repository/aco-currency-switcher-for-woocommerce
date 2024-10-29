<?php

if (!defined('ABSPATH'))
    die('No direct access allowed');

class ACOWCS_Fixeduser {

    public function __construct() {}

    public function get_value($post_id, $currency = false, $roles = array(), $price_type = 'regular'){
        $price = false;
        $variation_id = false;
        if(get_post_type( $post_id ) == 'product_variation'){
            $variation_id = $post_id;
            $post_id = wp_get_post_parent_id($post_id);
        }


        if(!$currency) 
            return false;

        $fixed_price = get_post_meta( $post_id, 'fixed_userrole_price', true );

        if(!$fixed_price)
            return false;

            

        $product = wc_get_product($post_id);
        if($product->get_type() == 'variable'){
            $new_fixed_price = array();
            foreach($fixed_price as $single){
                if(isset($single['variable']) && $single['variable'] != '' && isset($single['currency']) && $single['variable'] == $variation_id && $single['currency'] == $currency ){
                    if(!array_key_exists($single['variable'], $new_fixed_price)){
                        array_push($new_fixed_price, $single);
                    }        
                }
            }    
        $fixed_price = $new_fixed_price;
        }
        
        $key_search = array_search($currency, array_column($fixed_price, 'currency'));
        
        
        if($key_search === false)
            return false;
        
        
        $currency_roles = array_map(function($v){
            return $v['name'];
        }, $fixed_price[$key_search]['userroles']);

        
        $role_exists = !empty(array_intersect($roles, $currency_roles));
        if(!$role_exists)
            return false;

            
        if($price_type == 'regular' && isset($fixed_price[$key_search]['regular_price']))
            $price = $fixed_price[$key_search]['regular_price'];

        if($price_type == 'sale' && isset($fixed_price[$key_search]['sale_price']))
            $price = $fixed_price[$key_search]['sale_price'];

        return $price;
    }
}