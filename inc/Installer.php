<?php

namespace FluentCartGermanized;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legt fehlende Rechtstext-Seiten an (mit passenden Shortcodes/Platzhaltern) und
 * verknüpft sie in den Settings. Läuft bei Aktivierung und per Button im Backend.
 */
class Installer
{
    /**
     * Seiten-Definitionen: settings-key => [slug, title, content]
     * Inhalt bewusst Platzhalter – finaler Rechtstext kommt vom Anwalt.
     */
    private static function pages()
    {
        $disclaimer = "\n\n<!-- Bitte den geprüften Rechtstext (Anwalt/IT-Recht-Kanzlei) hier einsetzen. -->";
        return [
            'page_impressum' => [
                'impressum',
                __('Impressum', 'fluentcart-germanized'),
                '<!-- wp:paragraph --><p>' . esc_html__('Hier Ihr Impressum (§5 DDG) einfügen.', 'fluentcart-germanized') . '</p><!-- /wp:paragraph -->' . $disclaimer,
            ],
            'page_datenschutz' => [
                'datenschutz',
                __('Datenschutzerklärung', 'fluentcart-germanized'),
                '<!-- wp:paragraph --><p>' . esc_html__('Hier Ihre Datenschutzerklärung (DSGVO) einfügen.', 'fluentcart-germanized') . '</p><!-- /wp:paragraph -->' . $disclaimer,
            ],
            'page_agb' => [
                'agb',
                __('Allgemeine Geschäftsbedingungen', 'fluentcart-germanized'),
                '<!-- wp:paragraph --><p>' . esc_html__('Hier Ihre AGB einfügen.', 'fluentcart-germanized') . '</p><!-- /wp:paragraph -->' . $disclaimer,
            ],
            'page_widerruf' => [
                'widerrufsbelehrung',
                __('Widerrufsbelehrung', 'fluentcart-germanized'),
                '<!-- wp:shortcode -->[fcg_widerrufsbelehrung]<!-- /wp:shortcode -->'
                    . '<!-- wp:shortcode -->[fcg_widerrufsbutton]<!-- /wp:shortcode -->' . $disclaimer,
            ],
            'page_widerrufsformular' => [
                'widerrufsformular',
                __('Widerrufsformular', 'fluentcart-germanized'),
                '<!-- wp:shortcode -->[fcg_widerrufsformular_text]<!-- /wp:shortcode -->'
                    . '<!-- wp:shortcode -->[fcg_widerrufsformular]<!-- /wp:shortcode -->',
            ],
            'page_versand' => [
                'versand-und-zahlung',
                __('Versand & Zahlung', 'fluentcart-germanized'),
                '<!-- wp:paragraph --><p>' . esc_html__('Hier Versandkosten, Lieferzeiten und Zahlungsarten beschreiben.', 'fluentcart-germanized') . '</p><!-- /wp:paragraph -->' . $disclaimer,
            ],
        ];
    }

    public static function activate()
    {
        self::createMissingPages();
        update_option('fcg_pages_installed', '1', false);
    }

    /**
     * Einmaliger Lauf im Admin, falls Plugin bereits aktiv war als der Installer dazukam.
     */
    public static function maybeInstall()
    {
        if (get_option('fcg_pages_installed')) {
            return;
        }
        self::createMissingPages();
        update_option('fcg_pages_installed', '1', false);
    }

    /**
     * @return array Liste der neu angelegten Seitentitel
     */
    public static function createMissingPages()
    {
        $settings = get_option(Settings::OPTION, []);
        if (!is_array($settings)) {
            $settings = [];
        }
        $created = [];

        foreach (self::pages() as $key => $def) {
            list($slug, $title, $content) = $def;

            // Bereits zugewiesen und existent? -> überspringen
            $assigned = isset($settings[$key]) ? (int) $settings[$key] : 0;
            if ($assigned && get_post_status($assigned)) {
                continue;
            }

            // Existiert eine Seite mit dem Slug? -> verknüpfen statt neu anlegen
            $existing = get_page_by_path($slug);
            if ($existing) {
                $settings[$key] = $existing->ID;
                continue;
            }

            $pageId = wp_insert_post([
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);

            if ($pageId && !is_wp_error($pageId)) {
                $settings[$key] = $pageId;
                $created[] = $title;
            }
        }

        update_option(Settings::OPTION, array_merge(Settings::defaults(), $settings));
        Settings::flush();

        return $created;
    }
}
