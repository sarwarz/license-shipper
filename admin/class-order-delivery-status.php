<?php
defined('ABSPATH') || exit;

class Ls_License_Shipper_Order_Delivery_Status {

    public static function init() {

        // Add column to orders list
        add_filter(
            'manage_edit-shop_order_columns',
            [ __CLASS__, 'add_delivery_column' ],
            20
        );

        // Render column content
        add_action(
            'manage_shop_order_posts_custom_column',
            [ __CLASS__, 'render_delivery_column' ],
            10,
            2
        );

        // Add styles
        add_action(
            'admin_head',
            [ __CLASS__, 'admin_styles' ]
        );
    }

    /**
     * Add Delivery column
     */
    public static function add_delivery_column( $columns ) {

        $new_columns = [];

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;

            // Insert after status column
            if ( $key === 'order_status' ) {
                $new_columns['ls_delivery_status'] = __('Delivery', 'license-shipper');
            }
        }

        return $new_columns;
    }

    /**
     * Render Delivery status icon
     */
    public static function render_delivery_column( $column, $order_id ) {

        if ( $column !== 'ls_delivery_status' ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            echo '—';
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ls_cached_licenses';

        $required_products = 0;
        $delivered_products = 0;

        foreach ( $order->get_items() as $item ) {

            $product_id   = (int) $item->get_product_id();
            $variation_id = (int) $item->get_variation_id();
            $used_id      = $variation_id ?: $product_id;

            // Only License Shipper enabled products
            if ( get_post_meta( $used_id, '_ls_enabled', true ) !== 'yes' ) {
                continue;
            }

            $required_products++;

            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table 
                     WHERE order_id = %d 
                       AND product_id = %d 
                       AND fetched = 1",
                    $order_id,
                    $used_id
                )
            );

            if ( $count > 0 ) {
                $delivered_products++;
            }
        }

        // No License Shipper products
        if ( $required_products === 0 ) {
            echo '—';
            return;
        }

        // Delivery completed
        if ( $required_products === $delivered_products ) {

            echo '<span class="dashicons dashicons-yes-alt ls-delivery-complete"
                  title="License Delivered"></span>';
        
        } else {
        
            echo '<span class="dashicons dashicons-warning ls-delivery-pending"
                  title="License Pending"></span>';
        }

    }

    /**
     * Admin styles
     */
    public static function admin_styles() {
        ?>
        <style>
            .wp-list-table .column-ls_delivery_status {
                width: 80px;
                text-align: center;
            }
            .ls-delivery-complete {
                color: #46b450;
                font-size: 18px;
                font-weight: bold;
            }
            .ls-delivery-pending {
                color: red;
                font-size: 18px;
                font-weight: bold;
            }
        </style>
        <?php
    }
}

// Boot
Ls_License_Shipper_Order_Delivery_Status::init();
