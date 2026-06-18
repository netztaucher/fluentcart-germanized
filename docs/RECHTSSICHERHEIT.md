# Rechtssicherheit mit FluentCart Germanized — Wie das Plugin Compliance herstellt

> **Stand:** Juni 2026 · **Plugin-Version:** 1.0.0
> **Disclaimer:** Dieses Dokument beschreibt die *technische* Umsetzung. Es ist **keine Rechtsberatung**. Texte und Konfiguration müssen vom Betreiber bzw. einer Anwältin/einem Anwalt geprüft werden.

FluentCart ist international ausgelegt und bringt von Haus aus **keine** der deutschen/europäischen Pflicht-Funktionen für den B2C-Verkauf mit. *FluentCart Germanized* ist ein reines **Companion-Plugin**: es hängt sich ausschließlich über offizielle Hooks an FluentCart, verändert **keine** Original-Dateien und ist damit update-sicher.

Dieses Paper erklärt **was** rechtlich gefordert ist und **wie** das Plugin es jeweils technisch löst.

---

## 1. Architekturprinzipien

| Prinzip | Umsetzung |
|---|---|
| **Companion-only** | Nur WordPress-/FluentCart-Hooks (`add_action`/`add_filter`), keine Core-Edits → update-sicher |
| **Datenhaltung** | Produktdaten als WP-`post_meta` (`_fcg_*`), Bestelldaten über FluentCart-`OrderMeta::updateMeta()` |
| **Eingabe-UI** | FluentCart-Admin ist eine Vue-SPA → Pro-Produkt-Felder über **eigenen** Admin-Screen, Rechtstexte über zentrales Panel |
| **Durchsetzung** | Pflichten werden **server-seitig** erzwungen (nicht nur per JS), z. B. Checkbox-Consent über `fluent_cart/checkout/validate_before_process` |
| **Konfigurierbarkeit** | §19/Regelbesteuerung, Labels, Fristen, Texte, Checkboxen alle über Settings |

---

## 2. Preisangaben (PAngV)

### 2.1 Bruttopreis-Kennzeichnung „inkl. MwSt." / §19
**Pflicht:** PAngV §2/§4 — Endpreise inkl. USt. ausweisen; Kleinunternehmer dürfen keine USt. ausweisen (§19 UStG).
**Umsetzung:** Modul `Frontend\PriceLabels` hängt am Hook `fluent_cart/product/after_price` ein Label an den Preis: „inkl. MwSt." bzw. im Kleinunternehmer-Modus „keine USt. gem. §19 UStG". Der Modus ist global in den Settings umschaltbar und wirkt durchgängig (Preis, Mail, Rechnung).

### 2.2 „zzgl. Versand" mit Link
**Pflicht:** PAngV §6 — Hinweis auf zusätzliche Versandkosten + Verlinkung.
**Umsetzung:** `PriceLabels` ergänzt „zzgl. Versand" (verlinkt auf die Versandseite) — **nur bei physischen Artikeln** (digitale Downloads werden über `ProductHelper::isPhysical()` erkannt und ausgenommen).

### 2.3 Grundpreis (€/Einheit)
**Pflicht:** PAngV §4 — Grundpreis je Mengeneinheit.
**Umsetzung:** `Frontend\BasePrice` berechnet aus Pro-Produkt-Feldern (Einheit, Referenzmenge, Füllmenge) den Grundpreis und zeigt ihn am Preis an.

### 2.4 Omnibus — niedrigster Preis der letzten 30 Tage
**Pflicht:** PAngV §11 (Omnibus-Richtlinie) — bei Preisermäßigung den niedrigsten Preis der letzten 30 Tage angeben. **Häufiger Abmahngrund.**
**Umsetzung:** `Frontend\OmnibusPrice` schreibt eine rollierende Preis-Historie (`post_meta _fcg_price_history`, Tages-Minimum) und zeigt bei reduzierten Artikeln „Niedrigster Preis der letzten 30 Tage: …".

---

## 3. Lieferzeit
**Pflicht:** Art. 246a EGBGB — Angabe des Liefertermins/der Lieferzeit.
**Umsetzung:** `Frontend\DeliveryTime` — Lieferzeit pro Produkt oder Standardwert; wahlweise direkt am Preis oder (Default) auf der verlinkten Versandseite. Nur für physische Artikel.

---

## 4. Checkout

### 4.1 Button-Lösung
**Pflicht:** §312j Abs. 3 BGB — der Bestell-Button muss eindeutig auf die Zahlungspflicht hinweisen („Zahlungspflichtig bestellen").
**Umsetzung:** `Frontend\Checkout` setzt den Button-Text über den Filter `fluent_cart/checkout_page_order_button_text` **und** per `gettext`-Override (für den Vue/Modal-Checkout, der den Text lokalisiert ausgibt).

### 4.2 Pflicht-Checkboxen
**Pflicht:** Einwilligung/Information vor Vertragsschluss — AGB & Widerrufsbelehrung, Datenschutz (DSGVO), Datenübergabe an Versanddienstleister (DSGVO), Verzicht aufs Widerrufsrecht bei digitalen Inhalten (§356 Abs. 5 BGB), Altersbestätigung (JMStV).
**Umsetzung:** `Frontend\Checkout` injiziert die Checkboxen per JS in den (Vue-gerenderten) Checkout, **cart-abhängig**:
- Versanddienstleister-Einwilligung nur bei physischen Artikeln,
- Digital-Verzicht nur bei digitalen Artikeln,
- Altersbestätigung nur, wenn ein Artikel ein Mindestalter trägt.
Labels verlinken auf die jeweiligen Rechtstext-Seiten.

### 4.3 Server-seitige Durchsetzung + Nachweis
**Pflicht:** Tatsächliche Einwilligung + Rechenschaftspflicht (DSGVO Art. 7), §312i BGB.
**Umsetzung:** `Order\Consent`
- spiegelt die angehakten Checkboxen in ein Cookie,
- prüft beim Bestellen über `fluent_cart/checkout/validate_before_process` server-seitig, ob alle **erforderlichen** Consents (abhängig von der Warenkorb-Zusammensetzung) vorliegen — fehlt einer, wird die Bestellung mit **HTTP 403** abgewiesen (Client-Bypass nützt nichts),
- protokolliert nach Bestellabschluss die erteilten Consents + Zeitstempel + IP als Order-Meta (`_fcg_consent`).

---

## 5. Widerrufsrecht

### 5.1 Belehrung & Formular (amtliche Muster)
**Pflicht:** Art. 246a EGBGB — Widerrufsbelehrung (Anlage 1) + Muster-Widerrufsformular (Anlage 2).
**Umsetzung:** `LegalText` liefert die **amtlichen Mustertexte** (Warenkauf-Variante) mit eingesetzten Unternehmer-Angaben; Ausgabe über Shortcodes `[fcg_widerrufsbelehrung]` / `[fcg_widerrufsformular_text]`. Im zentralen Rechtstexte-Panel überschreibbar (z. B. Anwaltstext).

### 5.2 Widerrufsbutton & -workflow (§356a BGB)
**Pflicht:** §355/§356a BGB — einfache Ausübung des Widerrufs.
**Umsetzung:** `Frontend\Withdrawal`
- **eingeloggte Kunden** sehen ihre widerrufsfähigen Bestellungen (innerhalb der Frist) und widerrufen mit **einem Klick** (AJAX, Nonce, Ownership-Check); der Widerruf wird am Auftrag protokolliert (Order-Meta + Notiz, im FluentCart-Admin sichtbar) und per E-Mail (FluentSMTP) an Shop + Kunde bestätigt;
- **Gäste / nicht gefundene Bestellungen** nutzen das Muster-Formular → E-Mail-Anfrage an den Shop;
- **Footer-Textlink** „Vertrag widerrufen" (über Enfold-Filter `avf_copyright_info`, Fallback Footer-Menü);
- **Admin-Übersicht** „Widerrufe" listet alle Anfragen mit Status (erledigt-markierbar).

---

## 6. Rechnung (§14 / §19 UStG)
**Pflicht:** §14 UStG — u. a. fortlaufende Rechnungsnummer, USt-Ausweis; §19 — Hinweis bei Kleinunternehmern.
**Umsetzung:**
- Die **fortlaufende Rechnungsnummer** liefert FluentCart bereits nativ (`invoice_no` = Präfix + `receipt_number`).
- `Order\InvoiceFilter` ergänzt über `do_shortcode_tag` am Beleg `[fluent_cart_receipt]` den **§19-Hinweis** (im Kleinunternehmer-Modus) sowie eine frei konfigurierbare Rechnungsnotiz.
- **Storno/Gutschrift:** Bei Erstattung (`fluent_cart/order_refunded`) wird eine fortlaufende Gutschriftnummer (`GUT-JJJJ-NNNNNN`) vergeben, am Auftrag protokolliert und per Mail gemeldet.

---

## 7. E-Mail-Pflichtinhalte
**Pflicht:** §312i BGB / Informationspflichten — Bestätigungsmail mit Rechtstext-Bezug, USt-Hinweis.
**Umsetzung:** `Order\EmailFilter` erkennt FluentCart-Transaktionsmails (Marker) und hängt einen Rechts-Block an: USt-/§19-Hinweis + Links zu Impressum/AGB/Widerruf/Versand + Verweis auf die Widerrufsbelehrung.

---

## 8. Produktsicherheit (GPSR)
**Pflicht:** Verordnung (EU) 2023/988 (GPSR, seit 13.12.2024) — Hersteller (Name/Anschrift), EU-Verantwortliche Person, Sicherheitshinweise.
**Umsetzung:** `Frontend\Gpsr` zeigt diese Angaben auf der Produktseite (`fluent_cart/product/after_product_content`). Globale Defaults in den Settings, pro Produkt überschreibbar (`Admin\ProductFields`).

---

## 9. Rechtstext-Verwaltung
**Pflicht:** Verfügbarkeit/Verlinkung von Impressum, AGB, Widerruf, Datenschutz.
**Umsetzung:**
- `Installer` legt fehlende Rechtstext-Seiten automatisch an (mit den passenden Shortcodes) und verknüpft sie.
- `Legal\Texts` bietet ein **zentrales Panel** (Germanized → Rechtstexte) mit Editor je Text; die Inhalte werden über Shortcodes ausgegeben (`[fcg_agb]`, `[fcg_versand]`, `[fcg_widerrufsbelehrung]`, `[fcg_widerrufsformular_text]`). Widerruf/Formular sind mit dem amtlichen Muster vorbelegt.
- `Legal\Pages` verlinkt die Rechtstexte (Footer/Shortcode `[fcg_legal_links]`).

---

## 10. Kleinunternehmer (§19 UStG)
**Umsetzung:** Globaler Steuermodus in den Settings. Im §19-Modus entfällt der MwSt-Ausweis durchgängig (Preis-Label, Bestätigungsmail, Rechnungsnotiz „Gemäß §19 UStG keine Umsatzsteuer").

---

## 11. Modulübersicht

```
fluentcart-germanized/
  inc/
    Settings.php                Steuermodus, Labels, Checkboxen, GPSR, Unternehmer-Angaben
    Plugin.php                  zentrale Hook-Registrierung
    Installer.php               Auto-Anlage der Rechtstext-Seiten
    LegalText.php               amtliche Muster (Anlage 1/2 EGBGB)
    ProductHelper.php           physisch/digital-Erkennung
    Frontend/
      PriceLabels.php           inkl. MwSt / §19 / zzgl. Versand
      BasePrice.php             Grundpreis (PAngV §4)
      OmnibusPrice.php          30-Tage-Tiefstpreis (PAngV §11)
      DeliveryTime.php          Lieferzeit (Art. 246a EGBGB)
      Checkout.php              Button-Lösung + Pflicht-Checkboxen
      Withdrawal.php            Widerruf: Formular, 1-Klick, Footer, Admin
      Gpsr.php                  Produktsicherheit (VO (EU) 2023/988)
    Admin/
      ProductFields.php         Pro-Produkt-Felder (eigener Screen)
    Order/
      Consent.php               Server-Enforcement + Consent-Log
      InvoiceFilter.php         §19-Hinweis + Storno/Gutschrift
      EmailFilter.php           Rechtstexte in Bestätigungsmails
    Legal/
      Pages.php                 Rechtstext-Verlinkung
      Texts.php                 zentrales Rechtstexte-Panel + Shortcodes
```

---

## 12. Grenzen / nicht abgedeckt
Bewusst **nicht** enthalten (situativ/produktabhängig oder Germanized-*Pro*-Terrain): PDF-Rechnungs-Engine inkl. Storno-PDF, ZUGFeRD/XRechnung (B2B-E-Rechnung), Buchhaltungs-Export (lexoffice/sevDesk/DATEV), Versandlabel/Tracking, VIES-B2B-Reverse-Charge, Pfand (VerpackG), LMIV (Lebensmittel), ElektroG/BattG, Double-Opt-In-Newsletter (Zuständigkeit FluentCRM/Mailpoet).

Siehe auch [`GAP-UND-PLAN.md`](GAP-UND-PLAN.md) und [`GAP-UPDATE-UND-FLUENTCRM-EU.md`](GAP-UPDATE-UND-FLUENTCRM-EU.md).
