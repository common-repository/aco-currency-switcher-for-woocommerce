<?php  
/**
 * Acowcs_WIDGET
 */
class ACOWCS_Widget extends WP_Widget {
     /**
     * @var     array
     * @access  private
     */
    private $acowcs_settings = array();

    /**
     * @var     array
     * @access  protected
     * 
     */
    protected $user_roles;

    /**
     * The token.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    protected $token;

    /** 
     * @var     string
     * @access  private
    */
    private $current_user_country_code = '';

    /**
     * The plugin assets URL.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

       /**
     * @var     string
     * @access  private
     * @since   1.0.0
     */
    private $current_currency;

    /**
     * @var     string
     * @access  private
     */
    private $currency_storage_to = '';

    /**
     * @var     string
     * @access  private
     */
    private $get_client_ip = '';

    // The construct part  
    function __construct() {
        $this->set_initial_config();
        $this->token = ACOWCS_TOKEN;
        $this->file = ACOWCS_FILE;
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));
        
        parent::__construct(
  
            // Base ID of your widget
            'acowcs_currency_switcher', 
              
            // Widget name will appear in UI
            __('Acowcs Currency Switcher', 'aco-currency-switcher'), 
              
            // Widget description
            array( 'description' => __( 'Acowcs Multi-currency Switcher.', 'aco-currency-switcher' ), ) 
        );
    }
      
    
    /**
     * @access  private
     * @return  initial settings
     */
    private function acowcs_set_user_location(){
        $ip_address = ACOWCS_Helper()->get_client_ip(); 
        $ip_replace = str_replace('.', '', $ip_address);
        if(get_option( $ip_replace.'_ccod' ))
            $this->current_user_country_code = get_option( $ip_replace.'_ccod' );

        if(!get_option( $ip_replace.'_ccod' )){
            if(isset($_COOKIE['user_ccod']) && $_COOKIE['user_ccod'] != ''){
                update_option( $ip_replace.'_ccod', sanitize_text_field( $_COOKIE['user_ccod'] ) );
                $this->current_user_country_code = sanitize_text_field($_COOKIE['user_ccod']);
            }
        }
    }



    /**
     * @param NULL
     * @return NULL
     * @purpose set initial values
     */
    private function set_initial_config(){
     
        $client_ip = ACOWCS_Helper()->get_client_ip();
        $client_ip = str_replace('.', '', $client_ip);

        //User current location 
        $this->acowcs_set_user_location();

        //Visitor IP
        if(is_user_logged_in(  )) $client_ip = get_current_user_id(  );
        $this->get_client_ip = $client_ip;


        $this->acowcs_settings = acowcs_settings();
        $user = wp_get_current_user();
        $this->user_roles = ( array ) $user->roles;
        
        $this->currency_storage_to = isset($this->acowcs_settings['currency_storage_to']) && $this->acowcs_settings['currency_storage_to'] != '' ? $this->acowcs_settings['currency_storage_to'] : 'transient';
        $current_currency = ACOWCS_Helper()->get_current_currency($this->currency_storage_to, $this->get_client_ip);
        $curriencies = isset($this->acowcs_settings['curriencies']) ? $this->acowcs_settings['curriencies'] : array();

        if(count($curriencies) <= 0)
            return;
        
      
        $current_currency_key = array_search($current_currency, array_column($curriencies, 'currency'));

        if($current_currency_key === false){
            $current_currency_key = array_search(1, array_column($curriencies, 'default'));
            $current_currency = $curriencies[$current_currency_key]['currency'];
        }

        if($current_currency && $current_currency_key === false)
            return;

        $this->current_currency         = $curriencies[$current_currency_key];
    }

    // Creating widget front-end
    public function widget( $args, $instance ) {

        if(is_checkout() && isset($this->acowcs_settings['hide_swither_on_checkout']) && $this->acowcs_settings['hide_swither_on_checkout'] != '')
            return false;
    
        

        if(is_user_logged_in(  ) && isset($this->acowcs_settings['show_switcher_on_userrole']) && is_array($this->acowcs_settings['show_switcher_on_userrole']) && is_array( $this->user_roles ) ){
            $swithcer_on_user_role_status = isset($this->acowcs_settings['swithcer_on_user_role_status']) ? $this->acowcs_settings['swithcer_on_user_role_status'] : 'include';
            $show_switcher_on_userrole = array_map(function($s){
                return $s['name'];
            }, $this->acowcs_settings['show_switcher_on_userrole']);

            $role_exists =  !empty(array_intersect($this->user_roles, $show_switcher_on_userrole));

            if(($swithcer_on_user_role_status == 'include' && !$role_exists) || ($swithcer_on_user_role_status == 'exclude' && $role_exists) )
                return false;
        }

        
    
        if(isset($this->acowcs_settings['show_switcher_page']) && is_array($this->acowcs_settings['show_switcher_page'])){
            $status = isset($this->acowcs_settings['show_switcher_status']) && $this->acowcs_settings['show_switcher_status'] != '' ? $this->acowcs_settings['show_switcher_status'] : 'include'; 
            $pages = array_map(function($v){
                return $v['id'];
            }, $this->acowcs_settings['show_switcher_page']); 


            // show_switcher_status
            global $wp_query, $post;
            if(is_shop()){
                if($status == 'include' && !in_array( get_option( 'woocommerce_shop_page_id' ), $pages ) && count($pages) > 0)
                    return false;
                
                if($status == 'exclude' && in_array( get_option( 'woocommerce_shop_page_id' ), $pages ))
                    return false;
                
            }else{
                if($status == 'include' && !in_array($wp_query->get_queried_object_id(), $pages) && count($pages) > 0)
                    return false;
                
                if($status == 'exclude' && in_array($wp_query->get_queried_object_id(), $pages))
                    return false;
            }
        }
        

        wp_enqueue_style( $this->token . '-widgetCSS' );
        // Add JS
        wp_enqueue_script( $this->token . '_switcher' );
        wp_localize_script(
            $this->token . '_switcher',
            $this->token . '_object',
            array(
                'api_nonce' => wp_create_nonce('wp_rest'),
                'root' => rest_url($this->token . '/v1/'),
                'assets_url' => $this->assets_url,
                'user_id' => is_user_logged_in(  ) ? get_current_user_id(  ) : false
            )
        );
        $title = isset($instance['title']) ? $instance['title'] : '';
        $title = apply_filters( 'acowcs_widget_title', $title );
        echo $args['before_widget'];
        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];
            
            // This is where you run the code and display the output
            require_once(ACOWCS_PATH . 'view/acowcs_widget.php');

        echo $args['after_widget'];

    }
              
    // Creating widget Backend 
    public function form( $instance ) {
            $title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : __( 'New title', 'aco-currency-switcher' );
            $style = isset($instance[ 'style' ]) ? $instance[ 'style' ] : 'list';
        
            // Widget admin form
            ?>
            <p>
                <label for="<?php esc_attr_e( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'aco-currency-switcher' ); ?></label> 
                <input class="widefat" id="<?php esc_attr_e($this->get_field_id( 'title' )); ?>" name="<?php  esc_attr_e($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>
            <p>
                <label for="<?php  esc_attr_e($this->get_field_id( 'style' )); ?>"><?php esc_attr_e( 'Display Style:', 'aco-currency-switcher' ); ?></label> 
                <select class="widefat" id="<?php  esc_attr_e($this->get_field_id( 'style' )); ?>" name="<?php  esc_attr_e($this->get_field_name( 'style' )); ?>"  >
                    <option <?php echo $style == 'list' ? esc_attr('selected') : ''; ?> value="list"><?php esc_attr_e('List', 'aco-currency-switcher'); ?></option>
                    <option <?php echo $style == 'dropdown' ? esc_attr('selected') : ''; ?> value="dropdown"><?php esc_attr_e('Dropdown', 'aco-currency-switcher'); ?></option>
                </select>
            </p>
            <?php
    }
          
    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['style'] = ( ! empty( $new_instance['style'] ) ) ? strip_tags( $new_instance['style'] ) : '';
        return $instance;
    }
    // Class ACOWCS_Widget ends here
} 