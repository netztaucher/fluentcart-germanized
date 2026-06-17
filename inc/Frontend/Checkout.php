<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checkout-Compliance.
 *
 * Phase 1: Button-Lösung (§312j BGB) – Bestell-Button-Text auf
 * „Zahlungspflichtig bestellen" setzen. FluentCart rendert den Button-Text
 * sowohl im PHP-Renderer (Filter) als auch im Vue/Modal-Checkout über
 * __('Place order'/'Place Order','fluent-cart') – daher zusätzlich via gettext.
 *
 * Pflicht-Checkboxen (AGB/Widerruf/Datenschutz/Digital-Verzicht) folgen in einer
 * Folge-Iteration nach Live-Verifikation des Vue-Checkout-Feldschemas.
 */
class Checkout
{
    public function register()
    {
        // PHP-Renderer-Filter
        add_filter('fluent_cart/checkout_page_order_button_text', [$this, 'buttonText']);

        // Vue/Modal-Checkout + sonstige Stellen: lokalisierte Strings überschreiben
        add_filter('gettext', [$this, 'gettextButton'], 10, 3);
        add_filter('gettext_with_context', [$this, 'gettextButtonCtx'], 10, 4);
    }

    public function buttonText($text)
    {
        $custom = trim((string) Settings::get('order_button_text'));
        return $custom !== '' ? $custom : $text;
    }

    public function gettextButton($translated, $text, $domain)
    {
        if ($domain === 'fluent-cart' && $this->isOrderButtonString($text)) {
            $custom = trim((string) Settings::get('order_button_text'));
            if ($custom !== '') {
                return $custom;
            }
        }
        return $translated;
    }

    public function gettextButtonCtx($translated, $text, $context, $domain)
    {
        return $this->gettextButton($translated, $text, $domain);
    }

    private function isOrderButtonString($text)
    {
        return in_array($text, ['Place order', 'Place Order'], true);
    }
}
