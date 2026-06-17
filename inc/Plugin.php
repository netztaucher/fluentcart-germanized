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

        // Frontend-Compliance
        (new \FluentCartGermanized\Frontend\PriceLabels())->register();
        (new \FluentCartGermanized\Frontend\Checkout())->register();

        // Rechtstext-Verlinkung
        (new \FluentCartGermanized\Legal\Pages())->register();

        // Folge-Iterationen (nach Live-Verifikation der jeweiligen Hooks):
        //  - Order\EmailFilter   (Widerrufsbelehrung + USt-Hinweis in Bestätigungsmail)
        //  - Order\InvoiceFilter (fortl. Rechnungsnr. + §14/§19)
        //  - Frontend\Checkout: Pflicht-Checkboxen im Vue-Checkout
        //  - Frontend\BasePrice / DeliveryTime + Admin\ProductFields
        //  - Frontend\Withdrawal (Widerrufsformular + Widerrufsbutton §356a)
    }
}
