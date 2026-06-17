<?php

namespace FluentCartGermanized\Legal;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verlinkung der (vom Anwalt bereitgestellten) Rechtstext-Seiten.
 *
 * - Shortcode [fcg_legal_links] für Footer/Menü.
 * - Helper getLinks() für Wiederverwendung (z.B. E-Mail).
 */
class Pages
{
    /** Reihenfolge + Labels der Rechtstext-Links */
    private function map()
    {
        return [
            'page_impressum'   => __('Impressum', 'fluentcart-germanized'),
            'page_agb'         => __('AGB', 'fluentcart-germanized'),
            'page_widerruf'    => __('Widerrufsbelehrung', 'fluentcart-germanized'),
            'page_datenschutz' => __('Datenschutz', 'fluentcart-germanized'),
            'page_versand'     => __('Versand', 'fluentcart-germanized'),
        ];
    }

    public function register()
    {
        add_shortcode('fcg_legal_links', [$this, 'shortcode']);
    }

    /**
     * @return array<int,array{label:string,url:string}>
     */
    public function getLinks()
    {
        $links = [];
        foreach ($this->map() as $key => $label) {
            $pid = (int) Settings::get($key);
            if ($pid && get_post_status($pid) === 'publish') {
                $links[] = ['label' => $label, 'url' => get_permalink($pid)];
            }
        }
        return $links;
    }

    public function shortcode($atts = [])
    {
        $atts = shortcode_atts(['sep' => ' · '], $atts, 'fcg_legal_links');
        $links = $this->getLinks();
        if (!$links) {
            return '';
        }
        $items = array_map(function ($l) {
            return '<a href="' . esc_url($l['url']) . '">' . esc_html($l['label']) . '</a>';
        }, $links);
        return '<span class="fcg-legal-links">' . implode(esc_html($atts['sep']), $items) . '</span>';
    }
}
