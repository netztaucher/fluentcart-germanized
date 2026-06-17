<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GPSR (Verordnung (EU) 2023/988, Pflicht seit 13.12.2024):
 * Produktsicherheits-Angaben — Hersteller (Name/Anschrift), EU-Verantwortliche Person,
 * Sicherheits-/Warnhinweise. Anzeige auf der Produktseite.
 *
 * Pro-Produkt-Meta (über Admin\ProductFields):
 *   _fcg_gpsr_manufacturer, _fcg_gpsr_manufacturer_address,
 *   _fcg_gpsr_eu_rep, _fcg_gpsr_safety
 */
class Gpsr
{
    private $done = false;

    public function register()
    {
        add_action('fluent_cart/product/after_product_content', [$this, 'render'], 10, 1);
    }

    public function render($data = [])
    {
        if ($this->done) {
            return;
        }
        $product = is_array($data) && isset($data['product']) ? $data['product'] : null;
        $id = $product && isset($product->ID) ? (int) $product->ID : $this->queriedProductId();
        if (!$id) {
            return;
        }
        $this->done = true;

        // Pro-Produkt-Meta, sonst globaler Standard aus Settings
        $manu      = trim((string) get_post_meta($id, '_fcg_gpsr_manufacturer', true)) ?: trim((string) Settings::get('gpsr_manufacturer'));
        $manuAddr  = trim((string) get_post_meta($id, '_fcg_gpsr_manufacturer_address', true)) ?: trim((string) Settings::get('gpsr_manufacturer_address'));
        $euRep     = trim((string) get_post_meta($id, '_fcg_gpsr_eu_rep', true)) ?: trim((string) Settings::get('gpsr_eu_rep'));
        $safety    = trim((string) get_post_meta($id, '_fcg_gpsr_safety', true));

        if (!$manu && !$manuAddr && !$euRep && !$safety) {
            return;
        }

        echo '<section class="fcg-gpsr" style="margin-top:24px;font-size:14px;line-height:1.5">';
        echo '<h3 style="font-size:16px;margin:0 0 8px">' . esc_html__('Produktsicherheit', 'fluentcart-germanized') . '</h3>';

        if ($manu || $manuAddr) {
            echo '<p style="margin:0 0 8px"><strong>' . esc_html__('Hersteller', 'fluentcart-germanized') . ':</strong><br>';
            if ($manu) {
                echo esc_html($manu) . '<br>';
            }
            if ($manuAddr) {
                echo nl2br(esc_html($manuAddr));
            }
            echo '</p>';
        }
        if ($euRep) {
            echo '<p style="margin:0 0 8px"><strong>' . esc_html__('Verantwortliche Person in der EU', 'fluentcart-germanized') . ':</strong><br>' . nl2br(esc_html($euRep)) . '</p>';
        }
        if ($safety) {
            echo '<p style="margin:0 0 8px"><strong>' . esc_html__('Sicherheitshinweise', 'fluentcart-germanized') . ':</strong><br>' . nl2br(esc_html($safety)) . '</p>';
        }
        echo '</section>';
    }

    private function queriedProductId()
    {
        if (function_exists('is_singular') && is_singular('fluent-products')) {
            $o = get_queried_object();
            return $o && isset($o->ID) ? (int) $o->ID : 0;
        }
        return 0;
    }
}
