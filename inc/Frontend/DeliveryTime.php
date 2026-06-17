<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lieferzeit-Anzeige (Art. 246a EGBGB).
 * Pro-Produkt-Meta _fcg_delivery_time, sonst Standard aus Settings.
 * Nur für physische Artikel sinnvoll – bei reinen Downloads unterdrückbar via Filter.
 */
class DeliveryTime
{
    private $printed = [];

    public function register()
    {
        add_action('fluent_cart/product/after_price', [$this, 'render'], 11, 1);
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

        $text = get_post_meta($product->ID, '_fcg_delivery_time', true);
        if (!$text) {
            $text = (string) Settings::get('default_delivery_time');
        }
        $text = apply_filters('fcg/delivery_time_text', $text, $product);
        if (!$text) {
            return;
        }

        echo '<span class="fcg-delivery-time">' . esc_html($text) . '</span>';
    }
}
