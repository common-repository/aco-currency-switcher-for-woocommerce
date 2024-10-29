<div class="SwitcherWrap">
                    <div class="switcherBody">
                        <div>    
                        <h4> <?php _e('Currency Switcher Fixed Product Price', 'aco-currency-switcher'); ?></h4>
                            <div class="itembodyWrap">
                                <button class="add" onClick="add_new_item">
                                    <span class="dashicons dashicons-plus-alt2">
                                        <?php _e('Add new currencies.', 'aco-currency-switcher'); ?>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <br/>
                        <br/>
                        <hr />


                       
                        <!-- Based on user Rold -->
                        <div>    
                        <h4> <?php _e('Currency Switcher Fixed Price based on User Role', 'aco-currency-switcher'); ?></h4>
                                        <div class="itembodyWrap">
                                            <button class="add" onClick="add_new_item('fixed_userrole_price')" >
                                                <span class="dashicons dashicons-plus-alt2">
                                                    <?php _e('Add new currencies for user role.', 'aco-currency-switcher'); ?>                                                                
                                                </span>
                                            </button>
                                        </div>
                                        
                                                    <button 
                                                        class="saveBtn"
                                                        onClick="saveHandler"
                                                        >
                                                            <span class="dashicons dashicons-cloud-saved"></span>&nbsp;
                                                                <?php _e('Save', 'aco-currency-switcher'); ?>
                                                    </button>
                                                
                                    </div>

                    </div>
            </div>