<?php

namespace FluentCartGermanized\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eigener Admin-Screen für DE-Pflichtfelder pro Produkt.
 * Grund: FluentCart-Produktverwaltung ist eine Vue-SPA – klassische Meta-Boxen
 * erscheinen dort nicht. Daher listet dieser Screen die fluent-products und
 * speichert die Felder als post_meta.
 *
 * Felder: _fcg_unit, _fcg_unit_base, _fcg_unit_product, _fcg_delivery_time,
 *         _fcg_min_age, _fcg_is_digital
 */
class ProductFields
{
    const NONCE = 'fcg_product_fields';
    const POST_TYPE = 'fluent-products';

    public function register()
    {
        add_action('admin_menu', [$this, 'addMenu'], 11);
        add_action('admin_post_fcg_save_product_fields', [$this, 'save']);
    }

    public function addMenu()
    {
        add_submenu_page(
            'fcg-settings',
            __('Produkt-Felder', 'fluentcart-germanized'),
            __('Produkt-Felder', 'fluentcart-germanized'),
            'manage_options',
            'fcg-product-fields',
            [$this, 'renderPage']
        );
    }

    private function products()
    {
        return get_posts([
            'post_type'      => self::POST_TYPE,
            'post_status'    => ['publish', 'draft', 'private'],
            'posts_per_page' => 200,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
    }

    public function renderPage()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $products = $this->products();
        $saved = isset($_GET['fcg_saved']);
        $units = ['', 'kg', 'g', 'l', 'ml', 'm', 'm²', 'Stk'];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Germanized – Produkt-Felder', 'fluentcart-germanized'); ?></h1>
            <?php if ($saved): ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Gespeichert.', 'fluentcart-germanized'); ?></p></div><?php endif; ?>
            <?php if (!$products): ?>
                <p><?php esc_html_e('Keine FluentCart-Produkte gefunden.', 'fluentcart-germanized'); ?></p>
            <?php else: ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="fcg_save_product_fields">
                <?php wp_nonce_field(self::NONCE, '_fcg_nonce'); ?>
                <p class="description"><?php esc_html_e('Grundpreis: Einheit + Füllmenge je Produkt. Lieferzeit überschreibt den Standard. Digital = reiner Download (kein Versand/Grundpreis).', 'fluentcart-germanized'); ?></p>
                <table class="widefat striped">
                    <thead><tr>
                        <th><?php esc_html_e('Produkt', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('Einheit', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('Referenzmenge', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('Füllmenge', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('Lieferzeit', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('Mindestalter', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('Digital', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('GPSR Hersteller (Override)', 'fluentcart-germanized'); ?></th>
                        <th><?php esc_html_e('Sicherheitshinweise', 'fluentcart-germanized'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($products as $p):
                        $id = $p->ID;
                        $unit = get_post_meta($id, '_fcg_unit', true);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($p->post_title); ?></strong><br><small>#<?php echo (int) $id; ?></small></td>
                            <td><select name="fcg[<?php echo (int) $id; ?>][unit]">
                                <?php foreach ($units as $u): ?>
                                    <option value="<?php echo esc_attr($u); ?>" <?php selected($unit, $u); ?>><?php echo $u === '' ? '—' : esc_html($u); ?></option>
                                <?php endforeach; ?>
                            </select></td>
                            <td><input type="number" step="any" style="width:90px" name="fcg[<?php echo (int) $id; ?>][unit_base]" value="<?php echo esc_attr(get_post_meta($id, '_fcg_unit_base', true)); ?>" placeholder="1"></td>
                            <td><input type="number" step="any" style="width:90px" name="fcg[<?php echo (int) $id; ?>][unit_product]" value="<?php echo esc_attr(get_post_meta($id, '_fcg_unit_product', true)); ?>"></td>
                            <td><input type="text" style="width:150px" name="fcg[<?php echo (int) $id; ?>][delivery_time]" value="<?php echo esc_attr(get_post_meta($id, '_fcg_delivery_time', true)); ?>"></td>
                            <td><input type="number" style="width:60px" name="fcg[<?php echo (int) $id; ?>][min_age]" value="<?php echo esc_attr(get_post_meta($id, '_fcg_min_age', true)); ?>"></td>
                            <td><input type="checkbox" name="fcg[<?php echo (int) $id; ?>][is_digital]" value="yes" <?php checked(get_post_meta($id, '_fcg_is_digital', true), 'yes'); ?>></td>
                            <td><input type="text" style="width:150px" name="fcg[<?php echo (int) $id; ?>][gpsr_manufacturer]" value="<?php echo esc_attr(get_post_meta($id, '_fcg_gpsr_manufacturer', true)); ?>" placeholder="<?php esc_attr_e('Standard', 'fluentcart-germanized'); ?>"></td>
                            <td><textarea style="width:200px;height:40px" name="fcg[<?php echo (int) $id; ?>][gpsr_safety]"><?php echo esc_textarea(get_post_meta($id, '_fcg_gpsr_safety', true)); ?></textarea></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button(__('Felder speichern', 'fluentcart-germanized')); ?>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function save()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'fluentcart-germanized'));
        }
        if (!isset($_POST['_fcg_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_fcg_nonce'])), self::NONCE)) {
            wp_die(esc_html__('Sicherheitsprüfung fehlgeschlagen.', 'fluentcart-germanized'));
        }

        $rows = isset($_POST['fcg']) && is_array($_POST['fcg']) ? wp_unslash($_POST['fcg']) : [];
        foreach ($rows as $id => $fields) {
            $id = (int) $id;
            if (get_post_type($id) !== self::POST_TYPE) {
                continue;
            }
            $this->setOrDelete($id, '_fcg_unit', isset($fields['unit']) ? sanitize_text_field($fields['unit']) : '');
            $this->setOrDelete($id, '_fcg_unit_base', isset($fields['unit_base']) && $fields['unit_base'] !== '' ? (float) $fields['unit_base'] : '');
            $this->setOrDelete($id, '_fcg_unit_product', isset($fields['unit_product']) && $fields['unit_product'] !== '' ? (float) $fields['unit_product'] : '');
            $this->setOrDelete($id, '_fcg_delivery_time', isset($fields['delivery_time']) ? sanitize_text_field($fields['delivery_time']) : '');
            $this->setOrDelete($id, '_fcg_min_age', isset($fields['min_age']) && $fields['min_age'] !== '' ? (int) $fields['min_age'] : '');
            $this->setOrDelete($id, '_fcg_is_digital', (isset($fields['is_digital']) && $fields['is_digital'] === 'yes') ? 'yes' : '');
            $this->setOrDelete($id, '_fcg_gpsr_manufacturer', isset($fields['gpsr_manufacturer']) ? sanitize_text_field($fields['gpsr_manufacturer']) : '');
            $this->setOrDelete($id, '_fcg_gpsr_safety', isset($fields['gpsr_safety']) ? sanitize_textarea_field($fields['gpsr_safety']) : '');
        }

        wp_safe_redirect(add_query_arg('fcg_saved', '1', admin_url('admin.php?page=fcg-product-fields')));
        exit;
    }

    private function setOrDelete($id, $key, $value)
    {
        if ($value === '' || $value === null) {
            delete_post_meta($id, $key);
        } else {
            update_post_meta($id, $key, $value);
        }
    }
}
