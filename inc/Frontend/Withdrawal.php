<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widerruf:
 *  - [fcg_widerrufsformular] : Online-Muster-Widerrufsformular (Anl. 2 EGBGB) mit E-Mail-Versand an den Shop.
 *  - [fcg_widerrufsbutton]   : „Vertrag widerrufen"-Button (§356a BGB, Pflicht ~19.06.2026) -> verlinkt aufs Formular.
 *
 * Self-contained: hängt nicht an FluentCart-internen Hooks.
 */
class Withdrawal
{
    const NONCE = 'fcg_widerruf';

    public function register()
    {
        add_shortcode('fcg_widerrufsformular', [$this, 'formShortcode']);
        add_shortcode('fcg_widerrufsbutton', [$this, 'buttonShortcode']);
        add_action('admin_post_nopriv_fcg_widerruf', [$this, 'handleSubmit']);
        add_action('admin_post_fcg_widerruf', [$this, 'handleSubmit']);
    }

    public function buttonShortcode($atts = [])
    {
        $atts = shortcode_atts([
            'text' => __('Vertrag widerrufen', 'fluentcart-germanized'),
            'url'  => '',
        ], $atts, 'fcg_widerrufsbutton');

        $url = $atts['url'];
        if (!$url) {
            $pid = (int) Settings::get('page_widerrufsformular');
            $url = ($pid && get_post_status($pid) === 'publish') ? get_permalink($pid) : '#fcg-widerruf';
        }
        return '<a class="fcg-widerruf-button" href="' . esc_url($url) . '">' . esc_html($atts['text']) . '</a>';
    }

    public function formShortcode($atts = [])
    {
        $sent = isset($_GET['fcg_widerruf']) && $_GET['fcg_widerruf'] === 'ok';
        ob_start();
        if ($sent) {
            echo '<div class="fcg-widerruf-ok"><p>' . esc_html__('Ihr Widerruf ist eingegangen. Sie erhalten eine Bestätigung per E-Mail.', 'fluentcart-germanized') . '</p></div>';
        }
        ?>
        <form class="fcg-widerruf-form" id="fcg-widerruf" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="fcg_widerruf">
            <?php wp_nonce_field(self::NONCE, '_fcg_nonce'); ?>
            <p><em><?php esc_html_e('Muster-Widerrufsformular (gem. Anlage 2 zu Art. 246a EGBGB). Wenn Sie den Vertrag widerrufen wollen, füllen Sie dieses Formular aus und senden es zurück.', 'fluentcart-germanized'); ?></em></p>
            <p><label><?php esc_html_e('Name', 'fluentcart-germanized'); ?><br><input type="text" name="fcg_name" required></label></p>
            <p><label><?php esc_html_e('Anschrift', 'fluentcart-germanized'); ?><br><textarea name="fcg_address" rows="2" required></textarea></label></p>
            <p><label><?php esc_html_e('E-Mail', 'fluentcart-germanized'); ?><br><input type="email" name="fcg_email" required></label></p>
            <p><label><?php esc_html_e('Bestell-/Rechnungsnummer', 'fluentcart-germanized'); ?><br><input type="text" name="fcg_order"></label></p>
            <p><label><?php esc_html_e('Bestellt am / erhalten am', 'fluentcart-germanized'); ?><br><input type="text" name="fcg_dates"></label></p>
            <p><label><input type="checkbox" name="fcg_confirm" value="yes" required> <?php esc_html_e('Hiermit widerrufe ich den von mir abgeschlossenen Vertrag über den Kauf der folgenden Waren / Dienstleistungen.', 'fluentcart-germanized'); ?></label></p>
            <p><button type="submit"><?php esc_html_e('Widerruf absenden', 'fluentcart-germanized'); ?></button></p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function handleSubmit()
    {
        if (!isset($_POST['_fcg_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_fcg_nonce'])), self::NONCE)) {
            wp_die(esc_html__('Sicherheitsprüfung fehlgeschlagen.', 'fluentcart-germanized'));
        }

        $name    = isset($_POST['fcg_name']) ? sanitize_text_field(wp_unslash($_POST['fcg_name'])) : '';
        $address = isset($_POST['fcg_address']) ? sanitize_textarea_field(wp_unslash($_POST['fcg_address'])) : '';
        $email   = isset($_POST['fcg_email']) ? sanitize_email(wp_unslash($_POST['fcg_email'])) : '';
        $order   = isset($_POST['fcg_order']) ? sanitize_text_field(wp_unslash($_POST['fcg_order'])) : '';
        $dates   = isset($_POST['fcg_dates']) ? sanitize_text_field(wp_unslash($_POST['fcg_dates'])) : '';

        $admin = get_option('admin_email');
        $subject = sprintf(__('Widerruf eingegangen – %s', 'fluentcart-germanized'), $name);
        $lines = [
            __('Es ist ein Widerruf über das Online-Formular eingegangen:', 'fluentcart-germanized'),
            '',
            __('Name:', 'fluentcart-germanized') . ' ' . $name,
            __('Anschrift:', 'fluentcart-germanized') . ' ' . $address,
            __('E-Mail:', 'fluentcart-germanized') . ' ' . $email,
            __('Bestell-/Rechnungsnummer:', 'fluentcart-germanized') . ' ' . $order,
            __('Datum:', 'fluentcart-germanized') . ' ' . $dates,
        ];
        $body = implode("\n", $lines);

        // an Shop
        wp_mail($admin, $subject, $body);
        // Eingangsbestätigung an Kunde
        if ($email && is_email($email)) {
            wp_mail($email, __('Eingangsbestätigung Ihres Widerrufs', 'fluentcart-germanized'), __('Wir bestätigen den Eingang Ihres Widerrufs. Details:', 'fluentcart-germanized') . "\n\n" . $body);
        }

        $back = wp_get_referer() ?: home_url('/');
        wp_safe_redirect(add_query_arg('fcg_widerruf', 'ok', $back));
        exit;
    }
}
