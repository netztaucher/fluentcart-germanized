<?php

namespace FluentCartGermanized\Order;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rechnungs-Compliance (§14/§19 UStG).
 *
 * FluentCart vergibt bereits eine fortlaufende Rechnungsnummer ($order->invoice_no =
 * Präfix + receipt_number) und weist USt., Verkäufer-/Käufer-USt-ID und Steuer-
 * aufschlüsselung aus – die §14-Pflichtangaben sind also weitgehend abgedeckt.
 *
 * Dieses Modul ergänzt, was fehlt:
 *  - §19-Hinweis „Gemäß §19 UStG wird keine Umsatzsteuer berechnet." (Kleinunternehmer-Modus)
 *  - eine optionale, frei konfigurierbare Rechnungsnotiz (z.B. Leistungsdatum-Klausel)
 *
 * Mechanik: Der Beleg wird über den Shortcode [fluent_cart_receipt] gerendert; wir
 * hängen unseren Hinweis sauber über den do_shortcode_tag-Filter an (kein Core-Eingriff).
 */
class InvoiceFilter
{
    public function register()
    {
        if (Settings::get('invoice_enhance') !== 'yes') {
            return;
        }
        add_filter('do_shortcode_tag', [$this, 'appendToReceipt'], 10, 2);
    }

    /**
     * @param string $output Shortcode-Ausgabe
     * @param string $tag    Shortcode-Name
     * @return string
     */
    public function appendToReceipt($output, $tag)
    {
        if ($tag !== 'fluent_cart_receipt' || !is_string($output) || $output === '') {
            return $output;
        }
        $note = $this->buildNote();
        if (!$note) {
            return $output;
        }
        return $output . $note;
    }

    public function buildNote()
    {
        $lines = [];

        if (Settings::isKleinunternehmer()) {
            $lines[] = esc_html__('Gemäß §19 UStG wird keine Umsatzsteuer berechnet.', 'fluentcart-germanized');
        }

        $custom = trim((string) Settings::get('invoice_note'));
        if ($custom !== '') {
            $lines[] = wp_kses_post($custom);
        }

        $lines = apply_filters('fcg/invoice_note_lines', $lines);
        if (!$lines) {
            return '';
        }

        return '<div class="fcg-invoice-note" style="margin-top:16px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:13px;color:#475569;line-height:1.5">'
            . implode('<br>', $lines)
            . '</div>';
    }
}
