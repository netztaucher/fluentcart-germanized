<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;
use FluentCartGermanized\ProductHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lieferzeit (Art. 246a EGBGB).
 * Wird an die Preis-Note angehängt (eine Zeile: „… • 3 Tage"), statt separatem Block.
 * Pro-Produkt-Meta _fcg_delivery_time, sonst Standard aus Settings. Nur physische Artikel.
 */
class DeliveryTime
{
    public function register()
    {
        add_filter('fcg/price_note', [$this, 'appendToNote'], 10, 2);
    }

    public function appendToNote($note, $product)
    {
        if (Settings::get('delivery_in_note') !== 'yes') {
            return $note; // Lieferzeit am Preis aus (Info steht auf der verlinkten Versandseite)
        }
        if (!ProductHelper::isPhysical($product)) {
            return $note;
        }

        $text = '';
        if ($product && isset($product->ID)) {
            $text = (string) get_post_meta($product->ID, '_fcg_delivery_time', true);
        }
        if (!$text) {
            $text = (string) Settings::get('default_delivery_time');
        }
        $text = apply_filters('fcg/delivery_time_text', $text, $product);
        if (!$text) {
            return $note;
        }

        $sep = '<span class="fcg-sep"> • </span>';
        return $note . $sep . '<span class="fcg-delivery-time">' . esc_html($text) . '</span>';
    }
}
