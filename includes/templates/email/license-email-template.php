<?php
/**
 * License Email Template
 *
 * Variables passed in:
 * @var string $site_name
 * @var string $subject
 * @var string $brand_color
 * @var string $accent_color
 * @var string $text_color
 * @var string $muted_color
 * @var string $bg_color
 * @var string $card_bg
 * @var string $border_color
 * @var string $logo_url
 * @var string $support_email
 * @var string $customer_name
 * @var string $order_number
 * @var string $order_date
 * @var string $rows_html
 */
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="x-apple-disable-message-reformatting">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($subject); ?></title>
</head>
<body style="margin:0; padding:0; background:<?php echo $bg_color; ?>;">
    <span style="display:none !important; visibility:hidden; opacity:0; height:0; width:0; overflow:hidden; mso-hide:all; color:transparent;">
        Your license keys and download/activation links are inside.
    </span>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:<?php echo $bg_color; ?>;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="640" style="max-width:640px; width:100%;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding:20px 24px; background:<?php echo $card_bg; ?>; border-radius:12px 12px 0 0; border:1px solid <?php echo $border_color; ?>; border-bottom:0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <?php if ($logo_url): ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" height="36" style="display:block; height:36px; max-width:160px;">
                                        <?php else: ?>
                                            <div style="font:bold 20px/28px -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:<?php echo $text_color; ?>;">
                                                <?php echo esc_html($site_name); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block; padding:6px 10px; border-radius:999px; background:<?php echo $brand_color; ?>; color:#fff; font:12px/16px -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
                                            Order #<?php echo esc_html($order_number); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background:<?php echo $card_bg; ?>; border-left:1px solid <?php echo $border_color; ?>; border-right:1px solid <?php echo $border_color; ?>;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:8px 24px 0 24px;">
                                        <h1 style="margin:16px 0 8px 0; font:600 22px/30px -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:<?php echo $text_color; ?>;">
                                            Hi <?php echo esc_html($customer_name); ?>, your license keys are ready
                                        </h1>
                                        <p style="margin:0 0 8px 0; font:14px/22px -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:<?php echo $muted_color; ?>;">
                                            Thanks for your purchase on <strong><?php echo esc_html($order_date); ?></strong>. Below you’ll find your keys, downloads, and activation guides.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Table Card -->
                                <tr>
                                    <td style="padding:0 24px 24px 24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid <?php echo $border_color; ?>; border-radius:10px; overflow:hidden;">
                                            <thead>
                                                <tr>
                                                    <th align="left" style="padding:12px 14px; font:600 12px/16px Arial, sans-serif; letter-spacing:.02em; text-transform:uppercase; color:<?php echo $muted_color; ?>; background:#F9FAFB; border-bottom:1px solid <?php echo $border_color; ?>;">Product</th>
                                                    <th align="left" style="padding:12px 14px; font:600 12px/16px Arial, sans-serif; letter-spacing:.02em; text-transform:uppercase; color:<?php echo $muted_color; ?>; background:#F9FAFB; border-bottom:1px solid <?php echo $border_color; ?>;">License Key</th>
                                                    <th align="center" style="padding:12px 14px; font:600 12px/16px Arial, sans-serif; letter-spacing:.02em; text-transform:uppercase; color:<?php echo $muted_color; ?>; background:#F9FAFB; border-bottom:1px solid <?php echo $border_color; ?>;">Download</th>
                                                    <th align="center" style="padding:12px 14px; font:600 12px/16px Arial, sans-serif; letter-spacing:.02em; text-transform:uppercase; color:<?php echo $muted_color; ?>; background:#F9FAFB; border-bottom:1px solid <?php echo $border_color; ?>;">Guide</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php echo $rows_html; ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Help -->
                                <tr>
                                    <td style="padding:0 24px 24px 24px;">
                                        <p style="margin:0; font:14px/22px Arial, sans-serif; color:<?php echo $muted_color; ?>;">
                                            Need help? Contact us at <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:<?php echo $brand_color; ?>; text-decoration:none;"><?php echo esc_html($support_email); ?></a>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:16px 24px 28px 24px; background:<?php echo $card_bg; ?>; border:1px solid <?php echo $border_color; ?>; border-top:0; border-radius:0 0 12px 12px;">
                            <p style="margin:0; font:12px/18px Arial, sans-serif; color:<?php echo $muted_color; ?>;">
                                <?php echo esc_html($site_name); ?> • <a href="<?php echo esc_url(home_url()); ?>" style="color:<?php echo $muted_color; ?>; text-decoration:none;"><?php echo esc_html(home_url()); ?></a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
