<?php
defined('ABSPATH') || exit;

class Ls_License_Shipper_Email_Handler {

    /**
     * Send the license email for an order.
     */
    public static function send_license_email($order_id, $email) {
        global $wpdb;

        if (empty($order_id) || !is_email($email)) return false;

        $order = wc_get_order($order_id);
        if (!$order) return false;

        $table = $wpdb->prefix . 'ls_cached_licenses';

        // Prevent duplicate sends
        $already_sent = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE order_id = %d AND email_sent = 1", $order_id)
        );
        if ($already_sent > 0) return false;

        $licenses = $wpdb->get_results(
            $wpdb->prepare("SELECT id, product_id, key_value, download_link, activation_guide FROM {$table} WHERE order_id = %d", $order_id)
        );
        if (empty($licenses)) return false;

        // Product names
        $product_names = [];
        foreach (array_unique(array_map(fn($r) => (int)$r->product_id, $licenses)) as $pid) {
            $p = wc_get_product($pid);
            $product_names[$pid] = $p ? $p->get_name() : ('Product #' . $pid);
        }

        // Branding
        $site_name     = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject       = get_option('lship_email_subject', 'Your License Keys from ' . $site_name);
        $brand_color   = get_option('lship_brand_color', '#4F46E5');
        $accent_color  = get_option('lship_accent_color', '#0EA5E9');
        $text_color    = '#111827';
        $muted_color   = '#6B7280';
        $bg_color      = '#F3F4F6';
        $card_bg       = '#FFFFFF';
        $border_color  = '#E5E7EB';
        $logo_url      = esc_url(get_option('lship_email_logo', get_site_icon_url()));
        $support_email = is_email(get_option('lship_support_email')) ? get_option('lship_support_email') : get_option('admin_email');

        // Customer info
        $customer_name = esc_html($order->get_formatted_billing_full_name() ?: 'there');
        $order_number  = esc_html($order->get_order_number());
        $order_date    = esc_html(date_i18n(get_option('date_format', 'M j, Y'), $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time()));

        // Build rows HTML
        $rows_html = '';
        $i = 0;
        foreach ($licenses as $lic) {
            $i++;
            $pname = esc_html($product_names[(int)$lic->product_id] ?? 'Product');
            $key   = esc_html($lic->key_value);

            $download_link = filter_var($lic->download_link, FILTER_VALIDATE_URL)
                ? esc_url($lic->download_link)
                : esc_url(home_url('/'));

            $guide_url = wp_nonce_url(
                add_query_arg(['action' => 'download_activation_guide', 'key_id' => (int)$lic->id], admin_url('admin-ajax.php')),
                'ls_download_guide_' . (int)$lic->id
            );

            $zebra = ($i % 2 === 0) ? 'background-color:#F9FAFB;' : '';
            $rows_html .= '
            <tr style="'.$zebra.'">
                <td style="padding:12px 14px; font:14px Arial,sans-serif; color:'.$text_color.';">'.$pname.'</td>
                <td style="padding:12px 14px; color:'.$text_color.';">
                    <span style="display:inline-block; padding:6px 10px; background:#F3F4F6; border:1px solid '.$border_color.'; border-radius:6px;">'.$key.'</span>
                </td>
                <td align="center">
                    <a href="'.$download_link.'" style="display:inline-block; padding:8px 14px; text-decoration:none; border-radius:6px; border:1px solid '.$border_color.'; color:'.$text_color.';">Download</a>
                </td>
                <td align="center">
                    <a href="'.$guide_url.'" style="display:inline-block; padding:8px 14px; text-decoration:none; border-radius:6px; background:'.$accent_color.'; color:#fff;">Guide</a>
                </td>
            </tr>';
        }

        // Template
        $template_path = plugin_dir_path(__FILE__) . 'templates/email/license-email-template.php';
        ob_start();
        include $template_path;
        $html = ob_get_clean();

        // Headers
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sender_name  = get_option('lship_email_sender_name', $site_name);
        $sender_email = get_option('lship_email_sender_email', get_option('admin_email'));
        $headers[] = 'From: ' . sanitize_text_field($sender_name) . ' <' . sanitize_email($sender_email) . '>';

        // Send
        $sent = wp_mail($email, $subject, $html, $headers);

        

        if ($sent) {
            $wpdb->update($table, ['email_sent' => 1], ['order_id' => $order_id]);
        }

        return (bool)$sent;
    }
}
