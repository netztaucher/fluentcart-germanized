<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;
use FluentCartGermanized\Order\Consent;

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

        // Pflicht-Checkboxen per JS in den (Vue-gerenderten) Checkout injizieren + Bestellung blocken.
        add_action('wp_enqueue_scripts', [$this, 'assets']);
    }

    public function assets()
    {
        $defs = $this->checkboxDefs();
        if (!$defs) {
            return;
        }
        $checkboxes = [];
        foreach ($defs as $id => $label) {
            $checkboxes[] = ['id' => $id, 'label' => $label, 'required' => true];
        }

        $jsPath = FCG_DIR . 'assets/js/checkout.js';
        $jsVer = file_exists($jsPath) ? filemtime($jsPath) : FCG_VERSION;
        wp_register_script('fcg-checkout', FCG_URL . 'assets/js/checkout.js', [], $jsVer, true);
        wp_localize_script('fcg-checkout', 'fcgCheckout', [
            'checkboxes' => $checkboxes,
            'errorText'  => __('Bitte bestätigen Sie die markierten Pflichtangaben, um fortzufahren.', 'fluentcart-germanized'),
        ]);
        wp_enqueue_script('fcg-checkout');

        $css = '.fcg-checkout-legal{margin:14px 0;padding:12px 14px;border:1px solid #d6dae1;border-radius:8px;font-size:14px;line-height:1.4}'
            . '.fcg-checkout-legal.fcg-invalid{border-color:#e02b2b;background:#fff5f5}'
            . '.fcg-cb-row{display:flex;gap:8px;align-items:flex-start;margin:6px 0}'
            . '.fcg-cb-row input{margin-top:3px;flex:none}'
            . '.fcg-cb-error{color:#e02b2b;margin-top:6px;font-size:13px}'
            . '.fcg-checkout-legal a{text-decoration:underline}';
        wp_register_style('fcg-checkout', false);
        wp_enqueue_style('fcg-checkout');
        wp_add_inline_style('fcg-checkout', $css);
    }

    /**
     * @return array<string,string> id => Label (mit Links zu Rechtstext-Seiten)
     */
    private function checkboxDefs()
    {
        $comp = (new Consent())->cartComposition();
        $defs = [];
        $link = function ($pageKey, $text) {
            $pid = (int) Settings::get($pageKey);
            if ($pid && get_post_status($pid) === 'publish') {
                return '<a href="' . esc_url(get_permalink($pid)) . '" target="_blank" rel="noopener">' . esc_html($text) . '</a>';
            }
            return esc_html($text);
        };

        if (Settings::get('checkbox_terms') === 'yes') {
            $defs['fcg_terms'] = sprintf(
                /* translators: 1: AGB-Link, 2: Widerruf-Link */
                __('Ich akzeptiere die %1$s und habe die %2$s zur Kenntnis genommen.', 'fluentcart-germanized'),
                $link('page_agb', __('AGB', 'fluentcart-germanized')),
                $link('page_widerruf', __('Widerrufsbelehrung', 'fluentcart-germanized'))
            );
        }
        if (Settings::get('checkbox_privacy') === 'yes') {
            $defs['fcg_privacy'] = sprintf(
                __('Ich habe die %s gelesen.', 'fluentcart-germanized'),
                $link('page_datenschutz', __('Datenschutzerklärung', 'fluentcart-germanized'))
            );
        }
        // Versanddienstleister-Einwilligung nur bei physischen Artikeln im Warenkorb
        if (Settings::get('checkbox_shipping_data') === 'yes' && $comp['has_physical']) {
            $defs['fcg_shipping_data'] = __('Ich willige ein, dass meine Adressdaten zur Zustellung an den beauftragten Versanddienstleister übermittelt werden.', 'fluentcart-germanized');
        }
        // Digital-Verzicht nur bei digitalen Artikeln im Warenkorb
        if (Settings::get('checkbox_digital') === 'yes' && $comp['has_digital']) {
            $defs['fcg_digital'] = __('Bei digitalen Inhalten: Ich stimme der sofortigen Ausführung zu und weiß, dass mein Widerrufsrecht damit erlischt.', 'fluentcart-germanized');
        }

        return $defs;
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
