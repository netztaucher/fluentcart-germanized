<?php

namespace FluentCartGermanized\Frontend;

use FluentCartGermanized\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widerruf (§355/§356a BGB).
 *
 * [fcg_widerrufsformular]:
 *   - Eingeloggte Kunden: Liste widerrufsfähiger Bestellungen (innerhalb der Frist)
 *     mit 1-Klick-Widerruf (AJAX). Widerruf wird am Order protokolliert (Meta + Notiz)
 *     -> im Shop/FluentCart-Admin sichtbar, plus E-Mail an Shop + Kunde (FluentSMTP/wp_mail).
 *   - Gäste / keine Order gefunden: Formular (Name + Bestelldatum) -> E-Mail-Anfrage an Shop.
 *
 * [fcg_widerrufsbutton]: „Vertrag widerrufen"-Button -> verlinkt aufs Formular.
 */
class Withdrawal
{
    const NONCE_FORM = 'fcg_widerruf';
    const NONCE_ONECLICK = 'fcg_withdraw_order';

    public function register()
    {
        add_shortcode('fcg_widerrufsformular', [$this, 'formShortcode']);
        add_shortcode('fcg_widerrufsbutton', [$this, 'buttonShortcode']);

        add_action('admin_post_nopriv_fcg_widerruf', [$this, 'handleSubmit']);
        add_action('admin_post_fcg_widerruf', [$this, 'handleSubmit']);
        add_action('wp_ajax_fcg_withdraw_order', [$this, 'ajaxWithdrawOrder']);

        if (Settings::get('withdrawal_button_footer') === 'yes') {
            // Enfold-Footer-Copyright: „Vertrag widerrufen" als Textlink anhängen.
            add_filter('avf_copyright_info', [$this, 'copyrightLink'], 20);
            // Generischer Fallback (Nicht-Enfold-Themes): Footer-Menü-Location.
            add_filter('wp_nav_menu_items', [$this, 'footerMenuLink'], 20, 2);
        }
        add_action('wp_enqueue_scripts', [$this, 'assets']);
    }

    /** Enfold-Copyright-Zeile: Textlink anhängen. */
    public function copyrightLink($copyright)
    {
        $url = $this->formUrl();
        if (!$url) {
            return $copyright;
        }
        return $copyright . ' • <a href="' . esc_url($url) . '">' . esc_html__('Vertrag widerrufen', 'fluentcart-germanized') . '</a>';
    }

    /** Fallback für Themes mit echtem Footer-Menü (Location enthält "footer"/"socket"/avia3). */
    public function footerMenuLink($items, $args)
    {
        $loc = isset($args->theme_location) ? (string) $args->theme_location : '';
        if ($loc !== 'avia3' && stripos($loc, 'footer') === false && stripos($loc, 'socket') === false) {
            return $items;
        }
        $url = $this->formUrl();
        if (!$url) {
            return $items;
        }
        return $items . '<li class="menu-item fcg-menu-widerruf"><a href="' . esc_url($url) . '">' . esc_html__('Vertrag widerrufen', 'fluentcart-germanized') . '</a></li>';
    }

    private function formUrl()
    {
        $pid = (int) Settings::get('page_widerrufsformular');
        return ($pid && get_post_status($pid) === 'publish') ? get_permalink($pid) : '';
    }

    public function assets()
    {
        $css = '.fcg-widerruf-button{display:inline-block;padding:8px 16px;border:1px solid currentColor;border-radius:6px;text-decoration:none;font-size:14px;line-height:1.2}'
            . '.fcg-orders{margin:0 0 24px;border-collapse:collapse;width:100%}'
            . '.fcg-orders th,.fcg-orders td{text-align:left;padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:14px;vertical-align:middle}'
            . '.fcg-orders .fcg-do-withdraw{cursor:pointer;padding:6px 12px;border:0;border-radius:6px;background:#253241;color:#fff;font-size:13px}'
            . '.fcg-orders .fcg-do-withdraw[disabled]{opacity:.5;cursor:default}'
            . '.fcg-orders .fcg-state-done{color:#15803d;font-weight:600}'
            . '.fcg-widerruf-form input,.fcg-widerruf-form textarea{max-width:420px}'
            . '.fcg-withdraw-msg{margin:8px 0;font-size:14px}';
        wp_register_style('fcg-withdrawal', false);
        wp_enqueue_style('fcg-withdrawal');
        wp_add_inline_style('fcg-withdrawal', $css);
    }

    public function buttonShortcode($atts = [])
    {
        $atts = shortcode_atts([
            'text' => __('Vertrag widerrufen', 'fluentcart-germanized'),
            'url'  => '',
        ], $atts, 'fcg_widerrufsbutton');

        $url = $atts['url'];
        if (!$url) {
            $pid = (int) Settings::get('page_widerrufsformular');
            $url = ($pid && get_post_status($pid) === 'publish') ? get_permalink($pid) : '#fcg-widerruf';
        }
        return '<a class="fcg-widerruf-button" href="' . esc_url($url) . '">' . esc_html($atts['text']) . '</a>';
    }

    /* ---------- Frontend-Ausgabe ---------- */

    public function formShortcode($atts = [])
    {
        ob_start();

        if (isset($_GET['fcg_widerruf']) && $_GET['fcg_widerruf'] === 'ok') {
            echo '<div class="fcg-widerruf-ok fcg-withdraw-msg"><strong>' . esc_html__('Ihr Widerruf ist eingegangen. Sie erhalten eine Bestätigung per E-Mail.', 'fluentcart-germanized') . '</strong></div>';
        }

        if (is_user_logged_in()) {
            echo $this->renderOrdersList(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<details class="fcg-manual-toggle" style="margin-top:8px"><summary>' . esc_html__('Bestellung nicht aufgeführt? Widerruf per Formular', 'fluentcart-germanized') . '</summary>';
            echo $this->renderManualForm(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</details>';
        } else {
            echo '<p>' . esc_html__('Tipp: Melden Sie sich an, um Ihre Bestellungen mit einem Klick zu widerrufen. Andernfalls nutzen Sie das Formular.', 'fluentcart-germanized') . '</p>';
            echo $this->renderManualForm(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return ob_get_clean();
    }

    private function renderOrdersList()
    {
        $orders = $this->eligibleOrders();
        if (!$orders) {
            return '<p>' . esc_html__('Keine widerrufsfähigen Bestellungen innerhalb der Widerrufsfrist gefunden.', 'fluentcart-germanized') . '</p>';
        }

        $nonce = wp_create_nonce(self::NONCE_ONECLICK);
        ob_start();
        ?>
        <h3><?php esc_html_e('Ihre widerrufsfähigen Bestellungen', 'fluentcart-germanized'); ?></h3>
        <table class="fcg-orders" data-fcg-orders data-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
            <thead><tr>
                <th><?php esc_html_e('Bestellung', 'fluentcart-germanized'); ?></th>
                <th><?php esc_html_e('Datum', 'fluentcart-germanized'); ?></th>
                <th><?php esc_html_e('Betrag', 'fluentcart-germanized'); ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($orders as $o):
                $no = $o->invoice_no ?: ('#' . $o->id);
                $date = $this->fmtDate($o->created_at);
                $amount = $this->fmtMoney($o->total_amount);
                ?>
                <tr data-uuid="<?php echo esc_attr($o->uuid); ?>">
                    <td><?php echo esc_html($no); ?></td>
                    <td><?php echo esc_html($date); ?></td>
                    <td><?php echo esc_html($amount); ?></td>
                    <td>
                        <button type="button" class="fcg-do-withdraw"><?php esc_html_e('Widerrufen', 'fluentcart-germanized'); ?></button>
                        <span class="fcg-row-state"></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <script>
        (function(){
            var t=document.querySelector('[data-fcg-orders]'); if(!t) return;
            t.addEventListener('click', function(e){
                var btn=e.target.closest('.fcg-do-withdraw'); if(!btn) return;
                var row=btn.closest('tr'); var uuid=row.getAttribute('data-uuid');
                if(!confirm(<?php echo wp_json_encode(__('Diese Bestellung verbindlich widerrufen?', 'fluentcart-germanized')); ?>)) return;
                btn.disabled=true;
                var body=new URLSearchParams();
                body.append('action','fcg_withdraw_order');
                body.append('_nonce',t.getAttribute('data-nonce'));
                body.append('uuid',uuid);
                fetch(t.getAttribute('data-ajax'),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
                 .then(function(r){return r.json();})
                 .then(function(d){
                    var st=row.querySelector('.fcg-row-state');
                    if(d&&d.ok){ btn.remove(); st.className='fcg-row-state fcg-state-done'; st.textContent=<?php echo wp_json_encode(__('✓ widerrufen', 'fluentcart-germanized')); ?>; }
                    else { btn.disabled=false; st.textContent=(d&&d.message)?d.message:'Fehler'; }
                 })
                 .catch(function(){ btn.disabled=false; });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private function renderManualForm()
    {
        ob_start();
        ?>
        <form class="fcg-widerruf-form" id="fcg-widerruf" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="fcg_widerruf">
            <?php wp_nonce_field(self::NONCE_FORM, '_fcg_nonce'); ?>
            <p><em><?php esc_html_e('Muster-Widerrufsformular (Anlage 2 zu Art. 246a EGBGB). Bitte ausfüllen und absenden.', 'fluentcart-germanized'); ?></em></p>
            <p><label><?php esc_html_e('Name', 'fluentcart-germanized'); ?><br><input type="text" name="fcg_name" required></label></p>
            <p><label><?php esc_html_e('E-Mail', 'fluentcart-germanized'); ?><br><input type="email" name="fcg_email" required></label></p>
            <p><label><?php esc_html_e('Bestellnummer (optional)', 'fluentcart-germanized'); ?><br><input type="text" name="fcg_order"></label></p>
            <p><label><?php esc_html_e('Bestelldatum', 'fluentcart-germanized'); ?><br><input type="text" name="fcg_dates" placeholder="TT.MM.JJJJ" required></label></p>
            <p><label><input type="checkbox" name="fcg_confirm" value="yes" required> <?php esc_html_e('Hiermit widerrufe ich den von mir abgeschlossenen Vertrag über den Kauf der folgenden Waren/Dienstleistungen.', 'fluentcart-germanized'); ?></label></p>
            <p><button type="submit"><?php esc_html_e('Widerruf absenden', 'fluentcart-germanized'); ?></button></p>
        </form>
        <?php
        return ob_get_clean();
    }

    /* ---------- Bestellungen ---------- */

    /** @return array Liste widerrufsfähiger Order-Objekte des aktuellen Kunden */
    private function eligibleOrders()
    {
        $customer = $this->currentCustomer();
        if (!$customer || !class_exists('\\FluentCart\\App\\Models\\Order')) {
            return [];
        }
        try {
            $cutoff = gmdate('Y-m-d H:i:s', time() - $this->windowDays() * DAY_IN_SECONDS);
            $orders = \FluentCart\App\Models\Order::query()
                ->where('customer_id', $customer->id)
                ->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', '');
                })
                ->where('created_at', '>=', $cutoff)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        } catch (\Throwable $e) {
            return [];
        }

        $success = $this->successStatuses();
        $out = [];
        foreach ($orders as $o) {
            if (!in_array($o->status, $success, true)) {
                continue;
            }
            if ($o->getMeta('_fcg_withdrawal')) {
                continue; // bereits widerrufen
            }
            $out[] = $o;
        }
        return $out;
    }

    public function ajaxWithdrawOrder()
    {
        if (!is_user_logged_in()) {
            wp_send_json(['ok' => false, 'message' => __('Bitte anmelden.', 'fluentcart-germanized')], 403);
        }
        if (!isset($_POST['_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_nonce'])), self::NONCE_ONECLICK)) {
            wp_send_json(['ok' => false, 'message' => __('Sicherheitsprüfung fehlgeschlagen.', 'fluentcart-germanized')], 403);
        }

        $uuid = isset($_POST['uuid']) ? sanitize_text_field(wp_unslash($_POST['uuid'])) : '';
        $customer = $this->currentCustomer();
        if (!$uuid || !$customer) {
            wp_send_json(['ok' => false, 'message' => __('Bestellung nicht gefunden.', 'fluentcart-germanized')], 404);
        }

        $order = \FluentCart\App\Models\Order::query()
            ->where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$order) {
            wp_send_json(['ok' => false, 'message' => __('Bestellung nicht gefunden.', 'fluentcart-germanized')], 404);
        }
        if (!in_array($order->status, $this->successStatuses(), true)) {
            wp_send_json(['ok' => false, 'message' => __('Diese Bestellung ist nicht widerrufsfähig.', 'fluentcart-germanized')], 422);
        }
        if ($order->getMeta('_fcg_withdrawal')) {
            wp_send_json(['ok' => true, 'message' => __('Bereits widerrufen.', 'fluentcart-germanized')], 200);
        }

        $this->recordWithdrawal($order, $customer);

        wp_send_json(['ok' => true], 200);
    }

    private function recordWithdrawal($order, $customer)
    {
        $stamp = gmdate('Y-m-d H:i:s');
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        // 1) Am Order protokollieren (im FluentCart-Admin sichtbar)
        try {
            $order->updateMeta('_fcg_withdrawal', ['time' => $stamp, 'ip' => $ip, 'source' => 'one-click']);
            $note = (string) $order->note;
            $order->note = trim($note . "\n[" . $stamp . " UTC] " . __('Widerruf durch Kunde (Online-Widerrufsbutton).', 'fluentcart-germanized'));
            $order->save();
        } catch (\Throwable $e) {
            // protokollieren best-effort
        }

        // 2) E-Mails (FluentSMTP über wp_mail)
        $no = $order->invoice_no ?: ('#' . $order->id);
        $custName = method_exists($customer, 'getAttribute') ? trim($customer->first_name . ' ' . $customer->last_name) : '';
        $custEmail = $customer->email ?? '';

        $shopBody = implode("\n", [
            __('Eingang eines Widerrufs (1-Klick über Kundenkonto):', 'fluentcart-germanized'),
            '',
            __('Bestellung:', 'fluentcart-germanized') . ' ' . $no,
            __('Kunde:', 'fluentcart-germanized') . ' ' . $custName,
            __('E-Mail:', 'fluentcart-germanized') . ' ' . $custEmail,
            __('Zeitpunkt:', 'fluentcart-germanized') . ' ' . $stamp . ' UTC',
        ]);
        wp_mail($this->shopEmail(), sprintf(__('Widerruf – Bestellung %s', 'fluentcart-germanized'), $no), $shopBody);

        if ($custEmail && is_email($custEmail)) {
            wp_mail(
                $custEmail,
                __('Eingangsbestätigung Ihres Widerrufs', 'fluentcart-germanized'),
                sprintf(__("Wir bestätigen den Eingang Ihres Widerrufs zur Bestellung %s. Wir melden uns zur weiteren Abwicklung.", 'fluentcart-germanized'), $no)
            );
        }
    }

    /* ---------- Gäste-Formular ---------- */

    public function handleSubmit()
    {
        if (!isset($_POST['_fcg_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_fcg_nonce'])), self::NONCE_FORM)) {
            wp_die(esc_html__('Sicherheitsprüfung fehlgeschlagen.', 'fluentcart-germanized'));
        }

        $name  = isset($_POST['fcg_name']) ? sanitize_text_field(wp_unslash($_POST['fcg_name'])) : '';
        $email = isset($_POST['fcg_email']) ? sanitize_email(wp_unslash($_POST['fcg_email'])) : '';
        $order = isset($_POST['fcg_order']) ? sanitize_text_field(wp_unslash($_POST['fcg_order'])) : '';
        $dates = isset($_POST['fcg_dates']) ? sanitize_text_field(wp_unslash($_POST['fcg_dates'])) : '';

        $body = implode("\n", [
            __('Widerruf-Anfrage über das Formular:', 'fluentcart-germanized'),
            '',
            __('Name:', 'fluentcart-germanized') . ' ' . $name,
            __('E-Mail:', 'fluentcart-germanized') . ' ' . $email,
            __('Bestellnummer:', 'fluentcart-germanized') . ' ' . $order,
            __('Bestelldatum:', 'fluentcart-germanized') . ' ' . $dates,
        ]);

        wp_mail($this->shopEmail(), sprintf(__('Widerruf-Anfrage – %s', 'fluentcart-germanized'), $name), $body);
        if ($email && is_email($email)) {
            wp_mail($email, __('Eingangsbestätigung Ihres Widerrufs', 'fluentcart-germanized'), __('Wir bestätigen den Eingang Ihrer Widerruf-Anfrage. Details:', 'fluentcart-germanized') . "\n\n" . $body);
        }

        $back = wp_get_referer() ?: home_url('/');
        wp_safe_redirect(add_query_arg('fcg_widerruf', 'ok', $back));
        exit;
    }

    /* ---------- Helpers ---------- */

    private function currentCustomer()
    {
        try {
            if (class_exists('\\FluentCart\\Api\\Resource\\CustomerResource')) {
                return \FluentCart\Api\Resource\CustomerResource::getCurrentCustomer();
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    private function successStatuses()
    {
        try {
            if (class_exists('\\FluentCart\\App\\Helpers\\Status') && method_exists('\\FluentCart\\App\\Helpers\\Status', 'getOrderSuccessStatuses')) {
                $s = \FluentCart\App\Helpers\Status::getOrderSuccessStatuses();
                if (is_array($s) && $s) {
                    return $s;
                }
            }
        } catch (\Throwable $e) {
        }
        return ['paid', 'completed', 'processing', 'on-hold'];
    }

    private function windowDays()
    {
        $d = (int) Settings::get('withdrawal_window_days');
        return $d > 0 ? $d : 14;
    }

    private function shopEmail()
    {
        $e = trim((string) Settings::get('withdrawal_email'));
        return ($e && is_email($e)) ? $e : get_option('admin_email');
    }

    private function fmtDate($gmt)
    {
        $ts = strtotime((string) $gmt);
        return $ts ? wp_date(get_option('date_format'), $ts) : (string) $gmt;
    }

    private function fmtMoney($minor)
    {
        if (class_exists('\\FluentCart\\App\\Helpers\\Helper') && method_exists('\\FluentCart\\App\\Helpers\\Helper', 'toDecimal')) {
            try {
                return \FluentCart\App\Helpers\Helper::toDecimal((int) $minor) . ' ' . apply_filters('fcg/currency_symbol', '€');
            } catch (\Throwable $e) {
            }
        }
        return number_format(((int) $minor) / 100, 2, ',', '.') . ' €';
    }
}
