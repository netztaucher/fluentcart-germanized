<?php

namespace FluentCartGermanized\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Omnibus-Richtlinie / PAngV §11: Bei Preisermäßigungen muss der niedrigste
 * Gesamtpreis der letzten 30 Tage angegeben werden.
 *
 * Mechanik:
 *  - Passive Preis-Historie: bei jeder Preisanzeige wird der aktuelle Preis je Tag
 *    festgehalten (post_meta _fcg_price_history: {YYYY-MM-DD: minorPrice}), 30 Tage rollierend.
 *  - Zusätzlich Snapshot bei fluent_cart/product_updated.
 *  - Anzeige „Niedrigster Preis der letzten 30 Tage" nur bei reduzierten Artikeln.
 */
class OmnibusPrice
{
    const META = '_fcg_price_history';
    private $done = [];

    public function register()
    {
        add_action('fluent_cart/product/after_price', [$this, 'handle'], 8, 1);
        add_action('fluent_cart/product_updated', [$this, 'onProductUpdated'], 10, 1);
    }

    public function handle($data = [])
    {
        $product = is_array($data) && isset($data['product']) ? $data['product'] : null;
        if (!$product || !isset($product->ID)) {
            return;
        }
        $scope = is_array($data) && isset($data['scope']) ? $data['scope'] : 'default';
        $key = $product->ID . ':' . $scope;
        if (isset($this->done[$key])) {
            return;
        }
        $this->done[$key] = true;

        $price = $this->currentPrice($data, $product);
        if ($price !== null) {
            $this->record($product->ID, $price);
        }

        // Anzeige nur bei Reduzierung
        $compare = $this->comparePrice($product);
        if ($compare !== null && $price !== null && $compare > $price) {
            $low = $this->lowest30($product->ID);
            if ($low !== null) {
                $txt = sprintf(
                    /* translators: %s lowest price */
                    __('Niedrigster Preis der letzten 30 Tage: %s', 'fluentcart-germanized'),
                    $this->money($low)
                );
                echo '<span class="fcg-omnibus" style="display:block;font-size:12px;color:#666;margin-top:2px">' . esc_html($txt) . '</span>';
            }
        }
    }

    public function onProductUpdated($payload)
    {
        $product = is_array($payload) && isset($payload['product']) ? $payload['product'] : (is_object($payload) ? $payload : null);
        if (!$product || !isset($product->ID)) {
            return;
        }
        $price = $this->currentPrice([], $product);
        if ($price !== null) {
            $this->record($product->ID, $price);
        }
    }

    private function record($productId, $priceMinor)
    {
        $hist = get_post_meta($productId, self::META, true);
        if (!is_array($hist)) {
            $hist = [];
        }
        $today = gmdate('Y-m-d');
        // niedrigsten Preis des Tages halten
        if (!isset($hist[$today]) || $priceMinor < $hist[$today]) {
            $hist[$today] = (int) $priceMinor;
        }
        // auf 40 Tage kürzen
        $cutoff = gmdate('Y-m-d', time() - 40 * DAY_IN_SECONDS);
        foreach (array_keys($hist) as $d) {
            if ($d < $cutoff) {
                unset($hist[$d]);
            }
        }
        update_post_meta($productId, self::META, $hist);
    }

    private function lowest30($productId)
    {
        $hist = get_post_meta($productId, self::META, true);
        if (!is_array($hist) || !$hist) {
            return null;
        }
        $cutoff = gmdate('Y-m-d', time() - 30 * DAY_IN_SECONDS);
        $vals = [];
        foreach ($hist as $d => $p) {
            if ($d >= $cutoff) {
                $vals[] = (int) $p;
            }
        }
        return $vals ? min($vals) : null;
    }

    private function currentPrice($data, $product)
    {
        if (is_array($data) && isset($data['current_price']) && is_numeric($data['current_price'])) {
            return (int) $data['current_price'];
        }
        if (isset($product->detail) && isset($product->detail->min_price) && is_numeric($product->detail->min_price)) {
            return (int) $product->detail->min_price;
        }
        return null;
    }

    private function comparePrice($product)
    {
        try {
            if (isset($product->variants) && is_iterable($product->variants)) {
                foreach ($product->variants as $v) {
                    $cp = is_object($v) && isset($v->compare_price) ? $v->compare_price : (is_array($v) && isset($v['compare_price']) ? $v['compare_price'] : null);
                    if (is_numeric($cp) && $cp > 0) {
                        return (int) $cp;
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    private function money($minor)
    {
        $div = (float) apply_filters('fcg/price_minor_unit_divisor', 100);
        $val = $div > 0 ? $minor / $div : $minor;
        return number_format($val, 2, ',', '.') . ' ' . apply_filters('fcg/currency_symbol', '€');
    }
}
