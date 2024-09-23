<div class="row">
    <div class="col-sm-8">

        <div class="consistent-box">
            <h4 class="mb-4">
                <i class="fa-regular fa-address-card"></i>
                <?php _e( 'Identity Settings', 'myagileprivacy' ); ?>
            </h4>

            <div class="row mb-5">
                <div class="col-sm-12">
                <?php _e( 'Here you can enter the company details or the site older informations. This is used for populating the privacy data controller section.', 'myagileprivacy' ); ?>
                </div>
            </div>

            <!-- website name -->
            <div class="row mb-4">
                <label for="website_name_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Domain list', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <textarea class="form-control" rows="6" id="website_name_field" name="website_name_field"><?php echo esc_attr(stripslashes($the_options['website_name']))  ?></textarea>

                    <div class="form-text">
                        <?php _e( 'You can enter multiple domain linked to this website.', 'myagileprivacy' ); ?>
                    </div>
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->


            <!-- company name -->
            <div class="row mb-4">
                <label for="identity_name_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Company Name / Site Holder', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <input type="text" class="form-control" id="identity_name_field" name="identity_name_field" value="<?php echo esc_attr(stripslashes($the_options['identity_name']))  ?>" />

                    <div class="form-text">
                        <?php _e( 'Insert here the name of the company or the person who is the owner of the website.', 'myagileprivacy' ); ?>
                    </div>
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->

            <!-- company address -->
            <div class="row mb-4">
                <label for="identity_address_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Company Address', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <input type="text" class="form-control" id="identity_address_field" name="identity_address_field" value="<?php echo esc_attr(stripslashes($the_options['identity_address']))  ?>" />

                    <div class="form-text">
                        <?php _e( 'Insert here the address of the company or person who owns the website.', 'myagileprivacy' ); ?>
                    </div>
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->

            <!-- vat id -->
            <div class="row mb-4">
                <label for="identity_vat_id_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Vat Id', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <input type="text" class="form-control" id="identity_vat_id_field" name="identity_vat_id_field" value="<?php echo esc_attr(stripslashes($the_options['identity_vat_id']))  ?>" />

                    <div class="form-text">
                        <?php esc_html_e( 'Leave it blank, if not applicabile', 'myagileprivacy' ); ?>.
                    </div>
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->

            <!-- email -->
            <div class="row mb-4">
                <label for="identity_email_field" class="col-sm-5 col-form-label">
                    <?php _e( 'Company E-mail', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <input type="text" class="form-control" id="identity_email_field" name="identity_email_field" value="<?php echo esc_attr(stripslashes($the_options['identity_email']))  ?>" />

                    <div class="form-text">
                        <?php esc_html_e( 'Insert here the email where the user can contact you for questions about privacy and personal data use', 'myagileprivacy' ); ?>.
                    </div>
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->



        </div> <!-- consistent-box -->

        <span class="translate-middle-y forbiddenWarning badge rounded-pill bg-danger  <?php if( $the_options['pa'] == 1){echo 'd-none';} ?>">
            <small><?php _e( 'Premium Feature','myagileprivacy' ); ?></small>
        </span>

        <div class="consistent-box <?php if( $the_options['pa'] != 1){echo 'forbiddenArea';} ?>">

            <h4 class="mb-4">
                <i class="fa-regular fa-address-card"></i>
                <?php _e( 'DPO settings', 'myagileprivacy' ); ?>
            </h4>

            <div class="row mb-5">
                <div class="col-sm-12">
                <?php _e( 'Here you can enter the DPO data for your organization, if applicable.', 'myagileprivacy' ); ?>
                </div>
            </div>

            <!-- dpo checkbox -->
            <div class="row mb-4">
                <label for="display_dpo_field" class="col-sm-5 col-form-label">
                    <?php _e( 'I have a DPO (Data Protection Officer)', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <div class="styled_radio d-inline-flex">
                        <div class="round d-flex me-4">
                            <input type="hidden" name="display_dpo_field" value="false" id="display_dpo_field_no">

                            <input class="hideShowInput" data-hide-show-ref="display_dpo_field_wrapper" name="display_dpo_field" type="checkbox" value="true" id="display_dpo_field" <?php checked($the_options['display_dpo'], true); ?>>

                            <label for="display_dpo_field" class="me-2 label-checkbox"></label>

                            <label for="display_dpo_field">
                                <?php echo esc_html__( 'Yes, I have a DPO', 'myagileprivacy' ); ?>.
                            </label>

                        </div>
                    </div> <!-- ./ styled_radio -->
                </div> <!-- /.col-sm-6 -->

            </div> <!-- row -->

            <!-- dpo email -->
            <div class="row mb-4 display_dpo_field_wrapper displayNone">
                <label for="dpo_email_field" class="col-sm-5 col-form-label">
                    <?php _e( 'DPO Email', 'myagileprivacy' ); ?> (*)
                </label>

                <div class="col-sm-7">
                    <input type="text" class="form-control" id="dpo_email_field" name="dpo_email_field" value="<?php echo esc_attr(stripslashes($the_options['dpo_email']))  ?>" />

                    <div class="form-text">
                        <?php esc_html_e( 'Insert here the email of your DPO', 'myagileprivacy' ); ?>.
                    </div>
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->


            <!-- dpo name -->
            <div class="row mb-4 display_dpo_field_wrapper displayNone">
                <label for="dpo_email_field" class="col-sm-5 col-form-label">
                    <?php _e( 'DPO Name / Company name', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <input type="text" class="form-control" id="dpo_name_field" name="dpo_name_field" value="<?php echo esc_attr(stripslashes($the_options['dpo_name']))  ?>" />
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->


            <!-- dpo name -->
            <div class="row mb-4 display_dpo_field_wrapper displayNone">
                <label for="dpo_email_field" class="col-sm-5 col-form-label">
                    <?php _e( 'DPO Address', 'myagileprivacy' ); ?>
                </label>

                <div class="col-sm-7">
                    <input type="text" class="form-control" id="dpo_address_field" name="dpo_address_field" value="<?php echo esc_attr(stripslashes($the_options['dpo_address']))  ?>" />
                </div> <!-- /.col-sm-6 -->
            </div> <!-- row -->



        </div> <!-- consistent-box -->
    </div> <!-- /.col-sm-8 -->

    <div class="col-sm-4">
        <?php
            $tab = 'identity';
            include 'inc.admin_sidebar.php';
        ?>
    </div>
</div> <!-- /.row -->