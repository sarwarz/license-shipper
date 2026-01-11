<?php
defined('ABSPATH') || exit;

class License_Shipper_Admin_Fetch_License {

    public function __construct() {
        add_action('wp_ajax_ls_admin_fetch_license', [$this, 'handle']);
    }

    /**
     * ADMIN ONLY: Fetch license for one product in an order
     */
    public function handle() {

        // 🔐 Security
        check_ajax_referer('ls_fetch_license_api', '_ajax_nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        global $wpdb;

        $order_id   = absint($_POST['order_id'] ?? 0);
        $product_id = absint($_POST['product_id'] ?? 0);
        $quantity   = max(1, absint($_POST['qnty'] ?? 1));

        if (!$order_id || !$product_id) {
            wp_send_json_error(['message' => 'Invalid request data.']);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        if ($order->get_status() !== 'completed') {
            wp_send_json_error(['message' => 'Order must be completed.']);
        }

        /**
         * ============================
         * Validate product exists in order
         * ============================
         */
        $valid_product = false;
        foreach ($order->get_items() as $item) {
            $pid = $item->get_variation_id() ?: $item->get_product_id();
            if ($pid === $product_id) {
                $quantity = (int) $item->get_quantity();
                $valid_product = true;
                break;
            }
        }

        if (!$valid_product) {
            wp_send_json_error(['message' => 'Product not found in order.']);
        }

        /**
         * ============================
         * License Shipper enabled?
         * ============================
         */
        if (get_post_meta($product_id, '_ls_enabled', true) !== 'yes') {
            wp_send_json_error(['message' => 'License Shipper is disabled for this product.']);
        }

        /**
         * ============================
         * Mapped SKU
         * ============================
         */
        $mapped_sku = get_post_meta($product_id, '_ls_mapped_product', true);
        if (!$mapped_sku) {
            wp_send_json_error(['message' => 'No mapped SKU found for this product.']);
        }

        /**
         * ============================
         * Prevent duplicate fetch
         * ============================
         */
        $table = $wpdb->prefix . 'ls_cached_licenses';

        $existing = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE order_id = %d AND product_id = %d",
            $order_id,
            $product_id
        ));

        if ($existing >= $quantity) {
            wp_send_json_error(['message' => 'License already fetched for this product.']);
        }

        /**
         * ============================
         * Call API
         * ============================
         */
        $api = License_Shipper_Api::fetch_license([
            'sku'      => $mapped_sku,
            'quantity' => $quantity,
            'order_id' => $order_id,
            'email'    => $order->get_billing_email(),
            'source'   => sanitize_title( get_bloginfo('name') ).'(admin)',
        ]);

        if (empty($api['success'])) {
            wp_send_json_error([
                'message' => $api['message'] ?? 'API request failed.',
                'meta'    => $api['meta'] ?? [],
            ]);
        }

        $licenses = $api['licenses'] ?? [];
        if (empty($licenses)) {
            wp_send_json_error(['message' => 'API returned no licenses.']);
        }

        /**
         * ============================
         * Save licenses
         * ============================
         */
        $saved_keys = [];

        foreach ($licenses as $license) {
            $key = trim($license['key'] ?? '');
            if (!$key) {
                continue;
            }

            $inserted = $wpdb->insert($table, [
                'order_id'   => $order_id,
                'product_id' => $product_id,
                'sku'        => $mapped_sku,
                'email'      => $order->get_billing_email(),
                'key_value'  => $key,
                'source'     => 'admin',
                'fetched'    => 1,
            ]);

            if ($inserted) {
                $saved_keys[] = $key;
            }
        }

        if (empty($saved_keys)) {
            wp_send_json_error(['message' => 'Failed to save licenses.']);
        }

        /**
         * ============================
         * Build ADMIN HTML
         * ============================
         */
        ob_start();
        foreach ($saved_keys as $key) {
            echo '<code style="display:block;user-select:all;">' . esc_html($key) . '</code>';
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'message' => 'License fetched successfully.',
            'html'    => $html,
            'count'   => count($saved_keys),
        ]);
    }
}

new License_Shipper_Admin_Fetch_License();
