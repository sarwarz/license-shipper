<?php
defined('ABSPATH') || exit;

class License_Shipper_Admin_MetaBoxes {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_metabox']);
        add_action('admin_footer', [$this, 'print_inline_js']);
    }

    public function register_metabox() {
        add_meta_box(
            'ls_order_license_keys',
            __('License Keys', 'license-shipper'),
            [$this, 'render_metabox'],
            'shop_order',
            'advanced',
            'high'
        );
    }

    public function render_metabox($post) {
        $order = wc_get_order($post->ID);
        if (!$order || $order->get_status() !== 'completed') {
            echo '<p>' . esc_html__('License keys are available only for completed orders.', 'license-shipper') . '</p>';
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ls_cached_licenses';

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT product_id, key_value FROM $table WHERE order_id = %d", $order->get_id())
        );

        $licenses = [];
        foreach ($rows as $row) {
            $licenses[$row->product_id][] = $row->key_value;
        }
        ?>

        <table class="widefat striped">
            <thead>
            <tr>
                <th><?php esc_html_e('Product', 'license-shipper'); ?></th>
                <th><?php esc_html_e('License', 'license-shipper'); ?></th>
                <th><?php esc_html_e('Action', 'license-shipper'); ?></th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($order->get_items() as $item):
                $product_id = $item->get_variation_id() ?: $item->get_product_id();
                $qty        = (int) $item->get_quantity();
            ?>
                <tr data-product-row="<?php echo esc_attr($product_id); ?>">
                    <td><?php echo esc_html($item->get_name()); ?></td>

                    <td class="ls-license-cell">
                        <?php if (!empty($licenses[$product_id])): ?>
                            <?php foreach ($licenses[$product_id] as $key): ?>
                                <code style="display:block"><?php echo esc_html($key); ?></code>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <em><?php esc_html_e('Not fetched yet', 'license-shipper'); ?></em>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (empty($licenses[$product_id])): ?>
                            <button
                                class="button button-primary ls-get-license"
                                data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                data-product-id="<?php echo esc_attr($product_id); ?>"
                                data-email="<?php echo esc_attr($order->get_billing_email()); ?>"
                                data-qnty="<?php echo esc_attr($qty); ?>">
                                <?php esc_html_e('Get License', 'license-shipper'); ?>
                            </button>
                        <?php else: ?>
                            <span style="color:green;">✔</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
        <?php
    }

    /**
     * ✅ WORKING INLINE JS
     */
    public function print_inline_js() {
        ?>
        <script>
        jQuery(document).on('click', '.ls-get-license', function () {
            const btn = jQuery(this);
            const row = btn.closest('tr');

            btn.prop('disabled', true).text('Fetching...');

            jQuery.post(ajaxurl, {
                action: 'ls_admin_fetch_license',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('ls_fetch_license_api')); ?>',
                order_id: btn.data('order-id'),
                product_id: btn.data('product-id'),
                email: btn.data('email'),
                qnty: btn.data('qnty')
            })
            .done(function (res) {
                if (res.success) {
                    row.find('.ls-license-cell').html(res.data.html);
                    btn.replaceWith('<span style="color:green;">✔</span>');
                } else {
                    alert(res.data.message || 'Failed to fetch license');
                    btn.prop('disabled', false).text('Get License');
                }
            })
            .fail(function () {
                alert('AJAX request failed');
                btn.prop('disabled', false).text('Get License');
            });
        });
        </script>
        <?php
    }
}

new License_Shipper_Admin_MetaBoxes();
