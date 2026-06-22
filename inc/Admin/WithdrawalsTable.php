<?php

namespace FluentCartGermanized\Admin;

use FluentCartGermanized\Frontend\Withdrawal;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Standard-WP-Listentabelle für Widerrufe: Checkboxen, Bulk-Aktionen
 * (Löschen / Als erledigt markieren), Zeilen-Aktionen (Ansehen/Antworten, Löschen).
 */
class WithdrawalsTable extends \WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'widerruf',
            'plural'   => 'widerrufe',
            'ajax'     => false,
        ]);
    }

    private function load()
    {
        $list = get_option(Withdrawal::OPTION_REQUESTS, []);
        return is_array($list) ? $list : [];
    }

    private function save($list)
    {
        update_option(Withdrawal::OPTION_REQUESTS, array_values($list), false);
    }

    public function get_columns()
    {
        return [
            'cb'     => '<input type="checkbox" />',
            'time'   => __('Zeit (UTC)', 'fluentcart-germanized'),
            'source' => __('Quelle', 'fluentcart-germanized'),
            'name'   => __('Name', 'fluentcart-germanized'),
            'email'  => __('E-Mail', 'fluentcart-germanized'),
            'order'  => __('Bestellung', 'fluentcart-germanized'),
            'status' => __('Status', 'fluentcart-germanized'),
        ];
    }

    public function get_bulk_actions()
    {
        return [
            'mark_handled' => __('Als erledigt markieren', 'fluentcart-germanized'),
            'delete'       => __('Löschen', 'fluentcart-germanized'),
        ];
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="request[]" value="%s" />', esc_attr($item['id'] ?? ''));
    }

    public function column_default($item, $column)
    {
        if ($column === 'status') {
            $s = $item['status'] ?? 'open';
            if ($s === 'handled') {
                return '✓ ' . esc_html__('erledigt', 'fluentcart-germanized');
            }
            if ($s === 'answered') {
                return '✉ ' . esc_html__('beantwortet', 'fluentcart-germanized');
            }
            return esc_html__('offen', 'fluentcart-germanized');
        }
        return esc_html($item[$column] ?? '');
    }

    public function column_name($item)
    {
        $id = $item['id'] ?? '';
        $base = admin_url('admin.php?page=fcg-withdrawals');
        $viewUrl = add_query_arg('view', $id, $base);
        $delUrl = wp_nonce_url(add_query_arg(['fcgdel' => $id], $base), 'fcgdel_' . $id);

        $actions = [
            'view'   => '<a href="' . esc_url($viewUrl) . '">' . esc_html__('Ansehen / Antworten', 'fluentcart-germanized') . '</a>',
            'delete' => '<a href="' . esc_url($delUrl) . '" onclick="return confirm(\'' . esc_js(__('Diesen Widerruf löschen?', 'fluentcart-germanized')) . '\')" style="color:#b32d2e">' . esc_html__('Löschen', 'fluentcart-germanized') . '</a>',
        ];

        return '<strong>' . esc_html($item['name'] ?? '—') . '</strong>' . $this->row_actions($actions);
    }

    /** Bulk-Aktionen + Einzel-Löschung verarbeiten. */
    public function handle_actions()
    {
        // Einzel-Löschung (GET-Link)
        if (isset($_GET['fcgdel'])) {
            $id = sanitize_text_field(wp_unslash($_GET['fcgdel']));
            check_admin_referer('fcgdel_' . $id);
            $list = array_filter($this->load(), function ($r) use ($id) {
                return ($r['id'] ?? '') !== $id;
            });
            $this->save($list);
            wp_safe_redirect(admin_url('admin.php?page=fcg-withdrawals'));
            exit;
        }

        $action = $this->current_action();
        if (!$action) {
            return;
        }
        check_admin_referer('bulk-' . $this->_args['plural']);
        $ids = isset($_REQUEST['request']) ? array_map('sanitize_text_field', (array) wp_unslash($_REQUEST['request'])) : [];
        if (!$ids) {
            return;
        }
        $list = $this->load();

        if ($action === 'delete') {
            $list = array_filter($list, function ($r) use ($ids) {
                return !in_array($r['id'] ?? '', $ids, true);
            });
        } elseif ($action === 'mark_handled') {
            foreach ($list as &$r) {
                if (in_array($r['id'] ?? '', $ids, true)) {
                    $r['status'] = 'handled';
                }
            }
            unset($r);
        }
        $this->save($list);
        wp_safe_redirect(admin_url('admin.php?page=fcg-withdrawals'));
        exit;
    }

    public function prepare_items()
    {
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $this->load();
    }
}
