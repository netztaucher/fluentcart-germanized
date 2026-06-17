<?php

namespace FluentCartGermanized;

if (!defined('ABSPATH')) {
    exit;
}

class ProductHelper
{
    /**
     * Ist das Produkt physisch (Versand nötig)?
     * Reihenfolge: explizites Meta _fcg_is_digital > FluentCart-Variant-fulfillment_type.
     * Im Zweifel false (sicherer für Download-Shops).
     */
    public static function isPhysical($product)
    {
        if (!$product || !isset($product->ID)) {
            return false;
        }

        $digitalMeta = get_post_meta($product->ID, '_fcg_is_digital', true);
        if ($digitalMeta === 'yes') {
            return false;
        }

        try {
            if (isset($product->variants) && is_iterable($product->variants)) {
                foreach ($product->variants as $v) {
                    $ft = is_object($v) && isset($v->fulfillment_type) ? $v->fulfillment_type
                        : (is_array($v) && isset($v['fulfillment_type']) ? $v['fulfillment_type'] : null);
                    if ($ft === 'physical') {
                        return true;
                    }
                }
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        // Variants nicht ladbar: wenn Meta explizit "nicht digital" gesetzt war, als physisch werten.
        return $digitalMeta === 'no';
    }
}
