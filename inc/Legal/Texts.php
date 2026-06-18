<?php

namespace FluentCartGermanized\Legal;

use FluentCartGermanized\LegalText;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zentrale Rechtstext-Verwaltung.
 * Speichert die Texte in einer Option und stellt pro Text einen Shortcode bereit.
 * Die zugehörigen WP-Seiten enthalten nur den Shortcode -> Pflege an EINER Stelle.
 *
 * Impressum bewusst NICHT enthalten (klassische WP-Seite).
 */
class Texts
{
    const OPTION = 'fcg_legal_texts';
    const NONCE = 'fcg_legal_texts_save';

    /** key => [Label, Shortcode, hat amtlichen Default] */
    public static function fields()
    {
        return [
            'widerruf'         => [__('Widerrufsbelehrung', 'fluentcart-germanized'), 'fcg_widerrufsbelehrung', true],
            'widerrufsformular' => [__('Widerrufsformular (Text)', 'fluentcart-germanized'), 'fcg_widerrufsformular_text', true],
            'agb'              => [__('AGB', 'fluentcart-germanized'), 'fcg_agb', false],
            'versand'          => [__('Versand & Zahlung', 'fluentcart-germanized'), 'fcg_versand', false],
        ];
    }

    public function register()
    {
        foreach (self::fields() as $key => $def) {
            add_shortcode($def[1], function () use ($key) {
                return $this->output($key);
            });
        }
        if (is_admin()) {
            add_action('admin_menu', [$this, 'menu'], 11);
            add_action('admin_post_fcg_save_legal_texts', [$this, 'save']);
        }
    }

    /** Gespeicherten Text holen; bei amtlichen Texten Fallback auf Gesetzes-Muster. */
    public static function get($key)
    {
        $all = get_option(self::OPTION, []);
        $val = is_array($all) && !empty($all[$key]) ? $all[$key] : '';
        if ($val === '') {
            $val = self::default($key);
        }
        return $val;
    }

    public static function default($key)
    {
        if ($key === 'widerruf') {
            return LegalText::widerrufsbelehrung();
        }
        if ($key === 'widerrufsformular') {
            return LegalText::widerrufsformularText();
        }
        return '';
    }

    private function output($key)
    {
        $content = self::get($key);
        if ($content === '') {
            return '';
        }
        // Gespeicherte Texte können HTML enthalten; Shortcodes innerhalb zulassen.
        return '<div class="fcg-legal-text fcg-legal-' . esc_attr($key) . '">' . do_shortcode(wp_kses_post($content)) . '</div>';
    }

    public function menu()
    {
        add_submenu_page(
            'fcg-settings',
            __('Rechtstexte', 'fluentcart-germanized'),
            __('Rechtstexte', 'fluentcart-germanized'),
            'manage_options',
            'fcg-legal-texts',
            [$this, 'render']
        );
    }

    public function render()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $all = get_option(self::OPTION, []);
        if (!is_array($all)) {
            $all = [];
        }
        $saved = isset($_GET['fcg_saved']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Rechtstexte', 'fluentcart-germanized'); ?></h1>
            <p class="description"><?php esc_html_e('Diese Texte werden über die jeweiligen Shortcodes ausgegeben. Widerrufsbelehrung/-formular sind mit dem amtlichen Muster (Anlage 1/2 EGBGB) vorbelegt — leer lassen = Muster wird verwendet. Finale Prüfung durch Betreiber/Anwalt.', 'fluentcart-germanized'); ?></p>
            <?php if ($saved): ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Gespeichert.', 'fluentcart-germanized'); ?></p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="fcg_save_legal_texts">
                <?php wp_nonce_field(self::NONCE, '_fcg_nonce'); ?>
                <?php foreach (self::fields() as $key => $def):
                    $stored = isset($all[$key]) ? $all[$key] : '';
                    $content = $stored !== '' ? $stored : self::default($key);
                    ?>
                    <h2><?php echo esc_html($def[0]); ?> <code style="font-weight:normal">[<?php echo esc_html($def[1]); ?>]</code></h2>
                    <?php
                    wp_editor($content, 'fcg_editor_' . $key, [
                        'textarea_name' => 'fcg_text[' . $key . ']',
                        'textarea_rows' => 10,
                        'media_buttons' => false,
                    ]);
                    ?>
                    <hr>
                <?php endforeach; ?>
                <?php submit_button(__('Rechtstexte speichern', 'fluentcart-germanized')); ?>
            </form>
        </div>
        <?php
    }

    public function save()
    {
        if (!current_user_can('manage_options')) {
            wp_die('');
        }
        if (!isset($_POST['_fcg_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_fcg_nonce'])), self::NONCE)) {
            wp_die(esc_html__('Sicherheitsprüfung fehlgeschlagen.', 'fluentcart-germanized'));
        }
        $in = isset($_POST['fcg_text']) && is_array($_POST['fcg_text']) ? wp_unslash($_POST['fcg_text']) : [];
        $clean = [];
        foreach (array_keys(self::fields()) as $key) {
            $clean[$key] = isset($in[$key]) ? wp_kses_post($in[$key]) : '';
        }
        update_option(self::OPTION, $clean, false);

        wp_safe_redirect(add_query_arg('fcg_saved', '1', admin_url('admin.php?page=fcg-legal-texts')));
        exit;
    }
}
