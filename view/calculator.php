<?php
    $class = '';
    if(is_product())
      $class = 'product';
    if(is_cart())
      $class= 'cart';
    
    $curriences = isset($this->acowcs_settings['curriencies']) && count($this->acowcs_settings['curriencies']) > 0 ? $this->acowcs_settings['curriencies'] : array();
 

    $currencies     = get_woocommerce_currencies();
    $symbols        = ACOWCS_Helper()->wcowcs_get_symbols_set();
    $symbols = array_map(function($v){
        $filter = preg_replace("/\([^)]+\)/","", $v); // 'ABC '
        $filter = str_replace(' ', '', $filter);
        return html_entity_decode($filter);
    }, $symbols);

    $options = '';
    foreach($curriences as $k => $single):
        $img = isset( $single['flag'] ) ? $this->assets_url . 'images/flags/' . strtolower($single['flag']) . '.svg' : '';
        $options .= '<option data-image="'. esc_url($img) .'" value="'. esc_attr($single['currency']) .'">'. esc_attr($symbols[$single['currency']]) .' - '. esc_attr($single['currency']) .'</option>';
    endforeach;
    
    ?>
<div id="<?php echo esc_attr($this->token) . '_calculator'; ?>" class="<?php esc_attr_e( $class ); ?>">
    <div class="loader" style="width:0;">
        <img src="<?php echo esc_url( $this->assets_url ); ?>images/loader.svg" alt="<?php _e('Acoweb Calculator', 'aco-currency-switcher'); ?>">
    </div>

    <div class="CalculatorWrap">            
        <!-- Error -->
        <div class="error" id="currencyError">
            <span></span>
        </div>
        
        <div class="calculatorBody">
            <div>
                <div class="form-group">
                    <label for="converted_amount"><?php _e('Amount', 'aco-currency-switcher'); ?></label>
                    <input onInput="handleUpdate(this);" type="number" name="converted_amount" id="converted_amount" placeholder="<?php _e('Calculate Currency', 'aco-currency-switcher'); ?>">
                </div>
            </div>
            <div class="mt-3 currencyselect-group">
                <div class="currency-selection">
                    <select onChange="handleUpdate(this);" name="currency_from" class="currency_select2" id="currency_from">
                        <option data-image="" value=""><?php _e('Currency', 'aco-currency-switcher'); ?></option>
                        <?php printf($options); ?>
                    </select>
                </div>
                
                <div>
                    <span class="arrow_icon"></span>
                </div>
                <div class="currency-selection">
                    <select onChange="handleUpdate(this);" name="currency_to" class="currency_select2" id="currency_to">
                        <option data-image="" value=""><?php _e('Currency', 'aco-currency-switcher'); ?></option>
                        <?php printf($options); ?>
                    </select>
                </div>                 
            </div>
            <div id="calculateBtn">
                <button id="colculateProcessBtn" disabled onClick="calculateCurrency(this);">
                    <img src="<?php echo esc_url($this->assets_url); ?>images/refresh.svg" alt="<?php _e('Refresh', 'aco-currency-switcher'); ?>" srcset="<?php echo esc_url($this->assets_url); ?>images/refresh.svg">
                    <span><?php _e('Convert', 'aco-currency-switcher'); ?></span>
                </button>    
            </div>
            <div>        
                <div id="calculator_result" class="result"><strong>0.00</strong></div>
            </div>
        </div>
    </div>
</div>