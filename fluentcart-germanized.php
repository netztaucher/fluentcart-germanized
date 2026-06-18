<?php
/**
 * Plugin Name:       FluentCart Germanized
 * Plugin URI:        https://netztaucher.com/
 * Description:        Macht FluentCart rechtssicher für den deutschen Markt (PAngV, Button-Lösung, Pflicht-Checkboxen, Widerruf, §19, Lieferzeit, Grundpreis). Companion-Plugin – ändert FluentCart-Originale nicht.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Author:            netztaucher
 * Text Domain:       fluentcart-germanized
 * Domain Path:       /languages
 *
 * HAFTUNGSAUSSCHLUSS: Dieses Plugin schafft die technischen Voraussetzungen für
 * Rechtssicherheit. Die finalen Rechtstexte und die Konfiguration müssen vom
 * Betreiber bzw. einem Anwalt geprüft werden. Keine Rechtsberatung.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FCG_VERSION', '1.0.0');
define('FCG_FILE', __FILE__);
define('FCG_DIR', plugin_dir_path(__FILE__));
define('FCG_URL', plugin_dir_url(__FILE__));
define('FCG_BASENAME', plugin_basename(__FILE__));

/**
 * Minimaler PSR-4-Autoloader für den Namespace FluentCartGermanized\ -> inc/
 */
spl_autoload_register(function ($class) {
    $prefix = 'FluentCartGermanized\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $path = FCG_DIR . 'inc/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($path)) {
        require $path;
    }
});

/**
 * Aktivierung: fehlende Rechtstext-Seiten anlegen.
 */
register_activation_hook(__FILE__, function () {
    require_once FCG_DIR . 'inc/Settings.php';
    require_once FCG_DIR . 'inc/Installer.php';
    \FluentCartGermanized\Installer::activate();
});

/**
 * Bootstrap nach dem Laden aller Plugins – erst dann steht fest, ob FluentCart aktiv ist.
 */
add_action('plugins_loaded', function () {
    load_plugin_textdomain('fluentcart-germanized', false, dirname(FCG_BASENAME) . '/languages');

    // FluentCart-Aktiv-Check: Kernklasse oder Haupt-Plugin-Datei.
    $fluentCartActive = defined('FLUENT_CART_PLUGIN_VERSION')
        || class_exists('FluentCart\\App\\App')
        || function_exists('fluentCart');

    if (!$fluentCartActive) {
        add_action('admin_notices', function () {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            echo '<div class="notice notice-warning"><p><strong>FluentCart Germanized</strong> benötigt das aktive Plugin <strong>FluentCart</strong>. Bitte FluentCart installieren/aktivieren.</p></div>';
        });
        return;
    }

    \FluentCartGermanized\Plugin::instance()->boot();
}, 20);
