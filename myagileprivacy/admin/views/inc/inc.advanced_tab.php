<div class="row">
    <div class="col-sm-8">

        <div class="consistent-box">
            <h4 class="mb-4">
                <i class="fa-solid fa-sliders-up"></i>
                <?php _e( 'Advanced Settings', 'myagileprivacy' ); ?>
            </h4>


            <div class="row mb-4">
                <label for="forced_auto_update_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Enable plugin auto update','myagileprivacy' ); ?>
                </label>

                <div class="col-sm-6">

                    <div class="styled_radio d-inline-flex">
                        <div class="round d-flex me-12">

                            <input type="hidden" name="forced_auto_update_field" value="false" id="forced_auto_update_field_no">

                            <input name="forced_auto_update_field" type="checkbox" value="true" id="forced_auto_update_field" <?php checked( $the_options['forced_auto_update'], true); ?>>

                            <label for="forced_auto_update_field" class="me-3 label-checkbox"></label>

                            <label for="forced_auto_update_field">
                                <?php _e( 'Yes, I would like to turn on automatic plugin updates.', 'myagileprivacy' ); ?>
                            </label>
                        </div>
                    </div> <!-- ./ styled_radio -->


                </div>
            </div> <!-- row -->


            <div class="row mb-4">
                <label for="enable_metadata_sync_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Cookie metadata Sync','myagileprivacy' ); ?>
                </label>

                <div class="col-sm-6">

                    <div class="styled_radio d-inline-flex">
                        <div class="round d-flex me-12">

                            <input type="hidden" name="enable_metadata_sync_field" value="false" id="enable_metadata_sync_field_no">

                            <input name="enable_metadata_sync_field" type="checkbox" value="true" id="enable_metadata_sync_field" <?php checked( $the_options['enable_metadata_sync'], true); ?>>

                            <label for="enable_metadata_sync_field" class="me-3 label-checkbox"></label>

                            <label for="enable_metadata_sync_field">
                                <?php _e( 'Yes, enable Cookie metadata synchronization.', 'myagileprivacy' ); ?>
                            </label>
                        </div>
                    </div> <!-- ./ styled_radio -->
                    <div class="form-text">
                        <?php _e( ' By enabling this feature, you will allow for automatic updates of settings related to the preemptive blocking of cookies, and you will achieve greater compliance in case of regulatory adjustments.', 'myagileprivacy' ); ?>
                    </div>

                </div>
            </div> <!-- row -->



            <div class="row mb-4">
                <label for="default_locale_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Language', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">

                    <select id="default_locale_field" name="default_locale_field" class="form-control">
                        <?php

                        $valid_options = array(
                            /*
                            'default_wordpress_locale'	=>	array(  'label' => __( 'Default Wordpress Locale', 'myagileprivacy' ),
                                                                    'selected' => false ),
                            */
                            'en_US'						=>	array(  'label' => __( 'English', 'myagileprivacy' ),
                                                                    'selected' => false ),
                            'it_IT'						=>	array(  'label' => __( 'Italian', 'myagileprivacy' ),
                                                                    'selected' => false ),
                            'fr_FR'						=>	array(  'label' => __( 'French', 'myagileprivacy' ),
                                                                    'selected' => false ),
                            'de_DE'						=>	array(  'label' => __( 'German', 'myagileprivacy' ),
                                                                    'selected' => false ),
                            'es_ES'						=>	array(  'label' => __( 'Spanish', 'myagileprivacy' ),
                                                                    'selected' => false ),
                        );

                        $selected_value = $the_options['default_locale'];

                        if( isset( $valid_options[ $selected_value ] ) )
                        {
                            $valid_options[ $selected_value ]['selected'] = true;
                        }

                        foreach( $valid_options as $key => $data )
                        {
                            if( $data['selected'] )
                            {
                                ?>
                                <option value="<?php echo esc_attr($key)?>" selected><?php echo esc_attr($data['label'])?></option>
                                <?php
                            }
                            else
                            {
                                ?>
                                <option value="<?php echo esc_attr($key)?>"><?php echo esc_attr($data['label'])?></option>
                                <?php
                            }
                        }

                        ?>
                    </select>

                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->


            <!-- wrapping css textarea -->
            <div class="row mb-4">
                <label for="custom_css_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Custom Css', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                
                    <div class="position-relative code-block-container">
                        <textarea id="custom_css_field" name="custom_css_field" class="code-editor text_style" spellcheck="false"><?php echo apply_filters( 'format_to_edit', esc_attr($the_options['custom_css'])); ?></textarea>

                        <pre class="line-numbers code-viewer"><code class="language-css"><?php echo apply_filters( 'format_to_edit', esc_attr($the_options['custom_css'])); ?></code></pre>
                    </div>


        

                    <div class="form-text">
                        <?php _e( 'Enter your custom css', 'myagileprivacy' ); ?>.
                    </div>
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->

            <!-- wrapping css checkbox -->
            <div class="row mb-4">
                <label for="wrap_shortcodes_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Enable policy wrapping for CSS customization purposes', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <div class="styled_radio d-inline-flex">
                        <div class="round d-flex me-4">

                            <input type="hidden" name="wrap_shortcodes_field" value="false" id="wrap_shortcodes_field_no">

                            <input name="wrap_shortcodes_field" type="checkbox" value="true" id="wrap_shortcodes_field" <?php checked($the_options['wrap_shortcodes'], true); ?>>

                            <label for="wrap_shortcodes_field" class="me-2 label-checkbox"></label>

                            <label for="wrap_shortcodes_field">
                                <?php echo esc_html__( 'Enable wrapping', 'myagileprivacy' ); ?>
                            </label>
                        </div>
                    </div> <!-- ./ styled_radio -->
                    <div class="form-text">
                        <?php _e( 'By enabling this feature, the textual content of the policies will become targetable by a CSS selector. The selector is .myagileprivacy_text_wrapper', 'myagileprivacy' ); ?>.
                    </div>

                </div> <!-- /.col-sm-6 -->


            </div> <!-- row -->

            <?php

                $force_jquery_inject_class = "";

                if( !( isset( $rconfig ) &&
                                isset( $rconfig['force_jquery_version'] ) &&
                                $rconfig['force_jquery_version'] == 1 ) )
                {
                     $force_jquery_inject_class = "d-none";
                }

            ?>

            <!-- jquery loading checkbox -->
            <div class="row mb-4 <?php echo esc_attr( $force_jquery_inject_class );?>">
                <label for="client_force_reinject_jquery_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Force jQuery reinject', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <div class="styled_radio d-inline-flex">
                        <div class="round d-flex me-4">

                            <input type="hidden" name="client_force_reinject_jquery_field" value="false" id="client_force_reinject_jquery_field_no">

                            <input name="client_force_reinject_jquery_field" type="checkbox" value="true" id="client_force_reinject_jquery_field" <?php checked($the_options['client_force_reinject_jquery'], true); ?>>

                            <label for="client_force_reinject_jquery_field" class="me-2 label-checkbox"></label>

                            <label for="client_force_reinject_jquery_field">
                                <?php _e( 'Force jQuery reinject', 'myagileprivacy' ); ?>
                            </label>
                        </div>
                    </div> <!-- ./ styled_radio -->

                    <div class="form-text">
                        <?php echo esc_html__( 'Enable this if you have a custom theme with deactivated jQuery, and loading issues.', 'myagileprivacy' ); ?>
                    </div>

                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->


            <!-- force sync checkbox -->
            <div class="row mb-4">
                <label for="force_sync" class="col-sm-5 col-form-label">
                    <?php _e( 'Force Cookies and Policy syncronization', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <div class="styled_radio d-inline-flex">
                        <div class="round d-flex me-4">

                            <input name="force_sync" class="uncheck_on_send" type="checkbox" value="1" id="force_sync">

                            <label for="force_sync" class="me-2 label-checkbox"></label>

                            <label for="force_sync">
                                <?php _e( 'Force Cookies and Policy syncronization', 'myagileprivacy' ); ?>
                            </label>
                        </div>
                    </div> <!-- ./ styled_radio -->

                    <div class="form-text">
                        <?php echo esc_html__( 'This will sync Cookies And Policy in the next five minute', 'myagileprivacy' ); ?>.
                    </div>

                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->

            <!-- reset settings checkbox -->
            <div class="row mb-4">
                <label for="reset_settings" class="col-sm-5 col-form-label">
                    <?php _e( 'Reset all settings', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <div class="styled_radio d-inline-flex">
                        <div class="round d-flex me-4">

                            <input class="uncheck_on_send" name="reset_settings" type="checkbox" value="1" id="reset_settings">

                            <label for="reset_settings" class="me-2 label-checkbox"></label>

                            <label for="reset_settings">
                                <?php _e( 'Reset all settings', 'myagileprivacy' ); ?>
                            </label>
                        </div>
                    </div> <!-- ./ styled_radio -->

                    <div class="form-text">
                        <?php echo esc_html__( 'Warning: this will reset all the plugin settings.', 'myagileprivacy' ); ?>
                    </div>

                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->


        </div> <!-- consistent-box -->
    </div> <!-- /.col-sm-8 -->

    <div class="col-sm-4">
        <?php
            $tab = 'advanced';
            include 'inc.admin_sidebar.php';
        ?>
    </div>
</div> <!-- /.row -->