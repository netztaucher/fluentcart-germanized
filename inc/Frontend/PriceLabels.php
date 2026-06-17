<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;

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
        $css = '.fcg-price-note{display:block;font-size:.8em;line-height:1.3;opacity:.85;margin-top:2px}.fcg-price-note a{text-decoration:underline}';
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
        if ($this->isPhysical($product)) {
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

    /**
     * Best-effort: ist mindestens eine Variante physisch (fulfillment_type 'physical')?
     * Im Zweifel false (sicherer für reine Download-Shops – kein falsches „zzgl. Versand").
     */
    private function isPhysical($product)
    {
        if (!$product) {
            return false;
        }
        try {
            // FluentCart Product-Model: variants->fulfillment_type
            if (isset($product->variants) && is_iterable($product->variants)) {
                foreach ($product->variants as $v) {
                    $ft = is_object($v) && isset($v->fulfillment_type) ? $v->fulfillment_type : (is_array($v) && isset($v['fulfillment_type']) ? $v['fulfillment_type'] : null);
                    if ($ft === 'physical') {
                        return true;
                    }
                }
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }
}
