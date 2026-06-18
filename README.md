<p align="center">
  <img src=".github/banner.jpg" alt="FluentCart Germanized – EU-Rechtssicherheit" width="100%">
</p>

# FluentCart Germanized

Macht [FluentCart](https://fluentcart.com/) rechtssicher für den **deutschen Markt** – analog zu *WooCommerce Germanized* für WooCommerce. Reines **Companion-Plugin**: hängt sich nur per Hooks an FluentCart, ändert keine FluentCart-Originaldateien (update-sicher).

> 🔵 **[netztaucher | digital](https://netztaucher.com/wordpress)** — Dienstleister für **WordPress**, **WooCommerce** und **Verkaufen mit WordPress**. Brauchst du Hilfe bei rechtssicherem E-Commerce? → **[netztaucher.com/wordpress](https://netztaucher.com/wordpress)**

> ⚠️ **Haftungsausschluss:** Dieses Plugin schafft die *technischen* Voraussetzungen für Rechtssicherheit. Die finalen Rechtstexte (AGB, Widerrufsbelehrung, Datenschutz, Impressum) und die Konfiguration müssen vom Betreiber bzw. einem Anwalt geprüft werden. **Keine Rechtsberatung.**

## 📄 Wie wird Rechtssicherheit hergestellt?

Das vollständige Paper erklärt pro Rechtsbereich **was** gefordert ist und **wie** das Plugin es technisch löst (Hooks, Module, Mechanik):

➡️ **[docs/RECHTSSICHERHEIT.md](docs/RECHTSSICHERHEIT.md)** — PAngV (inkl. Omnibus & Grundpreis) · Button-Lösung · server-seitig erzwungene Pflicht-Checkboxen + Consent-Log · Widerruf (amtliche Muster, 1-Klick, Admin) · GPSR · Rechnung §14/§19 · Mail-Pflichtinhalte · §19-Modus.

Weiterführend: [Gap-Analyse vs. WooCommerce Germanized](docs/GAP-UND-PLAN.md) · [Gap-Update + FluentCRM-EU-Einschätzung](docs/GAP-UPDATE-UND-FLUENTCRM-EU.md)

## Status

FluentCart bringt eine solide Steuer-Engine mit (EU-VAT, OSS, Steuerklassen, HTML-Rechnungen) + viele Hooks, aber **keine** DE-spezifische Rechtsebene. Dieses Plugin füllt die Lücke.

| Feature | Rechtsgrundlage | Modul | Status |
|---|---|---|---|
| Preis-Label „inkl. MwSt" / §19 | PAngV §2/§4 | `Frontend\PriceLabels` | ✅ aktiv |
| „zzgl. Versand" + Link (nur physisch) | PAngV §6 | `Frontend\PriceLabels` | ✅ aktiv |
| Grundpreis €/Einheit | PAngV §4 | `Frontend\BasePrice` + `Admin\ProductFields` | ✅ aktiv |
| Omnibus: niedrigster Preis 30 Tage | PAngV §11 | `Frontend\OmnibusPrice` | ✅ aktiv (Preis-Historie passiv, Anzeige bei Rabatt) |
| GPSR Produktsicherheit (Hersteller/EU-Rep/Sicherheitshinweise) | VO (EU) 2023/988 | `Frontend\Gpsr` + Settings/`ProductFields` | ✅ aktiv (global + pro Produkt) |
| Lieferzeit | Art. 246a EGBGB | `Frontend\DeliveryTime` | ✅ aktiv |
| Button „Zahlungspflichtig bestellen" | §312j BGB | `Frontend\Checkout` (Filter + gettext) | ✅ aktiv |
| Rechtstext-Links (Footer/Mail) | §5 DDG u.a. | `Legal\Pages` (`[fcg_legal_links]`) | ✅ aktiv |
| Widerrufsformular + „Vertrag widerrufen" | §355/§356a BGB | `Frontend\Withdrawal` (`[fcg_widerrufsformular]`, `[fcg_widerrufsbutton]`) | ✅ eingeloggt 1-Klick je Order + Gast-Formular + Footer-Link + Admin-Übersicht „Widerrufe" |
| Mindestalter-Bestätigung Checkout | JMStV | `Frontend\Checkout` + `Order\Consent` | ✅ cart-bedingt + server-enforced |
| Storno/Gutschrift bei Refund | §14 UStG | `Order\InvoiceFilter` | ✅ fortl. Gutschriftnr. + Order-Meta/Notiz + Mail |
| Pro-Produkt DE-Felder | — | `Admin\ProductFields` (eigener Screen, da FluentCart-Admin = Vue) | ✅ aktiv |
| §19 Kleinunternehmer-Modus | §19 UStG | `Settings` (durchgängig) | ✅ aktiv |
| Pflicht-Checkboxen (AGB/Widerruf/Datenschutz/Versanddienstleister/Digital-Verzicht) | §312i / §356 BGB / DSGVO | `Frontend\Checkout` + `Order\Consent` | ✅ JS-Injektion + cart-abhängig + **server-seitiges Enforcement** (Cookie → `validate_before_process`) + Protokoll in Order-Meta |
| Rechtstexte in Bestätigungsmail | §312i BGB | `Order\EmailFilter` | ✅ default an; hängt USt-Hinweis + Rechtstext-Links an FluentCart-Mails (Marker-Erkennung „fluentcart.com") |
| Rechnung: fortl. Nr. + §14/§19 | §14/§19 UStG | `Order\InvoiceFilter` | ✅ Nr. ist nativ fortlaufend (`invoice_no`); §19-Hinweis + Rechnungsnotiz via `do_shortcode_tag` an `[fluent_cart_receipt]` |

## Installation

1. Ordner `fluentcart-germanized/` nach `wp-content/plugins/` kopieren.
2. Plugin aktivieren (FluentCart muss aktiv sein).
3. Menü **Germanized** → Steuermodus, Labels, Button-Text, Rechtstext-Seiten zuordnen.
4. **Germanized → Produkt-Felder** → Grundpreis/Lieferzeit je Produkt.

## Architektur

```
fluentcart-germanized.php   Bootstrap, FluentCart-Check, Autoloader
inc/
  Settings.php              Options-API + Admin-Settingseite
  Plugin.php                zentrale Hook-Registrierung
  Installer.php             Auto-Anlage der Rechtstext-Seiten
  LegalText.php             amtliche Muster (Anlage 1/2 EGBGB)
  ProductHelper.php         physisch/digital-Erkennung
  Frontend/  PriceLabels · BasePrice · OmnibusPrice · DeliveryTime · Checkout · Withdrawal · Gpsr
  Admin/     ProductFields  (eigener Screen für post_meta)
  Order/     Consent · InvoiceFilter · EmailFilter
  Legal/     Pages · Texts
```

Pro-Produkt-Daten als `post_meta` auf CPT `fluent-products` (`_fcg_unit`, `_fcg_unit_base`, `_fcg_unit_product`, `_fcg_delivery_time`, `_fcg_min_age`, `_fcg_is_digital`).

## Verifikation (nach Aktivierung)

- Preis-Labels auf Shop + Produktseite sichtbar; §19-Toggle blendet MwSt-Hinweise korrekt um.
- Checkout-Button == „Zahlungspflichtig bestellen".
- Grundpreis korrekt berechnet (Preis ÷ Füllmenge × Referenzmenge).
- Widerrufsformular sendet Mail an Shop + Eingangsbestätigung an Kunde.
- Vor Aktivierung der „Erweitert"-Optionen (Mail/Rechnung): Hooks gegen die laufende FluentCart-Version prüfen.

## Bewusst nicht abgedeckt (Phase C / situativ)

- **PDF-Rechnung / ZUGFeRD / XRechnung** (B2B-E-Rechnung) – für reinen B2C nicht erforderlich.
- **Buchhaltungs-Export** (lexoffice/sevDesk/DATEV).
- **Versandlabel / Tracking** (DHL).
- **VIES B2B Reverse-Charge**-Hinweis.
- **Pfand / LMIV / ElektroG / BattG** – produktabhängig.

Details: [docs/GAP-UND-PLAN.md](docs/GAP-UND-PLAN.md) · [docs/GAP-UPDATE-UND-FLUENTCRM-EU.md](docs/GAP-UPDATE-UND-FLUENTCRM-EU.md)

---

## Über / Support

Entwickelt von **[netztaucher | digital](https://netztaucher.com/wordpress)** — deinem Dienstleister für **WordPress**, **WooCommerce** und **Verkaufen mit WordPress**.

👉 **[netztaucher.com/wordpress](https://netztaucher.com/wordpress)**
