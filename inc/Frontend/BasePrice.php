<?php

namespace FluentCartGermanized\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grundpreis (PAngV §4): Preis je Referenzeinheit, z.B. "12,50 € / 1 kg".
 *
 * Pro-Produkt-Meta (gesetzt über Admin\ProductFields):
 *  _fcg_unit         Referenzeinheit (kg, g, l, ml, m, m², Stk)
 *  _fcg_unit_base    Referenzmenge (z.B. 1 oder 100)
 *  _fcg_unit_product Füllmenge des Produkts in derselben Einheit (z.B. 500)
 *
 * Grundpreis = Preis / unit_product * unit_base
 */
class BasePrice
{
    private $printed = [];

    public function register()
    {
        add_action('fluent_cart/product/after_price', [$this, 'render'], 9, 1);
    }

    public function render($data = [])
    {
        $product = is_array($data) && isset($data['product']) ? $data['product'] : null;
        if (!$product || !isset($product->ID)) {
            return;
        }
        $scope = is_array($data) && isset($data['scope']) ? $data['scope'] : 'default';
        $key = $product->ID . ':' . $scope;
        if (isset($this->printed[$key])) {
            return;
        }
        $this->printed[$key] = true;

        $unit        = get_post_meta($product->ID, '_fcg_unit', true);
        $unitBase    = (float) get_post_meta($product->ID, '_fcg_unit_base', true);
        $unitProduct = (float) get_post_meta($product->ID, '_fcg_unit_product', true);

        if (!$unit || $unitProduct <= 0) {
            return; // kein Grundpreis konfiguriert
        }
        if ($unitBase <= 0) {
            $unitBase = 1;
        }

        $price = $this->resolvePrice($data, $product);
        if ($price === null) {
            return;
        }

        $basePrice = $price / $unitProduct * $unitBase;
        $formatted = $this->money($basePrice);
        $baseLabel = ($unitBase == 1 ? '' : rtrim(rtrim(number_format($unitBase, 2, ',', '.'), '0'), ',') . ' ') . $unit;

        echo '<span class="fcg-base-price">' . esc_html(sprintf('%s / %s', $formatted, $baseLabel)) . '</span>';
    }

    /** Preis aus Hook-Daten (current_price ist meist in Cent) bzw. Produkt ziehen. */
    private function resolvePrice($data, $product)
    {
        $cents = null;
        if (is_array($data) && isset($data['current_price']) && is_numeric($data['current_price'])) {
            $cents = (float) $data['current_price'];
        } elseif (isset($product->detail) && isset($product->detail->min_price) && is_numeric($product->detail->min_price)) {
            $cents = (float) $product->detail->min_price;
        }
        if ($cents === null) {
            return null;
        }
        // FluentCart speichert Preise in Minor-Units (Cent). Filterbar, falls anders.
        $divisor = (float) apply_filters('fcg/price_minor_unit_divisor', 100);
        return $divisor > 0 ? $cents / $divisor : $cents;
    }

    private function money($amount)
    {
        $symbol = apply_filters('fcg/currency_symbol', '€');
        return number_format($amount, 2, ',', '.') . ' ' . $symbol;
    }
}
