# Gap-Analyse & Plan — fluentcart-germanized vs. WooCommerce Germanized

Benchmark: **WooCommerce Germanized 4.0.7 (free) + Germanized Pro 4.3.4** (Quelle lokal analysiert).
Ziel: ermitteln, welche Funktionen einen *rechtssicheren DE-Shop* ausmachen, und was unserem FluentCart-Companion-Plugin noch fehlt.

Legende Status: ✅ vorhanden · 🟡 teilweise · ❌ fehlt
Legende Recht: 🔴 Pflicht/Abmahnrisiko · 🟠 situativ Pflicht · ⚪ Komfort/optional

---

## 1. Gap-Matrix

| Bereich | Germanized | fluentcart-germanized | Status | Recht | Notiz |
|---|---|---|---|---|---|
| Bruttopreis + „inkl. MwSt" | Shopmark-System, alle Locations | Label am Produktpreis | 🟡 | 🔴 | uns fehlt Cart/Checkout/Bestellung/Mail |
| „zzgl. Versand" + Link | ja, bedingt physisch | ja, physisch | ✅ | 🔴 | |
| Grundpreis (€/Einheit) | Auto-Berechnung, 8 Locations, Taxonomie `product_unit` | unit/unit_base/unit_product, Produkt+Card | 🟡 | 🔴 | uns fehlt Cart/Checkout-Anzeige |
| **Omnibus: niedrigster Preis 30 Tage** | (Woo/GZD Preis-Historie) | — | ❌ | 🔴 | **PAngV §11 — Abmahnklassiker**, bei reduzierten Artikeln |
| Streichpreis/UVP-Label | `_sale_price_label` Taxonomie | — | ❌ | 🟠 | |
| Lieferzeit | Taxonomie + per-Land + Cart/Checkout/Mail | per-Produkt/Default, nur Produkt | 🟡 | 🔴 | uns fehlt per-Land + Cart/Checkout/Mail |
| Button „zahlungspflichtig bestellen" | Option-Text | gettext-Override | ✅ | 🔴 | live verifiziert |
| Pflicht-Checkboxen | 10+ (terms, download, service, parcel_delivery, privacy, sepa, age, used_goods, defective, photovoltaic) | terms, privacy, shipping_data, digital — cart-bedingt + **Server-Enforcement** + Consent-Log | 🟡 | 🔴 | Kern abgedeckt; fehlen: age_verification, service, used_goods, SEPA |
| Widerrufsbelehrung/-formular | Seite + Formular + Mail | Seite (auto) + Formular + Mail | ✅ | 🔴 | |
| Widerrufsbutton §356a | Free-Package: My-Account, Artikelauswahl, Request-CPT, Admin-Freigabe→Auto-Refund, Status, Mails | eingeloggt 1-Klick je Order + Formular-Fallback + Order-Meta/Notiz + Mail | 🟡 | 🔴 | uns fehlt Admin-Workflow (Request-Verwaltung/Status), Artikelauswahl, Account-Integration |
| Bestätigungsmail-Pflichtinfos | ja | Rechtstexte-Append an FluentCart-Mails | ✅ | 🔴 | |
| §19 Kleinunternehmer | ja | durchgängig (Preis/Mail/Rechnung) | ✅ | 🟠 | |
| Rechnung §14 (fortl. Nr.) | StoreaBill PDF | FluentCart `invoice_no` nativ fortlaufend + §19-Hinweis/Notiz | ✅ | 🔴 | native Nummer reicht §14 |
| **Storno/Gutschrift bei Refund** | StoreaBill Cancellation (negativ, eigene Nummernreihe) | — | ❌ | 🟠 | bei Retouren/Refunds steuerlich nötig |
| ZUGFeRD/XRechnung (E-Rechnung) | StoreaBill eInvoice | — | ❌ | 🟠 | B2B-Empfang Pflicht seit 2025; Ausstellung gestaffelt — für B2C-Shop niedrig |
| Buchhaltungs-Export | lexoffice/sevDesk-Packages | — (Pattern `sevdesk-bridge` vorhanden) | ❌ | ⚪ | separat lösbar |
| **GPSR Produktsicherheit** | Hersteller-Taxonomie + Adresse/EU-Verantwortlicher + Sicherheitshinweise/-dokumente | — | ❌ | 🔴 | **seit 13.12.2024 Pflicht** für physische Produkte |
| Pfand (VerpackG) | deposit_type Taxonomie + sep. Ausweis | — | ❌ | 🟠 | nur wenn pfandpflichtig |
| LMIV (Lebensmittel) | Nährwerte/Allergene/Zutaten/Nutri-Score | — | ❌ | 🟠 | nur bei Lebensmitteln |
| ElektroG/WEEE/BattG | Geräte-/Netzteil-Kennzeichnung | — | ❌ | 🟠 | nur bei Elektro/Batterien |
| Mindestalter-Abfrage | `_min_age` + Checkbox | `_min_age` gespeichert, **keine Abfrage** | 🟡 | 🟠 | Feld da, Checkout-Abfrage fehlt |
| EU-VAT/OSS/VIES B2B Reverse-Charge | eu-tax-helper | FluentCart nativ (EU-VAT/OSS) | 🟡 | 🟠 | Reverse-Charge-Hinweis prüfen |
| Doppel-Opt-In Newsletter | ja | — | ❌ | 🟠 | Zuständigkeit FluentCRM/Mailpoet |
| Preis-Platzierungssystem (Shopmarks) | flexibel, alle Locations | 1 Hook (`after_price`) | 🟡 | — | technische Basis für mehrere Gaps |

---

## 2. Bewertung für bastianbandt.de (B2C, Digital-Alben + 1 Poster)

Sofort relevant: ✅ bereits erledigt (Preis-Label, Button, Checkboxen, Widerruf, §19, Rechnung-§19, Mail).
Noch offen & relevant: **Omnibus 30-Tage-Preis** (sobald Rabatte), **GPSR** (für den Poster/physische Ware), Preis-/Lieferzeit-Anzeige in Cart/Checkout.
Irrelevant hier: LMIV, ElektroG/BattG, Pfand, ZUGFeRD (kein B2B).

---

## 3. Plan

### Phase A — Rechtslücken schließen (generisch, hohe Priorität)
1. **Omnibus „Niedrigster Preis der letzten 30 Tage"** (PAngV §11)
   - Preis-Historie je Produkt/Variante mitschreiben: Hook auf Produkt-Save/Preisänderung → `post_meta _fcg_price_history` (Liste {preis, ts}).
   - Bei reduziertem Artikel min(30 Tage) berechnen + anzeigen via Preis-Hook.
2. **GPSR Produktsicherheit** (Pflicht seit 13.12.2024)
   - Pro-Produkt-Felder (im bestehenden Admin-Screen `ProductFields`): Hersteller (Name/Adresse), EU-Verantwortliche Person, Sicherheitshinweise, Sicherheits-/Doku-Anhänge.
   - Anzeige auf Produktseite via `fluent_cart/product/after_product_content`.
   - Optional: Hersteller als wiederverwendbare Liste (eigener Settings-Bereich).
3. **Placement-Service** (analog Shopmarks)
   - Kleine Klasse, die Label-Renderer (MwSt/Grundpreis/Lieferzeit/Pfand) an FluentCart-Hooks für **Cart-Item, Checkout-Summary, Bestellung/Receipt, E-Mail** hängt.
   - Spike: passende FluentCart-Hooks identifizieren (Cart-Drawer-Item, `checkout/summary_extra_lines`, Receipt-Renderer, Mail-Template).

### Phase B — Workflow & Steuer-Korrektheit
4. **Widerruf-Admin-Workflow**
   - Widerrufs-Anfragen als eigene Liste (Custom-Tabelle/Option oder CPT) mit Status (offen/genehmigt/abgelehnt), Admin-Screen, Status-Mails.
   - Optional Artikelauswahl je Order; Integration in FluentCart-Kundenkonto-Orderansicht (statt nur Shortcode-Seite).
5. **Storno/Gutschrift bei Refund** (§14)
   - Hook auf FluentCart-Refund → Gutschrift-Vermerk (eigene fortl. Nummer `GUT-…`) + Order-Meta + Beleg-Hinweis.
6. **Mindestalter-Abfrage** im Checkout (Feld `_min_age` ist da) — bedingte Pflicht-Checkbox.
7. **Pfand (VerpackG)** — nur falls Sortiment pfandpflichtig: Produkt-Feld + separater Ausweis.

### Phase C — Erweitert / optional
8. **ZUGFeRD/XRechnung** (E-Rechnung) — bei B2B-Bedarf.
9. **Buchhaltungs-Export** — `sevdesk-bridge`-Pattern wiederverwenden.
10. **VIES B2B Reverse-Charge-Hinweis** — erst FluentCart-Nativverhalten prüfen.
11. **Versanddienstleister-Label** — FluentCart-Shipping-Addon-Territorium; unser Consent-Checkbox ist bereits da.

### Nicht im Scope
- LMIV/Lebensmittel, ElektroG/BattG (produktabhängig, bei Bedarf nachrüsten).
- Doppel-Opt-In (FluentCRM/Mailpoet).

---

## 4. Architektur-Leitplanken
- Reines Companion-Plugin (nur Hooks, keine FluentCart-Core-Edits) — beibehalten.
- Meta-Konvention `_fcg_*` (Produkt = WP-postmeta; Order = FluentCart `OrderMeta::updateMeta`).
- Pro-Produkt-Eingaben über eigenen Admin-Screen (FluentCart-Admin ist Vue-SPA).
- Server-seitige Durchsetzung rechtlicher Pflichten (wie Checkbox-Enforcement via `validate_before_process`) — Muster für weitere Pflichten nutzen.
- Verifikation je Feature per Playwright + echte Offline-Testbestellung.

## 5. Empfohlene nächste Schritte
1. **Omnibus 30-Tage-Preis** (Abmahnrisiko, generisch). 
2. **GPSR-Produktfelder + Anzeige** (Pflicht seit 12/2024, betrifft den Poster).
3. **Placement-Service** → MwSt/Grundpreis/Lieferzeit in Cart/Checkout/Mail.
