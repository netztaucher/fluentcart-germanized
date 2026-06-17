<?php

namespace FluentCartGermanized;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zentrale Plugin-Klasse: bootet alle Feature-Module.
 */
class Plugin
{
    /** @var Plugin|null */
    private static $instance = null;

    /** @var bool */
    private $booted = false;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot()
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        // Settings + Admin
        (new Settings())->register();

        // Einmaliger Seiten-Install (falls Plugin schon aktiv war) + manueller Button
        if (is_admin()) {
            add_action('admin_init', ['\\FluentCartGermanized\\Installer', 'maybeInstall']);
            add_action('admin_post_fcg_create_pages', function () {
                if (!current_user_can('manage_options')) {
                    wp_die('');
                }
                check_admin_referer('fcg_create_pages');
                $created = \FluentCartGermanized\Installer::createMissingPages();
                wp_safe_redirect(add_query_arg('fcg_pages', count($created), admin_url('admin.php?page=fcg-settings')));
                exit;
            });
        }

        // Frontend-Compliance (PAngV / §312j)
        (new \FluentCartGermanized\Frontend\PriceLabels())->register();
        (new \FluentCartGermanized\Frontend\BasePrice())->register();
        (new \FluentCartGermanized\Frontend\DeliveryTime())->register();
        (new \FluentCartGermanized\Frontend\Checkout())->register();
        (new \FluentCartGermanized\Frontend\Withdrawal())->register();

        // Rechtstext-Verlinkung
        (new \FluentCartGermanized\Legal\Pages())->register();

        // Pro-Produkt-Felder (eigener Admin-Screen, da FluentCart-Admin = Vue-SPA)
        if (is_admin()) {
            (new \FluentCartGermanized\Admin\ProductFields())->register();
        }

        // Server-seitiges Checkbox-Enforcement
        (new \FluentCartGermanized\Order\Consent())->register();

        // Order-Compliance (default-schonend; Feinschliff der Hooks live verifizieren)
        (new \FluentCartGermanized\Order\EmailFilter())->register();
        (new \FluentCartGermanized\Order\InvoiceFilter())->register();
    }
}
