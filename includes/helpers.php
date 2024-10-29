<?php

/**
 * @param string for specific key
 * @return array with data
 * 
 */
function acowcs_settings($key = false){
    $settings = get_option( 'acowcs_settings', array() );
    
    if($key && count((array)$settings) > 0){
        return isset($settings[$key]) && $settings[$key] != '' ? $settings[$key] : false ;
    }
    return $settings;
}


/**
 * @param NULL
 * @return acowcs default currency
 * 
 */
function acowcs_default_currency($reverse = false){
    $curriencies = acowcs_settings('curriencies');
    $default_key = is_array($curriencies) && count($curriencies) > 0 ? array_search(1, array_column($curriencies, 'default')) : false;
    
    if($reverse && $default_key !== false){
        unset($curriencies[$default_key]);
        $rearrange = array_values($curriencies);
        return $rearrange;
    }
    
    return is_array($curriencies) && count($curriencies) > 0 ? $curriencies[$default_key]['currency'] : false;
}
