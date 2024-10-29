<?php
    $position = isset($this->acowcs_settings['curreicny_bar_position']) && $this->acowcs_settings['curreicny_bar_position'] != '' ? $this->acowcs_settings['curreicny_bar_position'] : 'left';
    if(isset($this->acowcs_settings['currency_bar_style'])) $position .= ' '. $this->acowcs_settings['currency_bar_style'];
    
    $color = isset($this->acowcs_settings['currency_bar_main_color']) && $this->acowcs_settings['currency_bar_main_color'] != '' ? $this->acowcs_settings['currency_bar_main_color'] : 'white';
    $bgcolor = isset($this->acowcs_settings['currency_bar_bg_color']) && $this->acowcs_settings['currency_bar_bg_color'] != '' ? $this->acowcs_settings['currency_bar_bg_color'] : 'rgba(0, 0, 0, 0.8)';
    
    $hoverBgColor = 'rgba(0, 0, 0, 0.3)';
    if(isset($this->acowcs_settings['currency_bar_hover_bg_color']) && $this->acowcs_settings['currency_bar_hover_bg_color'] != '')
        $hoverBgColor = $this->acowcs_settings['currency_bar_hover_bg_color'];    

    $hoverColor = '#fff';
    if(isset($this->acowcs_settings['currency_bar_hover_color']) && $this->acowcs_settings['currency_bar_hover_color'] != '')
        $hoverColor = $this->acowcs_settings['currency_bar_hover_color'];    
        

    $position = isset($this->acowcs_settings['curreicny_bar_position']) && $this->acowcs_settings['curreicny_bar_position'] != '' ? $this->acowcs_settings['curreicny_bar_position'] : 'left';
    wp_add_inline_style(
        $this->token . '-switcherCSS', 
        '
            div#acowcs_switcher .SwitcherWrap .switcherBody nav ul li{
                background-color: '.$bgcolor.';
                color: '.$color.';
            }
            #acowcs_switcher .SwitcherWrap .switcherBody nav ul li:not(.loadmore):before{
                background-color: '.$hoverBgColor.';
            }
            #acowcs_switcher .SwitcherWrap .switcherBody nav ul li:hover > span.flag,
            #acowcs_switcher .SwitcherWrap .switcherBody nav ul li.active > span.flag{
                border-color: '.$hoverColor.';
            }
            #acowcs_switcher .SwitcherWrap .switcherBody nav ul li:not(.loadmore):hover,
            #acowcs_switcher .SwitcherWrap .switcherBody nav ul li.active{
                color: '.$hoverColor.';
            }
        '
    );
    $symbols  = ACOWCS_Helper()->wcowcs_get_symbols_set();
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
    
    $show_currency_code = true;
    if(isset($this->acowcs_settings['currency_bar_style']) && in_array($this->acowcs_settings['currency_bar_style'], array('symbol', 'flag')))
        $show_currency_code = false;

    
    $collepse = false;
    $showcaseCurrency = $curriencies = array();
    $curriencies = $this->acowcs_settings['curriencies']; 

    if($this->version_type == 'free')
        $curriencies = array_slice($curriencies, 0, 2);
    

    if(isset($this->acowcs_settings['enable_collepse']) && $this->acowcs_settings['enable_collepse'] != '' && isset($this->acowcs_settings['collepse_after']) && $this->acowcs_settings['collepse_after'] != '' && $this->version_type != 'free'){
        $curriencies = array_slice($this->acowcs_settings['curriencies'], 0, $this->acowcs_settings['collepse_after']);
        $showcaseCurrency = array_slice($this->acowcs_settings['curriencies'], $this->acowcs_settings['collepse_after']);
        if(count($showcaseCurrency) > 0)
            $collepse = true;
    }
    $dyClass = $position; 
    $dyClass .= isset($this->acowcs_settings['currency_bar_style']) ? ' '. $this->acowcs_settings['currency_bar_style'] : '';

?>
<div id="<?php esc_attr_e( $this->token ); ?>_switcher" class="<?php esc_attr_e( $dyClass ); ?>">
    <div class="SwitcherWrap">
        <div class="switcherBody">
            <!-- Drop Down Style -->
            <nav>   
                    <?php if(isset($this->acowcs_settings['currency_bar_title']) && $this->acowcs_settings['currency_bar_title'] != ''): ?>
                    <h5><?php esc_attr_e( $this->acowcs_settings['currency_bar_title'] ); ?></h5>
                    <?php endif; ?>
                <ul>
                    <?php if(isset($this->acowcs_settings['curriencies']) && is_array($this->acowcs_settings['curriencies']) && count($this->acowcs_settings['curriencies']) > 0): 
                        if(isset($this->acowcs_settings['currencis_location_settings']) && count($this->acowcs_settings['currencis_location_settings']) > 0)
                            $enable_desable = ACOWCS_Helper()->get_geo_location_settings($this->acowcs_settings['currencis_location_settings']);
                            if(isset($this->acowcs_settings['hidecurrencyusrerole']) && count($this->acowcs_settings['hidecurrencyusrerole']) > 0)
                                $hidecurrencyUserRole = $this->acowcs_hide_currency_user_role($this->acowcs_settings['hidecurrencyusrerole']);
                    ?>
                        <?php foreach($curriencies as $k => $currency): 
                            if(isset($currency['disable_currency']) && $currency['disable_currency']) 
                                continue;


                        $flagUrl = '';
                        if($show_flag)
                            if(isset($currency['flag'])) $flagUrl = $this->assets_url . 'images/flags/' . strtolower($currency['flag']) . '.svg';
                            
                        
                        $currencyClass = $this->current_currency['currency'] == $currency['currency'] ? 'active' : '';
                        if(isset($this->acowcs_settings['show_switcher_flug']) && $this->acowcs_settings['show_switcher_flug'] != '') $currencyClass .= ' flag';
                        if(isset($this->acowcs_settings['currency_bar_style']) && $this->acowcs_settings['currency_bar_style'] != '') $currencyClass .= ' ' . $this->acowcs_settings['currency_bar_style'];
                        

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
                        class="<?php esc_attr_e( $currencyClass ); ?>"
                        onClick="acowcs_change_curriences('<?php esc_attr_e( $currency['currency'] ); ?>');"
                        >
                        <?php if($position == 'right'): ?>
                            
                            <span class="flag" 
                                <?php if(!empty($flagUrl)): ?>
                                    style="background-image:url(<?php echo esc_url($flagUrl); ?>);"
                                <?php endif; ?>
                            >
                                <?php if(!empty($flagUrl)): ?>
                                    <img src="<?php echo esc_url($flagUrl); ?>" alt="<?php esc_attr_e( $currency['currency'] );  ?>">
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if($position != 'bottom'):  ?>
                            <?php if($show_currency_code === true): ?>
                                <span class="currency"><?php esc_attr_e( $currency['currency'] ); ?></span>
                            <?php endif; ?>

                            <?php if($show_money_sign === true): ?>
                                <span class="currencySign one">
                                    <?php 
                                        if(isset($currency['symbol']) && isset($symbols[$currency['symbol']])){
                                            esc_attr_e( $symbols[$currency['symbol']]);
                                        }elseif(isset($currency['symbol']) && $currency['symbol'] != ''){
                                            esc_attr_e( $currency['symbol'] ); 
                                        }else{
                                            esc_attr_e( html_entity_decode(get_woocommerce_currency_symbol()) );
                                        }
                                    ?>                                                             
                                </span>
                            <?php endif; ?>

                        <?php endif; ?>
                        <?php if($position != 'right'): ?>
                            
                            <span class="flag" 
                                <?php if($flagUrl != ''): ?>
                                    style="background-image:url(<?php echo esc_url($flagUrl); ?>);"
                                <?php endif; ?>
                            >
                                <?php if($flagUrl != ''): ?>
                                    <img src="<?php echo esc_url($flagUrl); ?>" alt="<?php esc_attr_e( $currency['currency'] );  ?>">
                                <?php endif; ?>
                                <?php if($this->acowcs_settings['currency_bar_style'] == 'symbol') esc_attr_e( $currency['currency'] );  ?>
                            </span>
                            
                        <?php endif; ?>

                        <?php if($position == 'bottom'):  ?>
                            <span class="currencySign">
                                <?php 
                                    if(isset($currency['symbol']) && isset($symbols[$currency['symbol']])){
                                        esc_attr_e( $symbols[$currency['symbol']] );
                                    }elseif(isset($currency['symbol']) && $currency['symbol'] != ''){
                                        esc_attr_e($currency['symbol']); 
                                    }else{
                                        esc_attr_e( html_entity_decode(get_woocommerce_currency_symbol()) );
                                    }
                                ?>                                                             
                            </span>
                            <span class="currency"><?php esc_attr_e( $currency['currency'] ); ?></span>
                        <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                        <?php if($collepse): ?>
                        <li class="loadmore" onClick="lomoreHandler(this);">
                            <img src="<?php echo esc_url($this->assets_url) . 'images/more_black.svg'; ?>" alt="<?php _e('Load More', 'aco-currency-switcher'); ?>" />
                        </li>
                        <?php endif; ?>
                    <?php endif; ?> 
                </ul>
            </nav>
            </div>          
        </div>
    </div>


<!-- Lad more -->
<?php if($collepse && count($showcaseCurrency) > 0): ?>
    <div class="moreWindow">
        <div class="loadmoreInner">
            <nav>
                    <ul>
                        <?php foreach($showcaseCurrency as $s => $currency): 
                            if(isset($currency['disable_currency']) && $currency['disable_currency']) 
                                continue;
                                
                        $flagUrl = '';
                        if($show_flag && isset($currency['flag'])) $flagUrl = $this->assets_url . 'images/flags/' . strtolower($currency['flag']) . '.svg';
                        
                        $currencyClass = $this->current_currency == $currency['currency'] ? 'active' : '';
                        if(isset($this->acowcs_settings['show_switcher_flug']) && $this->acowcs_settings['show_switcher_flug'] != '') $currencyClass .= ' flag';
                        if(isset($this->acowcs_settings['currency_bar_style']) && $this->acowcs_settings['currency_bar_style'] != '') $currencyClass .= $this->acowcs_settings['currency_bar_style'];
                        

                        if(isset($this->acowcs_settings['hidecurrencyusrerole']) && count($this->acowcs_settings['hidecurrencyusrerole']) > 0){
                            $hidecurrencyUserRole = $this->acowcs_hide_currency_user_role($this->acowcs_settings['hidecurrencyusrerole']);
                            if(isset($hidecurrencyUserRole[$currency['currency']]) && in_array($this->user_roles[0], $hidecurrencyUserRole[$currency['currency']]) ){
                                continue;
                            }
                        }
                        
                        if(isset($enable_desable) && isset($enable_desable[$currency['currency']])){
                            // Desable Lists
                            if(isset($enable_desable[$currency['currency']]['disable']) && in_array($this->current_user_country_code, $enable_desable[$currency['currency']]['disable']))
                                continue;
                            
                            // Enable lists
                            if(isset($enable_desable[$currency['currency']]['enable']) && !in_array($this->current_user_country_code, $enable_desable[$currency['currency']]['enable']))
                                continue;
                        }
                        ?>
                            <li class="<?php esc_attr_e( $currencyClass ); ?>" onClick="acowcs_change_curriences('<?php esc_attr_e( $currency['currency'] ); ?>');">
                                <?php if($show_flag && isset($currency['flag']) && $currency['flag'] != ''): ?>
                                    <p class="flag">
                                            <img src="<?php echo esc_url($flagUrl); ?>" alt="<?php esc_attr_e( $currency['currency'] ); ?>" />
                                    </p>
                                <?php endif; ?>
                                <?php if($show_money_sign): ?>
                                    <span class="currencySign">
                                        <?php 
                                            if(isset($currency['symbol']) && isset($symbols[$currency['symbol']])){
                                                esc_attr_e( $symbols[$currency['symbol']] );
                                            }elseif(isset($currency['symbol']) && $currency['symbol'] != ''){
                                                esc_attr_e( $currency['symbol'] ); 
                                            }else{
                                                echo html_entity_decode(get_woocommerce_currency_symbol());
                                            }
                                        ?>
                                    </span>
                                <?php endif; ?>
                                    <p class="currency"><?php esc_attr_e( $currency['currency'] ); ?></p>
                            </li>
                        <?php endforeach; ?>                
                    </ul>
                </nav>
                <a onClick="hideSwitcherShowcase(this);" class="close" href="#">
                    <img src="<?php echo esc_url( $this->assets_url ) . 'images/cancel.svg'; ?>" alt="<?php _e('Close', 'aco-currency-switcher'); ?>" />
                </a>
        </div>
    </div>
<?php endif; ?>
