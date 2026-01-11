<?php 

function ls_render_license_table( $order_id, $return = false ) {
    if ( empty( $order_id ) ) return;

    $order = wc_get_order( $order_id );

    if (!$order || $order->get_status() !== 'completed') {
        return;
    }

    $is_completed_by_license_shipper = get_post_meta( $order_id, '_ls_completed_license_shipper', true );
    if ( $is_completed_by_license_shipper !== 'yes' ) {
        return;
    }



    global $wpdb;
    $table = $wpdb->prefix . 'ls_cached_licenses';

    ob_start();

    ?>
    <h2 class="woocommerce-order-downloads__title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <span><?php _e('License Keys', 'license-shipper'); ?></span>

        <div class="ls-header-buttons" style="display: flex; gap: 10px;">
            <!-- Download all license keys -->
            <?php
            $order_id  = $order->get_id();
            $url = add_query_arg([
                'action'    => 'export_license_key',
                'order_id'  => $order_id,
                'order_key' => $order->get_order_key(),          // proves ownership for guests
                'email'     => $order->get_billing_email(),      // optional extra check
                '_wpnonce'  => wp_create_nonce('export_license_nonce'),
            ], admin_url('admin-ajax.php'));
            ?>
            <a href="<?php echo esc_url($url); ?>" class="button ls-export-all-btn">Download All</a>


            <!-- Bulk "Get Keys" button -->
            <a href="#"
               class="button wp-element-button ls-get-all-keys-btn"
               data-order-id="<?php echo esc_attr($order_id); ?>"
               title="<?php esc_attr_e('Retrieve all license keys step by step', 'license-shipper'); ?>">
                <span class="ls-btn-text"><?php _e('Get All Keys', 'license-shipper'); ?></span>
            </a>
        </div>
    </h2>

    <table class="woocommerce-table woocommerce-table--order-details shop_table order_details" cellspacing="0" cellpadding="6" border="1" style="width:100%;margin-bottom:40px;">
        <thead>
            <tr>
                <th><?php _e('Product', 'license-shipper'); ?></th>
                <th><?php _e('Email', 'license-shipper'); ?></th>
                <th><?php _e('Quantity', 'license-shipper'); ?></th>
                <th><?php _e('Action', 'license-shipper'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ( $order ) :
                foreach ( $order->get_items() as $item_id => $item ) :
                    $product = $item->get_product();
                    if ( ! $product ) continue;

                    $product_id   = $product->get_id();

                    if ( ! ls_is_license_shipper_enabled($product_id) ) continue;

                    $product_name = $product->get_name();
                    $quantity     = $item->get_quantity();
                    $email        = $order->get_billing_email();

                    // Check for cached licenses
                    $cached_keys = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table WHERE order_id = %d AND product_id = %d",
                        $order_id, $product_id
                    ));

                    $has_cached = !empty($cached_keys);
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                                <?php echo esc_html($product_name); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($email); ?></td>
                        <td><?php echo esc_html($quantity); ?></td>
                        <td>
                            <?php if ( $has_cached ) : ?>
                                <button class="button ls-toggle-license-btn">
                                    <?php _e('View Key', 'license-shipper'); ?>
                                </button>
                            <?php else : ?>
                                <button class="button ls-view-license-btn"
								   data-product-name="<?php echo esc_html($product_name); ?>"
                                   data-product-id="<?php echo esc_attr($product_id); ?>"
                                   data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                   data-order-qnty="<?php echo esc_attr($quantity); ?>"
                                   data-email="<?php echo esc_attr($email); ?>">
                                    <?php _e('Get Key', 'license-shipper'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- License display row -->
                    <tr class="ls-license-details-row" style="display: none;">
                        <td colspan="4">
                            <div class="ls-license-details-content">
                                <?php if ( $has_cached ) : ?>
                                    <table class="shop_table" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th class="ls-key-table-header"><?php _e('License Key', 'license-shipper'); ?></th>
                                                <th><?php _e('Download Link', 'license-shipper'); ?></th>
                                                <th><?php _e('Activation Guide', 'license-shipper'); ?></th>
                                                <th><?php _e('Action', 'license-shipper'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $cached_keys as $index => $license ) : ?>
                                                <tr class="ls-license-row <?php echo $index >= 10 ? 'ls-hidden-row' : ''; ?>">
                                                    <td><code class="ls-license-key"><?php echo esc_html($license->key_value); ?></code></td>
                                                    <td>
                                                        <a href="<?php echo esc_url($license->download_link); ?>"
                                                           target="_blank" rel="noopener"
                                                           class="ls-btn-download">
                                                          <?php _e('Click Here', 'license-shipper'); ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo esc_url( admin_url('admin-ajax.php?action=download_activation_guide&key_id=' . $license->id) ); ?>"
                                                           target="_blank" rel="noopener"
                                                           class="ls-btn-guide">
                                                          <?php _e('Download Now', 'license-shipper'); ?>
                                                        </a>
                                                    </td>
                                                    <td><button class="button ls-copy-license-btn"><?php _e('Copy', 'license-shipper'); ?></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="ls-show-toggle-wrapper" style="text-align: center;">
                                                    <button class="button ls-show-more-btn wp-element-button" style="display: <?php echo count($cached_keys) > 10 ? 'inline-block' : 'none'; ?>">Show More Keys</button>
                                                    <button class="button ls-show-less-btn wp-element-button" style="display: none;">Show Less Key</button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                <?php else : ?>
                                    <p class="ls-loading"><?php _e('Preparing to load license...', 'license-shipper'); ?></p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php
                endforeach;
            endif;
            ?>
        </tbody>
    </table>
    <?php

    $output = ob_get_clean();

    if ( $return ) {
        return $output;
    }

    echo $output;
}



function ls_is_license_shipper_enabled($product_id) {
    return get_post_meta($product_id, '_ls_enabled', true) === 'yes';
}

function ls_customizeEmailTemplate($template, $data = []) {
    return strtr($template, $data);
}

/**
 * Get Activation Guide PDF Link
 *
 * Simply returns the stored PDF link from the database
 * (does not generate or convert anything).
 *
 * @param int $product_id Product ID
 * @return string|false PDF URL or false if not found
 */
function ls_get_activation_guide_pdf_link($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ls_activation_guides';

    // Fetch activation guide record
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT content FROM $table WHERE product_id = %d LIMIT 1",
        $product_id
    ));

    if (!$row) {
        return false;
    }

    $data = maybe_unserialize($row->content);

    // Check for PDF link inside serialized content
    if (!empty($data['pdf'])) {
        return esc_url($data['pdf']);
    }

    return false;
}




/**
 * Get Download Link for a Product
 *
 * Returns the stored download link from ls_download_links table
 * for a given WooCommerce product ID.
 *
 * @param int $product_id Product ID
 * @return string|false The download link URL or false if not found
 */
function ls_get_download_link($product_id) {
    global $wpdb;

    if (empty($product_id)) {
        return false;
    }

    $table = $wpdb->prefix . 'ls_download_links';

    $link = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT link FROM {$table} WHERE product_id = %d LIMIT 1",
            $product_id
        )
    );

    // Validate and return
    if (!empty($link) && filter_var($link, FILTER_VALIDATE_URL)) {
        return esc_url($link);
    }

    return false;
}





