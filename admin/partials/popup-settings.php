<div class="postbox ls-tab-form" style="max-width: 1000px; padding: 20px;">
    <h2 class="wp-heading-inline"><?php _e( 'Design Settings', 'license-sender' ); ?></h2>
    <div id="ls-description">
        <p><?php _e( 'These settings allow you to customize PopUp', 'license-sender' ); ?></p>
    </div>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('ls_save_settings_nonce'); ?>
        <input type="hidden" name="action" value="ls_save_settings">
        <input type="hidden" name="tab" value="popup">


        <table class="form-table">
          
          <?php
          $sw_title = esc_attr( get_option('ls_sw_confirm_title', __('Get license keys?', 'license-sender')) );
          $sw_text  = esc_attr( get_option('ls_sw_confirm_text',  __('We will fetch your license keys for {product}. Continue?', 'license-sender')) );
          ?>
          <tr>
            <th scope="row"><label for="ls_sw_confirm_title"><?php esc_html_e('Confirm Popup Title', 'license-sender'); ?></label></th>
            <td>
              <input id="ls_sw_confirm_title" name="ls_sw_confirm_title" type="text" class="regular-text"
                     value="<?php echo $sw_title; ?>"
                     placeholder="<?php esc_attr_e('Get license keys?', 'license-sender'); ?>" />
              <p class="description">
                <?php esc_html_e('Shown as the SweetAlert title before fetching keys.', 'license-sender'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="ls_sw_confirm_text"><?php esc_html_e('Confirm Popup Text', 'license-sender'); ?></label></th>
            <td>
              <input id="ls_sw_confirm_text" name="ls_sw_confirm_text" type="text" class="regular-text"
                     value="<?php echo $sw_text; ?>"
                     placeholder="<?php esc_attr_e('We will fetch your license keys for {product}. Continue?', 'license-sender'); ?>" />
              <p class="description">
                <?php esc_html_e('Use {product} to inject the product name, e.g. “for {product}”. If omitted, the product name will be appended automatically.', 'license-sender'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"></th>
            <td><?php submit_button(__('Save Settings', 'license-sender')); ?></td>
          </tr>
        </table>

    </form>
</div>
