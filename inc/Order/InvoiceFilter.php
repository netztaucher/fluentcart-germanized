<?php

namespace FluentCartGermanized\Order;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rechnungs-Compliance (§14/§19 UStG):
 *  - vergibt eine fortlaufende Rechnungsnummer pro bezahlter Bestellung (idempotent, als Order-/Post-Meta).
 *  - stellt getInvoiceNumber() + §19-Hinweistext bereit.
 *
 * Die Einblendung in die FluentCart-HTML-Rechnung erfolgt über den
 * Template-Filter (live zu verifizieren). Standardmäßig vergibt das Modul nur
 * die Nummer; die Template-Injektion ist per Setting 'invoice_enhance' schaltbar.
 */
class InvoiceFilter
{
    const COUNTER_OPTION = 'fcg_invoice_counter';
    const META_KEY = '_fcg_invoice_number';

    public function register()
    {
        // Nummernvergabe an bestätigten Bezahlt-Event.
        add_action('fluent_cart/order_paid_done', [$this, 'assignNumber'], 10, 1);

        if (Settings::get('invoice_enhance') === 'yes') {
            // Versuch, §19-Hinweis/Nr. in die Rechnungs-Ausgabe einzuhängen.
            add_filter('fluent_cart/invoice/footer_note', [$this, 'invoiceNote'], 10, 2);
        }
    }

    /**
     * @param mixed $payload FluentCart-Order-Event-Payload (Order-Objekt oder Array mit 'order').
     */
    public function assignNumber($payload)
    {
        $orderId = $this->extractOrderId($payload);
        if (!$orderId) {
            return;
        }
        // idempotent
        $existing = get_post_meta($orderId, self::META_KEY, true);
        if ($existing) {
            return;
        }
        update_post_meta($orderId, self::META_KEY, $this->nextNumber());
    }

    public function getInvoiceNumber($orderId)
    {
        return get_post_meta((int) $orderId, self::META_KEY, true);
    }

    /**
     * Fortlaufende Nummer: PREFIXJAHR-laufend, z.B. RE-2026-000123
     */
    public function nextNumber()
    {
        $counter = (int) get_option(self::COUNTER_OPTION, 0);
        $counter++;
        update_option(self::COUNTER_OPTION, $counter, false);

        $prefix = (string) Settings::get('invoice_number_prefix');
        $year = (int) gmdate('Y');
        $number = sprintf('%s%d-%06d', $prefix, $year, $counter);
        return apply_filters('fcg/invoice_number', $number, $counter);
    }

    public function invoiceNote($note, $order = null)
    {
        $extra = [];
        $orderId = $this->extractOrderId($order);
        if ($orderId) {
            $num = $this->getInvoiceNumber($orderId);
            if ($num) {
                $extra[] = sprintf(__('Rechnungsnummer: %s', 'fluentcart-germanized'), $num);
            }
        }
        if (Settings::isKleinunternehmer()) {
            $extra[] = __('Gemäß §19 UStG wird keine Umsatzsteuer berechnet.', 'fluentcart-germanized');
        }
        if (!$extra) {
            return $note;
        }
        return trim((string) $note . "\n" . implode("\n", $extra));
    }

    private function extractOrderId($payload)
    {
        if (is_numeric($payload)) {
            return (int) $payload;
        }
        if (is_object($payload)) {
            if (isset($payload->ID)) {
                return (int) $payload->ID;
            }
            if (isset($payload->id)) {
                return (int) $payload->id;
            }
            if (isset($payload->order) && is_object($payload->order) && isset($payload->order->id)) {
                return (int) $payload->order->id;
            }
        }
        if (is_array($payload)) {
            if (isset($payload['order']) && is_object($payload['order']) && isset($payload['order']->id)) {
                return (int) $payload['order']->id;
            }
            if (isset($payload['order_id'])) {
                return (int) $payload['order_id'];
            }
        }
        return 0;
    }
}
