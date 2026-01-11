<div class="postbox ls-tab-form" style="max-width: 1000px; padding: 20px;">
    <h2 class="wp-heading-inline"><?php _e( 'Single Sign-On (SSO) Settings', 'license-shipper' ); ?></h2>
     <div id="ls-description">
        <p>Configure secure Single Sign-On access between WordPress and your LicenseShipper application.</p>
    </div>
    <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('ls_save_settings_nonce'); ?>
        <input type="hidden" name="action" value="ls_save_settings">
        <input type="hidden" name="tab" value="advance">

        <table class="form-table">
           
			
			<tr>
				<th scope="row" class="titledesc">
					<?php _e('Enable SSO Login', 'license-shipper'); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<!-- Hidden fallback -->
						<input type="hidden" name="lship_sso_enabled" value="no">

						<label for="lship_sso_enabled">
							<input
								type="checkbox"
								id="lship_sso_enabled"
								name="lship_sso_enabled"
								value="yes"
								<?php checked(get_option('lship_sso_enabled', 'no'), 'yes'); ?>
							>
							<?php _e('Enable Single Sign-On (SSO) login from external system.', 'license-shipper'); ?>
						</label>

						<p class="description">
							<?php _e('Allow secure SSO login using a shared token from your main application.', 'license-shipper'); ?>
						</p>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th scope="row" class="titledesc">
					<?php _e('SSO Access Token', 'license-shipper'); ?>
				</th>
				<td class="forminp">
					<input
						type="text"
						class="regular-text"
						id="lship_sso_token"
						name="lship_sso_token"
						value="<?php echo esc_attr(get_option('lship_sso_token')); ?>"
					>

					<p class="description">
						<?php _e('Paste the SSO token generated from your Licenseshipper admin panel.', 'license-shipper'); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row" class="titledesc">
					<?php _e('SSO User Account Email', 'license-shipper'); ?>
				</th>
				<td class="forminp">
					<input
						type="email"
						class="regular-text"
						id="lship_sso_user_email"
						name="lship_sso_user_email"
						value="<?php echo esc_attr(get_option('lship_sso_user_email')); ?>"
					>

					<p class="description">
						<?php _e('Licenseshipper user email that will be logged in via SSO.', 'license-shipper'); ?>
					</p>
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