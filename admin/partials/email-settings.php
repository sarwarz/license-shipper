<div class="postbox ls-tab-form" style="max-width: 1000px; padding: 20px;">
    <h2 class="wp-heading-inline"><?php _e('E-Mail Settings', 'license-shipper'); ?></h2>
    <div id="ls-description">
        <p><?php _e('Configure the email settings used for license delivery. These settings control the sender name and email, subject lines for emails, and the recipient.', 'license-shipper'); ?></p>
    </div>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('ls_save_settings_nonce'); ?>
        <input type="hidden" name="action" value="ls_save_settings">
        <input type="hidden" name="tab" value="email">

        <table class="form-table">
            <tr>
                <th>
                    <label for="lship_email_send_mode"><?php _e('Email Send Mode', 'license-shipper'); ?></label>
                </th>
                <td>
                    <?php
                    $current_mode = get_option('lship_email_send_mode', 'after_all'); // Default: after_all
                    ?>
                    <select name="lship_email_send_mode" id="lship_email_send_mode">
                        <option value="after_each" <?php selected($current_mode, 'after_each'); ?>>
                            <?php _e('Send after each product license is fetched', 'license-shipper'); ?>
                        </option>
                        <option value="after_all" <?php selected($current_mode, 'after_all'); ?>>
                            <?php _e('Send once after all products are fetched', 'license-shipper'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose when to send license delivery emails. “After Each” sends immediately after each product license fetch, while “After All” waits until all ordered products are ready.', 'license-shipper'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th><label for="lship_email_sender_name"><?php _e('Sender Name', 'license-shipper'); ?></label></th>
                <td>
                    <input type="text" name="lship_email_sender_name" id="lship_email_sender_name" class="regular-text" value="<?php echo esc_attr(get_option('lship_email_sender_name')); ?>">
                    <p class="description"><?php _e('This name will appear as the sender in outgoing emails to customers.', 'license-shipper'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="lship_email_sender_email"><?php _e('Sender Email Address', 'license-shipper'); ?></label></th>
                <td>
                    <input type="email" name="lship_email_sender_email" id="lship_email_sender_email" class="regular-text" value="<?php echo esc_attr(get_option('lship_email_sender_email')); ?>">
                    <p class="description"><?php _e('The email address that will appear as the sender in customer emails. Leave blank to use the default site email or SMTP sender.', 'license-shipper'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="lship_email_subject"><?php _e('E-Mail Subject (Customer)', 'license-shipper'); ?></label></th>
                <td>
                    <input type="text" name="lship_email_subject" id="lship_email_subject" class="regular-text" value="<?php echo esc_attr(get_option('lship_email_subject')); ?>">
                    <p class="description"><?php _e('Subject line used when sending license keys to customers after voucher redemption.', 'license-shipper'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ls_test_email"><?php _e('Send test e-mail to', 'license-shipper'); ?></label></th>
                <td>
                    <input type="email" name="ls_test_email" id="ls_test_email" class="regular-text" value="<?php echo esc_attr(get_option('admin_email')); ?>">
                    <button style="margin-top: 5px" type="button" class="button" id="ls_send_test_email"><?php _e('Send', 'license-shipper'); ?></button>
                    <p class="description" id="ls_test_email_result"><?php _e('A test email will be sent using the current email settings.', 'license-shipper'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"></th>
                <td>
                    <?php submit_button(__('Save Settings', 'license-shipper')); ?>
                </td>
            </tr>
        </table>
    </form>
</div>
