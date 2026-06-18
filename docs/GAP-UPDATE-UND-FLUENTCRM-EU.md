# Gap-Update (FluentCart) + FluentCRM-EU-Einschätzung

Stand: Juni 2026.

---

## Teil 1 — Gap-Update: fluentcart-germanized vs. WooCommerce Germanized (free 4.0.7 + Pro 4.3.4)

### Seit erster Gap-Analyse zusätzlich umgesetzt (✅)
Omnibus 30-Tage-Preis (PAngV §11) · GPSR-Produktsicherheit · Mindestalter-Checkbox (JMStV) · Storno/Gutschrift-Protokoll bei Refund · Widerruf 1-Klick + Admin-Übersicht · amtliche Muster-Widerrufsbelehrung/-formular (Anlage 1/2 EGBGB) · zentrales Rechtstexte-Panel mit Shortcodes · Preis-Note einzeilig/aus Sticker gelöst.

### Gesamtabdeckung jetzt
| Pflichtbereich | Status |
|---|---|
| Bruttopreis/MwSt/§19 · zzgl. Versand · Grundpreis · Omnibus | ✅ |
| Lieferzeit (optional am Preis, sonst Versandseite) | ✅ |
| Button-Lösung „Zahlungspflichtig bestellen" | ✅ |
| Pflicht-Checkboxen (AGB/Widerruf/Datenschutz/Versand/Digital/Alter) – **server-enforced + Consent-Log** | ✅ (über Germanized free hinaus) |
| Widerruf: Belehrung+Formular (amtlich), 1-Klick, Footer-Link, Admin | ✅ |
| GPSR Produktsicherheit | ✅ |
| Rechtstext-Verwaltung zentral | ✅ |
| Rechnung: fortlaufende Nr. (FluentCart nativ) + §19-Hinweis | ✅ (Basis) |

### Verbleibende Lücken vs. Germanized Pro (🟠 = situativ, ⚪ = optional)
| Feature | Germanized | uns | Bewertung |
|---|---|---|---|
| **PDF-Rechnung (StoreaBill)** mit Vollausweis §14, Storno-PDF, Lieferschein | Pro | FluentCart-HTML-Beleg + §19-Notiz | 🟠 für reinen B2C/Kleinbetrieb meist ausreichend; echte PDF + Storno-PDF fehlt |
| **ZUGFeRD/XRechnung** (E-Rechnung) | Pro | ❌ | 🟠 B2B-Empfang Pflicht seit 2025; B2C niedrig |
| **Buchhaltungs-Export** lexoffice/sevDesk/DATEV | Pro | ❌ (Pattern `sevdesk-bridge` existiert) | ⚪ separat lösbar |
| **Versandlabel/Tracking** (DHL/Shiptastic) | Pro | ❌ | ⚪ FluentCart-Shipping-Addon-Thema |
| **VIES B2B Reverse-Charge** Hinweis/Validierung | free/Pro | FluentCart-nativ EU-VAT/OSS | 🟠 Reverse-Charge-Hinweis ungeprüft |
| **Pfand (VerpackG)** | free | ❌ | 🟠 nur pfandpflichtige Ware |
| **LMIV / ElektroG / BattG** | free/Pro | ❌ | 🟠 produktabhängig (für bastianbandt irrelevant) |
| **Label-Platzierung** in Cart/Checkout/Mail (Shopmark-System) | free | nur Produktseite | ⚪ Komfort/Konsistenz |
| **Multistep-Checkout** | Pro | ❌ | ⚪ UX |

**Fazit Teil 1:** Der rechtskritische B2C-Kern (Abmahnrisiko) ist abgedeckt — teils stärker als Germanized *free* (Server-Enforcement der Checkboxen + Consent-Log). Offen ist überwiegend Germanized-*Pro*-Terrain (E-Rechnung, Buchhaltungs-/Versand-Integrationen) — für bastianbandt nicht erforderlich, für ein verkaufbares Produkt mittelfristig interessant.

---

## Teil 2 — Ehrliche Einschätzung: FluentCRM professionell EU-rechtssicher

### Kurzfazit
**Ja, machbar — und FluentCRM hat dabei einen strukturellen Vorteil:** Es ist **selbst-gehostet**, die Kontaktdaten bleiben in der eigenen WordPress-DB (kein US-SaaS wie Mailchimp/ActiveCampaign → kein automatischer Drittlandtransfer der Kontaktliste). Damit ist die DSGVO-Ausgangslage besser als bei den meisten Cloud-ESP. **Out-of-the-box compliant ist es aber nicht** — es braucht korrekte Konfiguration + einige Härtungen.

### Was FluentCRM nativ kann (Basis ✅)
- **Double-Opt-In** (global + pro Kontakt) — Kern der Einwilligung (UWG §7, DSGVO Art. 6/7).
- **One-Click-Unsubscribe** + Abmeldelink/Manage-Subscription.
- **Compliance-Settings:** IP-Anonymisierung, Kontakt löschen bei User-Löschung, Kontaktdaten in WP-Datenexport (DSAR Art. 15/17/20).
- Konfigurierbarer **E-Mail-Footer** (für Impressum/Abmeldung).

### Lücken für „professionell rechtssicher EU" (🔴 wichtig, 🟠 situativ)
| Thema | Recht | FluentCRM | Härtung nötig |
|---|---|---|---|
| **Einwilligungs-Nachweis / Audit-Trail** (Zeitpunkt, IP, Quelle, exakter Consent-Text, DOI-Klickzeit) | DSGVO Art. 7(1) Rechenschaftspflicht | DOI ja, aber Protokoll dünn | 🔴 vollständiges Consent-Log je Kontakt erzwingen/speichern |
| **Tracking-Einwilligung** (Öffnungs-/Klick-Tracking = personenbezogen) | ePrivacy / TDDDG §25 | Tracking standardmäßig aktiv | 🔴 Tracking nur mit Einwilligung bzw. transparent + abschaltbar |
| **Versand-Infrastruktur / AVV + Drittland** (FluentSMTP → SES/Mailgun/Brevo …) | Art. 28 AVV, Art. 44ff | abhängig vom gewählten Provider | 🔴 EU-Provider wählen + AVV abschließen (z. B. Brevo/Mailjet EU, eigener EU-SMTP) |
| **DOI-Mail neutral** (keine Werbung in der Bestätigungsmail) | BGH-Rspr. | Template frei | 🟠 Template neutral halten |
| **Pflichtangaben in jeder Mail** (Impressum, Absender, Abmeldung, kein irreführender Betreff) | DDG/UWG | Footer konfigurierbar | 🟠 Footer verbindlich befüllen |
| **Löschkonzept / Aufbewahrung** (inaktive/unbestätigte Kontakte, Pending-Bereinigung) | Art. 5(1)(e) Speicherbegrenzung | manuell | 🟠 Automatik: Pending nach X Tagen löschen, Inaktive-Routine |
| **Granulares Preference-Center** (Themen-/Listen-Consent) | Art. 7 / Zweckbindung | Manage-Subscription vorhanden | ⚪ optional, stärkt Einwilligung |
| **Verarbeitungsverzeichnis / Datenschutzerklärung-Bausteine** | Art. 30 / Art. 13 | — | 🟠 organisatorisch + DSE-Text |

### Realistischer Weg zu „professionell rechtssicher" (Aufwand)
1. **Konfiguration (Tag 1, kein Code):** DOI global an; DOI-Mail neutral; Footer mit Impressum+Abmeldung; IP-Anonymisierung an; One-Click-Unsubscribe an; WP-Datenexport an.
2. **Versand EU-konform:** FluentSMTP auf **EU-Provider** mit AVV; SPF/DKIM/DMARC.
3. **Tracking entscheiden:** Öffnungs-/Klick-Tracking aus **oder** consent-gesteuert (TDDDG §25) + in DSE transparent.
4. **Companion-Addon (Code, analog fluentcart-germanized) — die eigentliche Härtung:**
   - **Consent-Log** erzwingen: bei jedem Opt-In (Form/Checkout/Import) Zeitpunkt, IP, Quelle, exakten Einwilligungstext, DOI-Bestätigungszeit speichern + exportierbar.
   - **Tracking-Gate:** Öffnungs-/Klick-Tracking erst nach Cookie-/Consent-Zustimmung.
   - **Retention-Automatik:** Pending-Kontakte nach Frist löschen, Inaktive-Report.
   - **DOI-/Footer-Guard:** sicherstellen, dass Pflicht-Footer + neutrale DOI nie fehlen.
   - Optional: granulares Preference-Center.

### Ehrliche Bewertung
- **Machbarkeit: hoch.** Die Bausteine sind da; das Heikle (Daten bleiben on-premise) ist bei FluentCRM bereits gelöst.
- **Hauptrisiken** liegen weniger im Plugin als in **(a) Versand-Provider/Drittland** und **(b) Tracking-Einwilligung** — beides lösbar (EU-Provider + Tracking-Gate).
- **Schwächster nativer Punkt:** belastbarer **Einwilligungs-Nachweis** (Rechenschaftspflicht). Genau hier bringt ein Companion-Addon den größten Hebel — dasselbe Muster wie beim Checkbox-Consent-Log in fluentcart-germanized.
- **Kein Showstopper.** Mit EU-Versand + Tracking-Gate + Consent-Log ist FluentCRM für den EU-Markt **professionell betreibbar** — eher einfacher als ein US-SaaS rechtssicher zu machen.
