<?php

namespace FluentCartGermanized;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zentraler Settings-Zugriff (WordPress Options-API) + Admin-Settingseite.
 *
 * Alle Optionen liegen unter dem Key 'fcg_settings' (Array).
 */
class Settings
{
    const OPTION = 'fcg_settings';

    /** @var array|null */
    private static $cache = null;

    public static function defaults()
    {
        return [
            // 'regular' = Regelbesteuerung (USt. ausweisen), 'kleinunternehmer' = §19
            'tax_mode'             => 'regular',
            'price_tax_label'      => 'inkl. MwSt.',
            'price_kleinunt_label' => 'keine USt. gem. §19 UStG',
            'shipping_label'       => 'zzgl. {link}',
            'shipping_link_text'   => 'Versandkosten',
            'order_button_text'    => 'Zahlungspflichtig bestellen',
            'default_delivery_time' => 'Lieferzeit 2–4 Werktage',

            // Pflicht-Checkboxen am Checkout aktivieren
            'checkbox_terms'       => 'yes',
            'checkbox_privacy'     => 'yes',
            'checkbox_withdrawal'  => 'yes',
            'checkbox_digital'     => 'yes', // nur bei Download-Artikeln
            'checkbox_shipping_data' => 'yes', // Datenübergabe an Versanddienstleister
            'checkbox_age'         => 'yes', // Altersbestätigung (wenn Produkt Mindestalter hat)

            // Seiten-IDs für Rechtstexte (vom Anwalt befüllt)
            'page_impressum'       => 0,
            'page_agb'             => 0,
            'page_widerruf'        => 0,
            'page_datenschutz'     => 0,
            'page_versand'         => 0,
            'page_widerrufsformular' => 0,

            'withdrawal_button_footer' => 'yes',
            'withdrawal_window_days'   => '14',
            'withdrawal_email'         => '', // Fallback: admin_email

            // GPSR (Produktsicherheit) – globale Defaults, pro Produkt überschreibbar
            'gpsr_manufacturer'         => '',
            'gpsr_manufacturer_address' => '',
            'gpsr_eu_rep'               => '',

            // Erweitert
            'email_legal_inject'   => 'yes',
            'invoice_enhance'      => 'yes',
            'invoice_note'         => '',
        ];
    }

    public static function all()
    {
        if (self::$cache === null) {
            $stored = get_option(self::OPTION, []);
            if (!is_array($stored)) {
                $stored = [];
            }
            self::$cache = array_merge(self::defaults(), $stored);
        }
        return self::$cache;
    }

    public static function get($key, $fallback = null)
    {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $fallback;
    }

    public static function isKleinunternehmer()
    {
        return self::get('tax_mode') === 'kleinunternehmer';
    }

    public static function flush()
    {
        self::$cache = null;
    }

    /**
     * Registriert Admin-Menü + Settings.
     */
    public function register()
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenu()
    {
        add_menu_page(
            __('Germanized', 'fluentcart-germanized'),
            __('Germanized', 'fluentcart-germanized'),
            'manage_options',
            'fcg-settings',
            [$this, 'renderPage'],
            'dashicons-shield-alt',
            58
        );
    }

    public function registerSettings()
    {
        register_setting('fcg_settings_group', self::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => self::defaults(),
        ]);
    }

    public function sanitize($input)
    {
        $clean = self::defaults();
        if (!is_array($input)) {
            return $clean;
        }
        foreach ($clean as $key => $default) {
            $isToggle = (strpos($key, 'checkbox_') === 0) || in_array($default, ['yes', 'no'], true);

            if (!isset($input[$key])) {
                // nicht gesendete Toggles = "no"
                if ($isToggle) {
                    $clean[$key] = 'no';
                }
                continue;
            }

            if (strpos($key, 'page_') === 0) {
                $clean[$key] = absint($input[$key]);
            } elseif ($key === 'tax_mode') {
                $clean[$key] = in_array($input[$key], ['regular', 'kleinunternehmer'], true) ? $input[$key] : 'regular';
            } elseif ($isToggle) {
                $clean[$key] = $input[$key] === 'yes' ? 'yes' : 'no';
            } elseif (in_array($key, ['invoice_note', 'gpsr_manufacturer_address', 'gpsr_eu_rep'], true)) {
                $clean[$key] = sanitize_textarea_field($input[$key]);
            } else {
                $clean[$key] = sanitize_text_field($input[$key]);
            }
        }
        self::flush();
        return $clean;
    }

    public function renderPage()
    {
        $s = self::all();
        $pages = get_pages();
        $pageSelect = function ($name, $selected) use ($pages) {
            $out = '<select name="' . esc_attr(self::OPTION . '[' . $name . ']') . '">';
            $out .= '<option value="0">' . esc_html__('— keine —', 'fluentcart-germanized') . '</option>';
            foreach ($pages as $p) {
                $out .= '<option value="' . esc_attr($p->ID) . '" ' . selected($selected, $p->ID, false) . '>' . esc_html($p->post_title) . '</option>';
            }
            $out .= '</select>';
            return $out;
        };
        $text = function ($name, $value) {
            return '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION . '[' . $name . ']') . '" value="' . esc_attr($value) . '">';
        };
        $check = function ($name, $value) {
            return '<label><input type="checkbox" name="' . esc_attr(self::OPTION . '[' . $name . ']') . '" value="yes" ' . checked($value, 'yes', false) . '> ' . esc_html__('aktiv', 'fluentcart-germanized') . '</label>';
        };
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('FluentCart Germanized', 'fluentcart-germanized'); ?></h1>
            <p style="max-width:760px"><em><?php esc_html_e('Haftungsausschluss: Dieses Plugin schafft die technischen Voraussetzungen für Rechtssicherheit. Rechtstexte und Konfiguration müssen vom Betreiber/Anwalt geprüft werden. Keine Rechtsberatung.', 'fluentcart-germanized'); ?></em></p>

            <?php if (isset($_GET['fcg_pages'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php printf(esc_html__('%d fehlende Seite(n) angelegt/verknüpft.', 'fluentcart-germanized'), (int) $_GET['fcg_pages']); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:10px 0">
                <input type="hidden" name="action" value="fcg_create_pages">
                <?php wp_nonce_field('fcg_create_pages'); ?>
                <button type="submit" class="button"><?php esc_html_e('Fehlende Rechtstext-Seiten anlegen', 'fluentcart-germanized'); ?></button>
                <span class="description"><?php esc_html_e('Legt Impressum/AGB/Widerruf/Widerrufsformular/Datenschutz/Versand an (mit Shortcodes) und verknüpft sie unten.', 'fluentcart-germanized'); ?></span>
            </form>

            <form method="post" action="options.php">
                <?php settings_fields('fcg_settings_group'); ?>

                <h2><?php esc_html_e('Steuermodus', 'fluentcart-germanized'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Modus', 'fluentcart-germanized'); ?></th><td>
                        <label><input type="radio" name="<?php echo esc_attr(self::OPTION); ?>[tax_mode]" value="regular" <?php checked($s['tax_mode'], 'regular'); ?>> <?php esc_html_e('Regelbesteuert (USt. ausweisen)', 'fluentcart-germanized'); ?></label><br>
                        <label><input type="radio" name="<?php echo esc_attr(self::OPTION); ?>[tax_mode]" value="kleinunternehmer" <?php checked($s['tax_mode'], 'kleinunternehmer'); ?>> <?php esc_html_e('Kleinunternehmer §19 UStG (keine USt.)', 'fluentcart-germanized'); ?></label>
                    </td></tr>
                    <tr><th><?php esc_html_e('Label „inkl. MwSt."', 'fluentcart-germanized'); ?></th><td><?php echo $text('price_tax_label', $s['price_tax_label']); ?></td></tr>
                    <tr><th><?php esc_html_e('Label §19', 'fluentcart-germanized'); ?></th><td><?php echo $text('price_kleinunt_label', $s['price_kleinunt_label']); ?></td></tr>
                    <tr><th><?php esc_html_e('Versand-Hinweis ({link})', 'fluentcart-germanized'); ?></th><td><?php echo $text('shipping_label', $s['shipping_label']); ?> &nbsp; <?php echo $text('shipping_link_text', $s['shipping_link_text']); ?></td></tr>
                </table>

                <h2><?php esc_html_e('Checkout', 'fluentcart-germanized'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Bestell-Button-Text', 'fluentcart-germanized'); ?></th><td><?php echo $text('order_button_text', $s['order_button_text']); ?> <p class="description"><?php esc_html_e('§312j BGB: muss „Zahlungspflichtig bestellen" o.ä. lauten.', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Checkbox AGB + Widerruf', 'fluentcart-germanized'); ?></th><td><?php echo $check('checkbox_terms', $s['checkbox_terms']); ?></td></tr>
                    <tr><th><?php esc_html_e('Checkbox Datenschutz', 'fluentcart-germanized'); ?></th><td><?php echo $check('checkbox_privacy', $s['checkbox_privacy']); ?></td></tr>
                    <tr><th><?php esc_html_e('Checkbox Widerrufsrecht', 'fluentcart-germanized'); ?></th><td><?php echo $check('checkbox_withdrawal', $s['checkbox_withdrawal']); ?></td></tr>
                    <tr><th><?php esc_html_e('Checkbox Digital-Verzicht', 'fluentcart-germanized'); ?></th><td><?php echo $check('checkbox_digital', $s['checkbox_digital']); ?> <p class="description"><?php esc_html_e('Nur nötig bei Download-Artikeln (Verzicht auf Widerrufsrecht).', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Checkbox Versanddienstleister', 'fluentcart-germanized'); ?></th><td><?php echo $check('checkbox_shipping_data', $s['checkbox_shipping_data']); ?> <p class="description"><?php esc_html_e('Einwilligung zur Datenübergabe an den Versanddienstleister (z.B. DHL).', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Checkbox Altersbestätigung', 'fluentcart-germanized'); ?></th><td><?php echo $check('checkbox_age', $s['checkbox_age']); ?> <p class="description"><?php esc_html_e('Erscheint nur, wenn ein Artikel im Warenkorb ein Mindestalter hat.', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Standard-Lieferzeit', 'fluentcart-germanized'); ?></th><td><?php echo $text('default_delivery_time', $s['default_delivery_time']); ?></td></tr>
                    <tr><th><?php esc_html_e('Widerruf-Link im Footer', 'fluentcart-germanized'); ?></th><td><?php echo $check('withdrawal_button_footer', $s['withdrawal_button_footer']); ?> <p class="description"><?php esc_html_e('„Vertrag widerrufen" als Textlink in der Footer-/Copyright-Zeile (§356a).', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Widerrufsfrist (Tage)', 'fluentcart-germanized'); ?></th><td><?php echo $text('withdrawal_window_days', $s['withdrawal_window_days']); ?> <p class="description"><?php esc_html_e('Nur Bestellungen innerhalb dieser Frist erscheinen mit 1-Klick-Widerruf.', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Widerruf-Empfänger E-Mail', 'fluentcart-germanized'); ?></th><td><?php echo $text('withdrawal_email', $s['withdrawal_email']); ?> <p class="description"><?php esc_html_e('Leer = Admin-E-Mail. Versand über FluentSMTP (wp_mail).', 'fluentcart-germanized'); ?></p></td></tr>
                </table>

                <h2><?php esc_html_e('Rechtstext-Seiten', 'fluentcart-germanized'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Impressum', 'fluentcart-germanized'); ?></th><td><?php echo $pageSelect('page_impressum', $s['page_impressum']); ?></td></tr>
                    <tr><th><?php esc_html_e('AGB', 'fluentcart-germanized'); ?></th><td><?php echo $pageSelect('page_agb', $s['page_agb']); ?></td></tr>
                    <tr><th><?php esc_html_e('Widerrufsbelehrung', 'fluentcart-germanized'); ?></th><td><?php echo $pageSelect('page_widerruf', $s['page_widerruf']); ?></td></tr>
                    <tr><th><?php esc_html_e('Widerrufsformular', 'fluentcart-germanized'); ?></th><td><?php echo $pageSelect('page_widerrufsformular', $s['page_widerrufsformular']); ?></td></tr>
                    <tr><th><?php esc_html_e('Datenschutz', 'fluentcart-germanized'); ?></th><td><?php echo $pageSelect('page_datenschutz', $s['page_datenschutz']); ?></td></tr>
                    <tr><th><?php esc_html_e('Versandkosten', 'fluentcart-germanized'); ?></th><td><?php echo $pageSelect('page_versand', $s['page_versand']); ?></td></tr>
                </table>

                <h2><?php esc_html_e('GPSR / Produktsicherheit (Standard)', 'fluentcart-germanized'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Hersteller', 'fluentcart-germanized'); ?></th><td><?php echo $text('gpsr_manufacturer', $s['gpsr_manufacturer']); ?></td></tr>
                    <tr><th><?php esc_html_e('Hersteller-Anschrift', 'fluentcart-germanized'); ?></th><td><textarea name="<?php echo esc_attr(self::OPTION); ?>[gpsr_manufacturer_address]" rows="3" class="large-text"><?php echo esc_textarea($s['gpsr_manufacturer_address']); ?></textarea></td></tr>
                    <tr><th><?php esc_html_e('EU-Verantwortliche Person', 'fluentcart-germanized'); ?></th><td><textarea name="<?php echo esc_attr(self::OPTION); ?>[gpsr_eu_rep]" rows="3" class="large-text"><?php echo esc_textarea($s['gpsr_eu_rep']); ?></textarea><p class="description"><?php esc_html_e('Gilt für alle Produkte; pro Produkt unter „Produkt-Felder" überschreibbar.', 'fluentcart-germanized'); ?></p></td></tr>
                </table>

                <h2><?php esc_html_e('Erweitert (live verifizieren)', 'fluentcart-germanized'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Rechtstexte in Bestätigungsmail', 'fluentcart-germanized'); ?></th><td><?php echo $check('email_legal_inject', $s['email_legal_inject']); ?> <p class="description"><?php esc_html_e('Hängt USt-Hinweis + Rechtstext-Links an Bestell-Mails. Vorher Mail-Erkennung prüfen.', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Rechnung erweitern', 'fluentcart-germanized'); ?></th><td><?php echo $check('invoice_enhance', $s['invoice_enhance']); ?> <p class="description"><?php esc_html_e('Hängt §19-Hinweis (im Kleinunternehmer-Modus) + Rechnungsnotiz an den Beleg [fluent_cart_receipt]. FluentCart-Rechnungsnummer ist bereits fortlaufend.', 'fluentcart-germanized'); ?></p></td></tr>
                    <tr><th><?php esc_html_e('Rechnungsnotiz (optional)', 'fluentcart-germanized'); ?></th><td><textarea name="<?php echo esc_attr(self::OPTION); ?>[invoice_note]" rows="3" class="large-text"><?php echo esc_textarea($s['invoice_note']); ?></textarea><p class="description"><?php esc_html_e('Freitext, erscheint unten auf jedem Beleg (z.B. Leistungsdatum-Klausel).', 'fluentcart-germanized'); ?></p></td></tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
