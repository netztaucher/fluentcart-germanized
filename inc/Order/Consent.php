<?php

namespace FluentCartGermanized\Order;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Server-seitiges Checkbox-Enforcement + Cart-Komposition + Consent-Protokollierung.
 *
 * Ablauf:
 *  - JS schreibt die angehakten Consent-IDs in ein Cookie (fcg_consent), das auf der
 *    Bestell-Request mitgesendet wird (in jedem Request-Kontext lesbar).
 *  - Filter fluent_cart/checkout/validate_before_process prüft die Pflicht-Consents
 *    anhand der Cart-Komposition (digital/physisch) und blockt sonst die Bestellung.
 *  - Nach Bestellabschluss werden die Consents + Zeitstempel als Order-Meta protokolliert.
 */
class Consent
{
    const COOKIE = 'fcg_consent';

    public function register()
    {
        add_filter('fluent_cart/checkout/validate_before_process', [$this, 'validate'], 10, 2);
        // Consent protokollieren – bei bezahlten UND offline/unbezahlten Bestellungen.
        add_action('fluent_cart/order_paid_done', [$this, 'recordConsent'], 10, 1);
        add_action('fluent_cart/order_placed_offline', [$this, 'recordConsent'], 10, 1);
    }

    /**
     * @param bool|\WP_Error $valid
     * @param array          $data
     * @return bool|\WP_Error
     */
    public function validate($valid, $data)
    {
        if (is_wp_error($valid)) {
            return $valid;
        }

        $required = $this->requiredConsents();
        if (!$required) {
            return $valid;
        }

        $given = $this->givenConsents($data);

        foreach ($required as $id) {
            if (!in_array($id, $given, true)) {
                return new \WP_Error(
                    'fcg_consent_missing',
                    __('Bitte bestätigen Sie alle rechtlich erforderlichen Angaben (AGB/Widerruf, Datenschutz, ggf. Versand/Digital), um die Bestellung abzuschließen.', 'fluentcart-germanized')
                );
            }
        }

        return $valid;
    }

    public function recordConsent($payload)
    {
        $order = $this->resolveOrder($payload);
        if (!$order || !method_exists($order, 'updateMeta')) {
            return;
        }
        // Doppel-Eintrag vermeiden
        if (method_exists($order, 'getMeta') && $order->getMeta('_fcg_consent')) {
            return;
        }
        $record = [
            'ids'  => $this->givenConsents([]),
            'time' => gmdate('c'),
            'ip'   => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
        ];
        try {
            $order->updateMeta('_fcg_consent', $record);
        } catch (\Throwable $e) {
            // still nicht kritisch – Enforcement ist die eigentliche Absicherung
        }
    }

    private function resolveOrder($payload)
    {
        if (is_object($payload) && method_exists($payload, 'updateMeta')) {
            return $payload;
        }
        if (is_object($payload) && isset($payload->order) && is_object($payload->order)) {
            return $payload->order;
        }
        $id = $this->orderId($payload);
        if ($id && class_exists('\\FluentCart\\App\\Models\\Order')) {
            try {
                return \FluentCart\App\Models\Order::query()->find($id);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    /** @return array Liste der vom Kunden bestätigten Consent-IDs */
    private function givenConsents($data)
    {
        $given = [];
        if (isset($_COOKIE[self::COOKIE])) {
            $raw = sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE]));
            $given = array_filter(array_map('sanitize_key', explode(',', $raw)));
        }
        if (is_array($data) && isset($data['fcg_consent']) && is_array($data['fcg_consent'])) {
            $given = array_merge($given, array_map('sanitize_key', $data['fcg_consent']));
        }
        return array_values(array_unique($given));
    }

    /** Welche Consents sind je nach Settings + Cart-Komposition Pflicht? */
    public function requiredConsents()
    {
        $comp = $this->cartComposition();
        $req = [];
        if (Settings::get('checkbox_terms') === 'yes') {
            $req[] = 'fcg_terms';
        }
        if (Settings::get('checkbox_privacy') === 'yes') {
            $req[] = 'fcg_privacy';
        }
        if (Settings::get('checkbox_shipping_data') === 'yes' && $comp['has_physical']) {
            $req[] = 'fcg_shipping_data';
        }
        if (Settings::get('checkbox_digital') === 'yes' && $comp['has_digital']) {
            $req[] = 'fcg_digital';
        }
        if (Settings::get('checkbox_age') === 'yes' && $this->cartMaxMinAge() > 0) {
            $req[] = 'fcg_age';
        }
        return $req;
    }

    /** Höchstes Mindestalter (_fcg_min_age) der Produkte im Warenkorb; 0 wenn keins. */
    public function cartMaxMinAge()
    {
        $cart = $this->cart();
        if (!$cart || empty($cart->cart_data) || !is_array($cart->cart_data)) {
            return 0;
        }
        $max = 0;
        foreach ($cart->cart_data as $item) {
            $pid = is_array($item) ? ($item['post_id'] ?? 0) : (is_object($item) && isset($item->post_id) ? $item->post_id : 0);
            if (!$pid) {
                continue;
            }
            $age = (int) get_post_meta((int) $pid, '_fcg_min_age', true);
            if ($age > $max) {
                $max = $age;
            }
        }
        return $max;
    }

    /** @return array{has_physical:bool,has_digital:bool} */
    public function cartComposition()
    {
        $out = ['has_physical' => false, 'has_digital' => false];
        $cart = $this->cart();
        if (!$cart || empty($cart->cart_data) || !is_array($cart->cart_data)) {
            return $out;
        }
        foreach ($cart->cart_data as $item) {
            $ft = is_array($item) ? ($item['fulfillment_type'] ?? '') : (is_object($item) && isset($item->fulfillment_type) ? $item->fulfillment_type : '');
            if ($ft === 'physical') {
                $out['has_physical'] = true;
            } elseif ($ft === 'digital') {
                $out['has_digital'] = true;
            }
        }
        return $out;
    }

    private function cart()
    {
        try {
            if (class_exists('\\FluentCart\\App\\Helpers\\CartHelper')) {
                return \FluentCart\App\Helpers\CartHelper::getCart();
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    private function orderId($payload)
    {
        if (is_numeric($payload)) {
            return (int) $payload;
        }
        if (is_object($payload)) {
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
