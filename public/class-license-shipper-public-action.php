<?php
defined( 'ABSPATH' ) || exit();

/**
 * Ls_License_Shipper_Public_Action Class
 */
class Ls_License_Shipper_Public_Action{
	
	public static function init(){

        // Fetch License
        add_action( 'wp_ajax_ls_fetch_license', array( __CLASS__, 'ls_handle_fetch_license' ) );
        add_action( 'wp_ajax_nopriv_ls_fetch_license', array( __CLASS__, 'ls_handle_fetch_license' ) );

        // Export License Key CSV
        add_action( 'wp_ajax_export_license_key', array( __CLASS__, 'ls_export_license_key_csv' ) );
        add_action( 'wp_ajax_nopriv_export_license_key', array( __CLASS__, 'ls_export_license_key_csv' ) );

        // Cron / Scheduled Event (no nopriv needed for these)
        add_action('ls_send_license_email_event', ['Ls_License_Shipper_Email_Handler', 'send_license_email'], 10, 2);

        // Download Activation Guide
        add_action( 'wp_ajax_download_activation_guide', array( __CLASS__, 'ls_download_activation_guide' ) );
        add_action( 'wp_ajax_nopriv_download_activation_guide', array( __CLASS__, 'ls_download_activation_guide' ) );



	}

    public static function ls_handle_fetch_license() {
        check_ajax_referer('ls_fetch_license_api', '_ajax_nonce');

        global $wpdb;

        $order_id   = absint($_POST['order_id']);
        $product_id = absint($_POST['product_id']); // May be parent or variation
        $email      = sanitize_email($_POST['email']);
        $qnty       = absint($_POST['qnty']);
        $table      = $wpdb->prefix . 'ls_cached_licenses';

        $order = wc_get_order($order_id);

        if (!$order || $order->get_billing_email() !== $email) {
            wp_send_json_error(['message' => 'Unauthorized access to order.']);
        }

        if (get_current_user_id() && $order->get_user_id() !== get_current_user_id()) {
            wp_send_json_error(['message' => 'This order does not belong to you.']);
        }

        $has_product = false;
        $used_product_id = null;

        foreach ($order->get_items() as $item) {
            $item_product_id   = (int) $item->get_product_id();
            $item_variation_id = (int) $item->get_variation_id();

            if ($item_variation_id && $item_variation_id === $product_id) {
                $has_product = true;
                $used_product_id = $item_variation_id;
                break;
            } elseif ($item_product_id === $product_id) {
                $has_product = true;
                $used_product_id = $item_product_id;
                break;
            }
        }


        if (!$has_product || !$used_product_id) {
            wp_send_json_error(['message' => 'Product not found in this order.']);
        }

        if (!in_array($order->get_status(), ['completed'], true)) {
            wp_send_json_error(['message' => 'License can only be fetched for completed orders.']);
        }

        // Check cache
        $cached_keys = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d AND product_id = %d",
            $order_id, $used_product_id
        ));

        if (!empty($cached_keys)) {
            ob_start(); ?>
            <table class="shop_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Key', 'license-shipper'); ?></th>
                        <th><?php esc_html_e('Download link', 'license-shipper'); ?></th>
                        <th><?php esc_html_e('Activation Process', 'license-shipper'); ?></th>
                        <th><?php esc_html_e('Action', 'license-shipper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cached_keys as $license) : ?>
                        <tr>
                            <td><code class="ls-license-key"><?php echo esc_html($license->key_value); ?></code></td>
                            <td><a href="<?php echo esc_url($license->download_link); ?>" class="ls-btn-download" target="_blank">Click Here</a></td>
                            <td>
                                <a href="<?php echo esc_url( admin_url('admin-ajax.php?action=download_activation_guide&key_id=' . $license->id) ); ?>" class="ls-btn-guide" target="_blank">Download Now</a>
                            </td>
                            <td><button class="button ls-copy-license-btn">Copy</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $html = ob_get_clean();
            wp_send_json_success(['html' => $html]);
        }

        // License Shipper enabled check
        $is_enabled = get_post_meta($used_product_id, '_ls_enabled', true);
        if ($is_enabled !== 'yes') {
            wp_send_json_error(['message' => 'License Shipper is not enabled for this product.']);
        }

        // Get mapped SKU
        $mapped_sku = get_post_meta($used_product_id, '_ls_mapped_product', true);
        if (empty($mapped_sku)) {
            wp_send_json_error(['message' => 'This product does not have a mapped SKU.']);
        }

        // Call API
        $result = License_Shipper_Api::fetch_license([
            'sku'      => $mapped_sku,
            'quantity' => $qnty,
            'order_id' => $order_id,
            'email'    => $email,
            'source'   => sanitize_title( get_bloginfo('name') ),
        ]);

        if (!$result['success']) {


            // Build a nicer error message including reason/scope from API
            $msg   = $result['message'] ?? __('Request failed', 'license-shipper');
            $scope = $result['meta']['scope']  ?? '';
            $reason= $result['meta']['reason'] ?? '';

            if ($reason) {
                $msg .= ' ' . sprintf(__('Reason: %s', 'license-shipper'), $reason);
            }
            if ($scope) {
                $msg .= ' ' . sprintf(__('[%s block]', 'license-shipper'), ucfirst($scope));
            }

            // Optionally pass extra info back to your JS
            wp_send_json_error([
                'message'   => $msg,
                'http_code' => $result['http_code'] ?? null,
                'meta'      => $result['meta'] ?? [],
            ]);
        }

        $licenses     = $result['licenses'];
        $product_info = $result['product'];

        $enable_downloads = get_option('lship_enable_manage_downloads') === 'yes';
        $enable_guides    = get_option('lship_enable_manage_activation_guides') === 'yes';

        // Try to get from plugin if enabled
        $plugin_download_link = ($enable_downloads && function_exists('ls_get_download_link'))
            ? ls_get_download_link($used_product_id)
            : '';

        $plugin_guide_link = ($enable_guides && function_exists('ls_get_activation_guide_pdf_link'))
            ? ls_get_activation_guide_pdf_link($used_product_id)
            : '';

        // Determine final values (plugin first → API fallback)
        $final_download_link = $plugin_download_link ?: ($product_info['download_link'] ?? '');
        $final_guide_link    = $plugin_guide_link ?: ($product_info['activation_guide'] ?? '');


        foreach ($licenses as $index => $license) {
            $keyVal = isset($license['key']) ? trim((string) $license['key']) : '';

            // Skip if empty
            if ($keyVal === '') {
                continue;
            }

            $ok = $wpdb->insert($table, [
                'order_id'         => $order_id,
                'product_id'       => $used_product_id,
                'sku'              => $mapped_sku,
                'email'            => $email,
                'key_value'        => $keyVal,
                'download_link'    => $final_download_link,
                'activation_guide' => $final_guide_link,
                'source'           => 'api',
                'fetched'          => 1,
            ]);

            if ($ok === false) {
                // Log why it failed — useful if a duplicate key is returned
                error_log('License insert failed: ' . $wpdb->last_error);
                continue;
            }

            $licenses[$index]['key_id'] = (int) $wpdb->insert_id;
        }


        // Email scheduling if enabled
        if (get_option('lship_send_email_after_redeem') === 'yes') {
            

            // Determine email send mode
            $send_mode = get_option('lship_email_send_mode', 'after_all'); // after_all | after_each

            if ($send_mode === 'after_each') {
                Ls_License_Shipper_Email_Handler::send_license_email($order_id, $email);
            } else {
                // after_all mode
                $total_items = count($order->get_items());
                $fetched_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT product_id) FROM $table WHERE order_id = %d AND fetched = 1",
                    $order_id
                ));

                if ($fetched_count >= $total_items) {
                    $already_sent = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE order_id = %d AND email_sent = 1",
                        $order_id
                    ));
                    if ($already_sent === 0 ) {
                        if (!wp_next_scheduled('ls_send_license_email_event', [$order_id, $email])) {
                            wp_schedule_single_event(time() + 30, 'ls_send_license_email_event', [$order_id, $email]);
                        }
                    }
                }
            }

        }

        // Render license table
        ob_start(); ?>
        <table class="shop_table" style="width: 100%;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Key', 'license-shipper'); ?></th>
                    <th><?php esc_html_e('Download link', 'license-shipper'); ?></th>
                    <th><?php esc_html_e('Activation Process', 'license-shipper'); ?></th>
                    <th><?php esc_html_e('Action', 'license-shipper'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license) : ?>
                    <tr>
                        <td><code class="ls-license-key"><?php echo esc_html($license['key']); ?></code></td>
                        <td><a href="<?php echo esc_url($final_download_link); ?>" class="button ls-btn-download" target="_blank">Click Here</a></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url('admin-ajax.php?action=download_activation_guide&key_id=' .$license['key_id']) ); ?>" class="button ls-btn-guide" target="_blank">Download Now</a>

                        </td>
                        <td><button class="button ls-copy-license-btn">Copy</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }




   public static function ls_export_license_key_csv() {
        // Ensure this only runs on AJAX context
        if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
            wp_die('Invalid context.', '', 400);
        }

        // Basic input / nonce checks
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $nonce    = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

        if ( ! $order_id || ! wp_verify_nonce( $nonce, 'export_license_nonce' ) ) {
            wp_die('Invalid request.', '', 400);
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die('Order not found.', '', 404);
        }

        // --- Access control ------------------------------------------------------
        $can_download = false;

        // 1) Admins/managers
        if ( current_user_can('manage_woocommerce') ) {
            $can_download = true;
        }

        // 2) Logged-in customer who owns the order
        if ( ! $can_download && is_user_logged_in() ) {
            $can_download = ( (int) $order->get_user_id() === get_current_user_id() );
        }

        // 3) Guests (or anyone) with a valid order_key (+ optional email match)
        if ( ! $can_download ) {
            $req_key = isset($_GET['order_key']) ? wc_clean( wp_unslash($_GET['order_key']) ) : '';
            if ( $req_key && hash_equals( (string) $order->get_order_key(), (string) $req_key ) ) {
                // Optional extra check: match billing email if provided
                if ( isset($_GET['email']) && $_GET['email'] !== '' ) {
                    $req_email = sanitize_email( wp_unslash($_GET['email']) );
                    $can_download = ( strtolower($req_email) === strtolower($order->get_billing_email()) );
                } else {
                    $can_download = true;
                }
            }
        }

        if ( ! $can_download ) {
            wp_die('Unauthorized', '', 403);
        }

        // Optional: restrict to paid statuses
        if ( ! $order->has_status( array('processing','completed') ) ) {
            wp_die('Order not paid/ready.', '', 403);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ls_cached_licenses';

        $licenses = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE order_id = %d", $order_id)
        );

        if ( empty($licenses) ) {
            wp_die('No license keys found for this order.', '', 404);
        }

        // Prepare CSV response
        nocache_headers();
        $filename = "license-order-{$order_id}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // CSV headers
        fputcsv($out, ['Order ID', 'Product Name', 'License Key']);

        foreach ( $licenses as $license ) {
            $product      = wc_get_product( (int) $license->product_id );
            $product_name = $product ? $product->get_name() : 'Unknown';
            fputcsv($out, [
                $license->order_id,
                $product_name,
                $license->key_value,
            ]);
        }

        fclose($out);
        exit;
    }



    public static function ls_download_activation_guide() {
        if ( ! isset($_GET['key_id']) || ! is_numeric($_GET['key_id']) ) {
            wp_die(__('Invalid request.', 'license-shipper'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ls_cached_licenses';
        $key_id = absint($_GET['key_id']);

        $license = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $key_id)
        );

        if ( ! $license || empty($license->activation_guide) ) {
            wp_die(__('Activation guide not found.', 'license-shipper'));
        }

        $url = esc_url_raw(trim($license->activation_guide));

        if ( ! filter_var($url, FILTER_VALIDATE_URL) ) {
            wp_die(__('Invalid activation guide URL.', 'license-shipper'));
        }

        // Fetch remote file using WP HTTP API
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'sslverify' => false, // important for bad host SSL setups
        ]);

        if ( is_wp_error($response) ) {
            wp_die(__('Could not access activation guide file.', 'license-shipper'));
        }

        $body = wp_remote_retrieve_body($response);
        if ( empty($body) ) {
            wp_die(__('Activation guide is empty.', 'license-shipper'));
        }

        $filename = basename(parse_url($url, PHP_URL_PATH)) ?: 'activation-guide.pdf';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        header('Cache-Control: no-cache');

        echo $body;
        exit;
    }












}

Ls_License_Shipper_Public_Action::init();