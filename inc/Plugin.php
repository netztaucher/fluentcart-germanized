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

        // Order-Compliance (default-schonend; Feinschliff der Hooks live verifizieren)
        (new \FluentCartGermanized\Order\EmailFilter())->register();
        (new \FluentCartGermanized\Order\InvoiceFilter())->register();
    }
}
