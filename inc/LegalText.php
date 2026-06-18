<?php

namespace FluentCartGermanized;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Amtliche Mustertexte:
 *  - Anlage 1 zu Art. 246a §1 Abs. 2 EGBGB (Muster-Widerrufsbelehrung, Warenkauf-Variante)
 *  - Anlage 2 zu Art. 246a §1 Abs. 2 EGBGB (Muster-Widerrufsformular)
 *
 * Hinweis: Standard ist die Variante für den Kauf von Waren (ein Standardpaket).
 * Für Dienstleistungen / digitale Inhalte / mehrteilige Lieferungen sind die
 * Gestaltungshinweise abweichend — bitte anwaltlich prüfen/anpassen.
 *
 * Platzhalter {UNTERNEHMER} wird aus Settings ('seller_block') befüllt.
 */
class LegalText
{
    public static function unternehmer()
    {
        $block = trim((string) Settings::get('seller_block'));
        if ($block !== '') {
            return nl2br(esc_html($block));
        }
        // Fallback aus WordPress
        $name = get_bloginfo('name');
        $mail = get_option('admin_email');
        return esc_html($name) . '<br>' . esc_html__('[Anschrift ergänzen]', 'fluentcart-germanized') . '<br>' . esc_html($mail);
    }

    /** Anlage 1 — Muster-Widerrufsbelehrung (Warenkauf). Gibt HTML zurück. */
    public static function widerrufsbelehrung()
    {
        $u = self::unternehmer();

        $h = '<div class="fcg-widerrufsbelehrung">';
        $h .= '<h2>' . esc_html__('Widerrufsbelehrung', 'fluentcart-germanized') . '</h2>';

        $h .= '<h3>' . esc_html__('Widerrufsrecht', 'fluentcart-germanized') . '</h3>';
        $h .= '<p>' . esc_html__('Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen.', 'fluentcart-germanized') . '</p>';
        $h .= '<p>' . esc_html__('Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag, an dem Sie oder ein von Ihnen benannter Dritter, der nicht der Beförderer ist, die Waren in Besitz genommen haben bzw. hat.', 'fluentcart-germanized') . '</p>';
        $h .= '<p>' . sprintf(
            /* translators: %s seller identity block */
            esc_html__('Um Ihr Widerrufsrecht auszuüben, müssen Sie uns (%s) mittels einer eindeutigen Erklärung (z. B. ein mit der Post versandter Brief oder eine E-Mail) über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren. Sie können dafür das beigefügte Muster-Widerrufsformular verwenden, das jedoch nicht vorgeschrieben ist.', 'fluentcart-germanized'),
            '<br>' . $u . '<br>'
        ) . '</p>';
        $h .= '<p>' . esc_html__('Zur Wahrung der Widerrufsfrist reicht es aus, dass Sie die Mitteilung über die Ausübung des Widerrufsrechts vor Ablauf der Widerrufsfrist absenden.', 'fluentcart-germanized') . '</p>';

        $h .= '<h3>' . esc_html__('Folgen des Widerrufs', 'fluentcart-germanized') . '</h3>';
        $h .= '<p>' . esc_html__('Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von Ihnen erhalten haben, einschließlich der Lieferkosten (mit Ausnahme der zusätzlichen Kosten, die sich daraus ergeben, dass Sie eine andere Art der Lieferung als die von uns angebotene, günstigste Standardlieferung gewählt haben), unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag zurückzuzahlen, an dem die Mitteilung über Ihren Widerruf dieses Vertrags bei uns eingegangen ist. Für diese Rückzahlung verwenden wir dasselbe Zahlungsmittel, das Sie bei der ursprünglichen Transaktion eingesetzt haben, es sei denn, mit Ihnen wurde ausdrücklich etwas anderes vereinbart; in keinem Fall werden Ihnen wegen dieser Rückzahlung Entgelte berechnet.', 'fluentcart-germanized') . '</p>';
        $h .= '<p>' . esc_html__('Wir können die Rückzahlung verweigern, bis wir die Waren wieder zurückerhalten haben oder bis Sie den Nachweis erbracht haben, dass Sie die Waren zurückgesandt haben, je nachdem, welches der frühere Zeitpunkt ist.', 'fluentcart-germanized') . '</p>';
        $h .= '<p>' . esc_html__('Sie haben die Waren unverzüglich und in jedem Fall spätestens binnen vierzehn Tagen ab dem Tag, an dem Sie uns über den Widerruf dieses Vertrags unterrichten, an uns zurückzusenden oder zu übergeben. Die Frist ist gewahrt, wenn Sie die Waren vor Ablauf der Frist von vierzehn Tagen absenden.', 'fluentcart-germanized') . '</p>';
        $h .= '<p>' . esc_html__('Sie tragen die unmittelbaren Kosten der Rücksendung der Waren.', 'fluentcart-germanized') . '</p>';
        $h .= '<p>' . esc_html__('Sie müssen für einen etwaigen Wertverlust der Waren nur aufkommen, wenn dieser Wertverlust auf einen zur Prüfung der Beschaffenheit, Eigenschaften und Funktionsweise der Waren nicht notwendigen Umgang mit ihnen zurückzuführen ist.', 'fluentcart-germanized') . '</p>';

        $h .= '<p><em>' . esc_html__('Hinweis: Dieser Mustertext gilt für den Kauf von Waren. Für Dienstleistungen, digitale Inhalte oder mehrteilige Lieferungen ist er anzupassen (bitte anwaltlich prüfen).', 'fluentcart-germanized') . '</em></p>';
        $h .= '</div>';

        return $h;
    }

    /** Anlage 2 — Muster-Widerrufsformular (statischer Text). */
    public static function widerrufsformularText()
    {
        $u = self::unternehmer();
        $h = '<div class="fcg-widerrufsformular-text">';
        $h .= '<p><em>' . esc_html__('(Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus und senden Sie es zurück.)', 'fluentcart-germanized') . '</em></p>';
        $h .= '<p>' . esc_html__('An:', 'fluentcart-germanized') . '<br>' . $u . '</p>';
        $h .= '<p>' . nl2br(esc_html(
            __("Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über den Kauf der folgenden Waren (*)/die Erbringung der folgenden Dienstleistung (*):\n\n"
            . "Bestellt am (*)/erhalten am (*):\n\n"
            . "Name des/der Verbraucher(s):\n\n"
            . "Anschrift des/der Verbraucher(s):\n\n"
            . "Unterschrift des/der Verbraucher(s) (nur bei Mitteilung auf Papier):\n\n"
            . "Datum:\n\n"
            . "(*) Unzutreffendes streichen.", 'fluentcart-germanized')
        )) . '</p>';
        $h .= '</div>';
        return $h;
    }
}
