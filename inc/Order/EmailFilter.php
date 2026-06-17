<?php

namespace FluentCartGermanized\Order;

use FluentCartGermanized\Settings;
use FluentCartGermanized\Legal\Pages;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hängt Pflicht-Rechtsinhalte (USt-Hinweis, Widerrufsbelehrung-Link, Rechtstext-Links)
 * an die Bestätigungsmail (§312i BGB).
 *
 * Mechanik: WordPress wp_mail-Filter, eng begrenzt auf HTML-Mails, die als
 * Bestellbestätigung erkannt werden. Standardmäßig AUS (Setting 'email_legal_inject'),
 * da die genaue Erkennung pro Setup live verifiziert werden muss.
 */
class EmailFilter
{
    public function register()
    {
        if (Settings::get('email_legal_inject') !== 'yes') {
            return;
        }
        add_filter('wp_mail', [$this, 'maybeAppend'], 20);
    }

    public function maybeAppend($args)
    {
        if (!is_array($args) || empty($args['message'])) {
            return $args;
        }
        // nur HTML-Mails
        $headers = isset($args['headers']) ? (array) $args['headers'] : [];
        $isHtml = false;
        foreach ($headers as $h) {
            if (stripos((string) $h, 'text/html') !== false) {
                $isHtml = true;
                break;
            }
        }
        if (!$isHtml) {
            return $args;
        }

        // Nur FluentCart-Mails (Marker im Body) – verhindert Anhängen an fremde Mails.
        $isFluentCart = apply_filters('fcg/email_is_fluentcart', $this->looksLikeFluentCart($args), $args);
        if (!$isFluentCart) {
            return $args;
        }

        $block = $this->buildLegalBlock();
        if (!$block) {
            return $args;
        }

        if (stripos($args['message'], '</body>') !== false) {
            $args['message'] = str_ireplace('</body>', $block . '</body>', $args['message']);
        } else {
            $args['message'] .= $block;
        }
        return $args;
    }

    private function looksLikeFluentCart($args)
    {
        $msg = isset($args['message']) ? (string) $args['message'] : '';
        // FluentCart-Mails enthalten den "Powered by FluentCart"-Footer bzw. fluentcart.com-Link.
        if (stripos($msg, 'fluentcart.com') !== false) {
            return true;
        }
        if (stripos($msg, 'data-fluent') !== false || stripos($msg, 'fct-email') !== false) {
            return true;
        }
        return false;
    }

    public function buildLegalBlock()
    {
        $parts = [];

        if (Settings::isKleinunternehmer()) {
            $parts[] = esc_html(Settings::get('price_kleinunt_label'));
        } else {
            $parts[] = esc_html__('Alle Preise inkl. gesetzlicher MwSt.', 'fluentcart-germanized');
        }

        $links = (new Pages())->getLinks();
        if ($links) {
            $items = array_map(function ($l) {
                return '<a href="' . esc_url($l['url']) . '">' . esc_html($l['label']) . '</a>';
            }, $links);
            $parts[] = implode(' · ', $items);
        }

        $widerrufPid = (int) Settings::get('page_widerruf');
        if ($widerrufPid && get_post_status($widerrufPid) === 'publish') {
            $parts[] = esc_html__('Ihre Widerrufsbelehrung:', 'fluentcart-germanized') . ' <a href="' . esc_url(get_permalink($widerrufPid)) . '">' . esc_html__('hier ansehen', 'fluentcart-germanized') . '</a>';
        }

        if (!$parts) {
            return '';
        }

        return '<hr style="margin:24px 0;border:none;border-top:1px solid #ddd"><div style="font-size:12px;color:#666;line-height:1.5">' . implode('<br>', $parts) . '</div>';
    }
}
