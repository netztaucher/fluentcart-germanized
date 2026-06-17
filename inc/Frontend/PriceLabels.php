<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;
use FluentCartGermanized\ProductHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Preis-Labels gem. PAngV:
 *  - "inkl. MwSt." (Regelbesteuerung) bzw. "keine USt. gem. §19 UStG" (Kleinunternehmer)
 *  - "zzgl. Versand" + Link (nur bei physischen Artikeln)
 *
 * Hängt sich an die FluentCart-Preis-Hooks. Markup bewusst minimal + per CSS gestaltbar.
 */
class PriceLabels
{
    /** verhindert Doppelausgabe pro Produkt+Scope innerhalb eines Renders */
    private $printed = [];

    public function register()
    {
        add_action('fluent_cart/product/after_price', [$this, 'renderAfterPrice'], 10, 1);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
    }

    public function assets()
    {
        // Hohe Spezifität (Klassen-Repetition) schlägt fremde Sticker-Regeln wie
        // ".fct-price-range .fct-item-price span{...!important}" – ohne Kopplung an deren Klassen.
        $sel = 'span.fcg-price-note.fcg-price-note.fcg-price-note.fcg-price-note.fcg-price-note';
        $selB = 'span.fcg-base-price.fcg-base-price.fcg-base-price.fcg-base-price.fcg-base-price,span.fcg-delivery-time.fcg-delivery-time.fcg-delivery-time.fcg-delivery-time.fcg-delivery-time';
        $base = 'display:block!important;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif!important;font-size:12px!important;font-weight:400!important;line-height:1.4!important;letter-spacing:normal!important;text-transform:none!important;color:#666!important;opacity:1!important;margin-top:6px!important';
        $css = $sel . '{' . $base . '}'
            . $sel . ' a{text-decoration:underline;color:inherit}'
            . $selB . '{' . $base . ';margin-top:2px!important}';
        wp_register_style('fcg-frontend', false);
        wp_enqueue_style('fcg-frontend');
        wp_add_inline_style('fcg-frontend', $css);
    }

    public function renderAfterPrice($data = [])
    {
        $product = is_array($data) && isset($data['product']) ? $data['product'] : null;
        $scope   = is_array($data) && isset($data['scope']) ? $data['scope'] : 'default';

        $key = ($product && isset($product->ID) ? $product->ID : '0') . ':' . $scope;
        if (isset($this->printed[$key])) {
            return;
        }
        $this->printed[$key] = true;

        echo $this->buildNote($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intern escaped
    }

    /**
     * Baut den Hinweis-Block. Public, damit auch andere Stellen (z.B. Single-Product) ihn nutzen können.
     */
    public function buildNote($product = null)
    {
        $parts = [];

        // 1. USt-Hinweis
        if (Settings::isKleinunternehmer()) {
            $parts[] = esc_html(Settings::get('price_kleinunt_label'));
        } else {
            $parts[] = esc_html(Settings::get('price_tax_label'));
        }

        // 2. Versand-Hinweis nur bei physischen Artikeln
        if (ProductHelper::isPhysical($product)) {
            $labelTpl    = esc_html(Settings::get('shipping_label')); // z.B. "zzgl. {link}"
            $versandPage = (int) Settings::get('page_versand');
            $linkText    = esc_html(Settings::get('shipping_link_text'));
            if ($versandPage && get_post_status($versandPage)) {
                $link = '<a href="' . esc_url(get_permalink($versandPage)) . '">' . $linkText . '</a>';
            } else {
                $link = $linkText;
            }
            $parts[] = str_replace('{link}', $link, $labelTpl);
        }

        $note = implode(' · ', $parts);
        $note = apply_filters('fcg/price_note', $note, $product);

        return '<span class="fcg-price-note">' . $note . '</span>';
    }
}
