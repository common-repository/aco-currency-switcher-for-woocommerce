<?php
    $currencies     = get_woocommerce_currencies();
    $symbols        = ACOWCS_Helper()->wcowcs_get_symbols_set();
    
?>

<div id="<?php esc_attr_e($this->token); ?>_widget" class="shortcode">
<div class="SwitcherWrap">
    <div class="switcherBody">
    <?php if(isset($args['style']) && $args['style'] == 'dropdown'): ?>
          <div class="form-group">
            <select onChange="acowcs_change_curriences(this);" name="acowcs_currency_change" id="acowcs_currency_change" class="form-control">
              <?php 
                if(isset($this->acowcs_settings['curriencies']) && is_array($this->acowcs_settings['curriencies'])):
                  if(isset($this->acowcs_settings['hidecurrencyusrerole']) && count($this->acowcs_settings['hidecurrencyusrerole']) > 0)
                    $hidecurrencyUserRole = $this->acowcs_hide_currency_user_role($this->acowcs_settings['hidecurrencyusrerole']);

                  foreach($this->acowcs_settings['curriencies'] as $k => $currency): 
                    if(isset($currency['disable_currency']) && $currency['disable_currency']) 
                      continue;

                    if(isset($this->acowcs_settings['hidecurrencyusrerole']) && count($this->acowcs_settings['hidecurrencyusrerole']) > 0){
                      if(isset($hidecurrencyUserRole[$currency['currency']]) && in_array($this->user_roles[0], $hidecurrencyUserRole[$currency['currency']]) ){
                          continue;
                      }
                    }
                  
                    if(isset($enable_desable) && isset($enable_desable[$currency['currency']])){
                        // Desable Lists
                        if(isset($enable_desable[$currency['currency']]['disable']) && in_array($this->current_user_country_code, $enable_desable[$currency['currency']]['disable']))
                            continue;
                        
                        // Enable lists
                        if(isset($enable_desable[$currency['currency']]['enable']) && count($enable_desable[$currency['currency']]['enable']) > 0 && !in_array($this->current_user_country_code, $enable_desable[$currency['currency']]['enable']))
                            continue;
                    }
                    $selected = $this->current_currency['currency'] == $currency['currency'] ? 'selected' : '';
                    ?>
                    <option <?php esc_attr_e($selected); ?> value="<?php esc_attr_e($currency['currency']); ?>"><?php echo esc_attr(html_entity_decode($symbols[$currency['currency']])) . ' - ' . esc_attr($currencies[$currency['currency']]); ?></option>
                    <?php
                  endforeach;
                endif;
              ?>
            </select>
          </div>          

        <?php else: 
          $color = isset($this->acowcs_settings['currency_bar_main_color']) && $this->acowcs_settings['currency_bar_main_color'] != '' ? $this->acowcs_settings['currency_bar_main_color'] : 'white';
          $bgcolor = isset($this->acowcs_settings['currency_bar_bg_color']) && $this->acowcs_settings['currency_bar_bg_color'] != '' ? $this->acowcs_settings['currency_bar_bg_color'] : 'rgba(0, 0, 0, 0.8)';
          
          $symbols = array_map(function($v){
              $filter = preg_replace("/\([^)]+\)/","", $v); // 'ABC '
              $filter = str_replace(' ', '', $filter);
              return html_entity_decode($filter);
          }, $symbols);
      
          
          $show_flag = false;
          if(isset($this->acowcs_settings['currency_bar_style']) && in_array($this->acowcs_settings['currency_bar_style'], array('flag', 'flag_currency', 'flag_currency_symbol')))
              $show_flag = true;
          if((!isset($this->acowcs_settings['currency_bar_style']) || isset($this->acowcs_settings['currency_bar_style']) && $this->acowcs_settings['currency_bar_style'] == 'default')  && isset($this->acowcs_settings['show_switcher_flug']) && $this->acowcs_settings['show_switcher_flug'] != '')
              $show_flag = true;
          
          $show_money_sign = false;
          if(isset($this->acowcs_settings['currency_bar_style']) && in_array($this->acowcs_settings['currency_bar_style'], array('symbol', 'flag_currency_symbol')))
              $show_money_sign = true;    
          if((!isset($this->acowcs_settings['currency_bar_style']) || isset($this->acowcs_settings['currency_bar_style']) && $this->acowcs_settings['currency_bar_style'] == 'default') && isset($this->acowcs_settings['show_money_sign']) && $this->acowcs_settings['show_money_sign'] != '')
              $show_money_sign = true;  
          
          ?>
          <nav>   
                <ul>
                    <?php if(isset($this->acowcs_settings['curriencies']) && is_array($this->acowcs_settings['curriencies']) && count($this->acowcs_settings['curriencies']) > 0): 
                        if(isset($this->acowcs_settings['currencis_location_settings']) && count($this->acowcs_settings['currencis_location_settings']) > 0)
                            $enable_desable = ACOWCS_Helper()->get_geo_location_settings($this->acowcs_settings['currencis_location_settings']);
                            if(isset($this->acowcs_settings['hidecurrencyusrerole']) && count($this->acowcs_settings['hidecurrencyusrerole']) > 0)
                                $hidecurrencyUserRole = $this->acowcs_hide_currency_user_role($this->acowcs_settings['hidecurrencyusrerole']);
                    ?>
                        <?php foreach($this->acowcs_settings['curriencies'] as $k => $currency): 
                          if(isset($currency['disable_currency']) && $currency['disable_currency']) 
                            continue;
                            
                        $flagUrl = '';
                        if($show_flag && isset($currency['flag'])) $flagUrl = $this->assets_url . 'images/flags/' . strtolower($currency['flag']) . '.svg';
                        
                        $currencyClass = $this->current_currency['currency'] == $currency['currency'] ? 'active' : '';
                        if(isset($this->acowcs_settings['show_switcher_flug']) && $this->acowcs_settings['show_switcher_flug'] != '') $currencyClass .= ' flag';
                        if(isset($this->acowcs_settings['currency_bar_style']) && $this->acowcs_settings['currency_bar_style'] != '') $currencyClass .= ' ' .$this->acowcs_settings['currency_bar_style'];
                        

                        if(isset($this->acowcs_settings['hidecurrencyusrerole']) && count($this->acowcs_settings['hidecurrencyusrerole']) > 0){
                            if(isset($hidecurrencyUserRole[$currency['currency']]) && in_array($this->user_roles[0], $hidecurrencyUserRole[$currency['currency']]) ){
                                continue;
                            }
                        }
                        
                        if(isset($enable_desable) && isset($enable_desable[$currency['currency']])){
                            // Desable Lists
                            if(isset($enable_desable[$currency['currency']]['disable']) && in_array($this->current_user_country_code, $enable_desable[$currency['currency']]['disable']))
                                continue;
                            
                            // Enable lists
                            if(isset($enable_desable[$currency['currency']]['enable']) && count($enable_desable[$currency['currency']]['enable']) > 0 && !in_array($this->current_user_country_code, $enable_desable[$currency['currency']]['enable']))
                                continue;
                        }
                        
                        ?>
                        <li
                        style="overflow:hidden; max-height:40px; color:<?php esc_attr_e( $color ); ?>; background-color: <?php esc_attr_e( $bgcolor ); ?>"
                        class="<?php esc_attr_e( $currencyClass ); ?>"
                        onClick="acowcs_change_curriences('<?php esc_attr_e($currency['currency']); ?>');"
                        >
                          <span class="currency"><?php esc_attr_e($currency['currency']); ?></span>
                          <span class="currencySign">
                              <?php 
                                  if(isset($currency['symbol']) && isset($symbols[$currency['symbol']])){
                                    esc_attr_e($symbols[$currency['symbol']]);
                                  }elseif(isset($currency['symbol']) && $currency['symbol'] != ''){
                                    esc_attr_e($currency['symbol']); 
                                  }else{
                                    esc_attr_e(html_entity_decode(get_woocommerce_currency_symbol()));
                                  }
                              ?>                                                             
                          </span>
                        
                          <span class="flag" 
                          <?php if(!empty($flagUrl)): ?>
                            style="background-image:url(<?php echo esc_url($flagUrl); ?>);"
                          <?php endif; ?>
                          >
                              <?php if(!empty($flagUrl)): ?>
                                <img style="opacity:0;" src="<?php echo esc_url($flagUrl); ?>" alt="<?php esc_attr_e($currency['currency']);  ?>">
                              <?php endif; ?>
                          </span>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?> 
                </ul>
          </nav>
        <?php endif; ?>

    </div>
</div>
</div>

<script>
  jQuery(document).ready(function(){
    setTimeout(function () {
      let is_currency_change = localStorage.getItem('is_currency_change');
      if(is_currency_change){
        jQuery(document.body).trigger('wc_fragment_refresh');
        localStorage.removeItem("is_currency_change");
      }
    }, 500);
  });
</script>