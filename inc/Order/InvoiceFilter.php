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
        // Storno/Gutschrift bei Erstattung (§14): fortlaufende Gutschriftnummer + Protokoll.
        add_action('fluent_cart/order_refunded', [$this, 'onRefund'], 10, 1);
    }

    public function onRefund($data)
    {
        $order = is_array($data) && isset($data['order']) ? $data['order'] : null;
        if (!is_object($order) || !method_exists($order, 'updateMeta')) {
            return;
        }
        if ($order->getMeta('_fcg_credit_note')) {
            return; // bereits vergeben
        }

        $counter = (int) get_option('fcg_credit_counter', 0) + 1;
        update_option('fcg_credit_counter', $counter, false);
        $prefix = (string) apply_filters('fcg/credit_note_prefix', 'GUT-');
        $number = sprintf('%s%d-%06d', $prefix, (int) gmdate('Y'), $counter);

        $amount = is_array($data) && isset($data['refunded_amount']) ? $data['refunded_amount'] : null;
        $stamp = gmdate('Y-m-d H:i:s');

        try {
            $order->updateMeta('_fcg_credit_note', ['number' => $number, 'time' => $stamp, 'amount' => $amount]);
            $note = (string) $order->note;
            $order->note = trim($note . "\n[" . $stamp . " UTC] " . sprintf(__('Gutschrift/Storno %s erstellt.', 'fluentcart-germanized'), $number));
            $order->save();
        } catch (\Throwable $e) {
            // best effort
        }

        $orderNo = $order->invoice_no ?: ('#' . $order->id);
        $admin = (string) apply_filters('fcg/credit_note_email', get_option('admin_email'));
        wp_mail(
            $admin,
            sprintf(__('Gutschrift %1$s zu Bestellung %2$s', 'fluentcart-germanized'), $number, $orderNo),
            sprintf(__("Für die Bestellung %1\$s wurde eine Erstattung verbucht.\nGutschrift-/Stornonummer: %2\$s\nZeitpunkt: %3\$s UTC", 'fluentcart-germanized'), $orderNo, $number, $stamp)
        );
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
