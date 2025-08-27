<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Message_Packages_Admin {
    private $table;
    private $currency_table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . "message_packages";
        $this->currency_table = $wpdb->prefix . "currency";

        add_action('admin_post_mpa_save', [$this, 'handle_save']);
        add_action('admin_post_mpa_delete', [$this, 'handle_delete']);
    }

    private function check_caps() {
        if ( ! current_user_can('manage_options') ) {
            wp_die('No tienes permisos suficientes.');
        }
    }

    /** ---------------- LISTADO ---------------- */
    public function render_list_page() {
        global $wpdb;

        $sql = "SELECT p.id, p.package_name, p.monthly_message_limit, p.price, 
                       c.currency_name, c.currency_code, p.modified_on, p.modified_by
                FROM {$this->table} p
                LEFT JOIN {$this->currency_table} c ON p.currency_id = c.id
                ORDER BY p.created_on DESC";

        $rows = $wpdb->get_results($sql);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Paquetes de Mensajes</h1>
            <a href="<?php echo admin_url('admin.php?page=message_packages_form'); ?>" class="page-title-action">Agregar nuevo</a>
            <hr class="wp-header-end">

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Límite mensual</th>
                        <th>Precio</th>
                        <th>Moneda</th>
                        <th>Modificado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows): foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->package_name); ?></td>
                            <td><?php echo esc_html($row->monthly_message_limit); ?></td>
                            <td><?php echo esc_html($row->price); ?></td>
                            <td><?php echo esc_html($row->currency_code . " - " . $row->currency_name); ?></td>
                            <td><?php echo esc_html($row->modified_on . " por " . $row->modified_by); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=message_packages_form&id=' . $row->id); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mpa_delete&id=' . $row->id), 'mpa_delete'); ?>" onclick="return confirm('¿Seguro de eliminar este paquete?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6">No hay paquetes registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /** ---------------- FORMULARIO ---------------- */
    public function render_form_page() {
        global $wpdb;
        $is_edit = !empty($_GET['id']);
        $row = null;

        if ($is_edit) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %s", $_GET['id']));
        }

        // Cargar lista de monedas
        $currencies = $wpdb->get_results("SELECT id, currency_name, currency_code FROM {$this->currency_table} ORDER BY currency_name ASC");

        $action_url = admin_url('admin-post.php');
        $nonce = wp_create_nonce('mpa_save');
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? 'Editar paquete' : 'Agregar paquete'; ?></h1>

            <form method="post" action="<?php echo esc_url($action_url); ?>" style="max-width:600px;">
                <input type="hidden" name="action" value="mpa_save">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="package_name">Nombre</label></th>
                        <td><input required type="text" id="package_name" name="package_name" value="<?php echo esc_attr($row->package_name ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="monthly_message_limit">Límite mensual</label></th>
                        <td><input required type="number" id="monthly_message_limit" name="monthly_message_limit" value="<?php echo esc_attr($row->monthly_message_limit ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="price">Precio</label></th>
                        <td><input required type="number" step="0.01" id="price" name="price" value="<?php echo esc_attr($row->price ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="currency_id">Moneda</label></th>
                        <td>
                            <select required id="currency_id" name="currency_id">
                                <option value="">-- Seleccionar moneda --</option>
                                <?php foreach ($currencies as $currency): ?>
                                    <option value="<?php echo esc_attr($currency->id); ?>"
                                        <?php selected($row->currency_id ?? '', $currency->id); ?>>
                                        <?php echo esc_html($currency->currency_code . " - " . $currency->currency_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button($is_edit ? 'Guardar cambios' : 'Crear paquete'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=message_packages_list')); ?>">Volver</a>
            </form>
        </div>
        <?php
    }

    /** ---------------- GUARDAR ---------------- */
    public function handle_save() {
        $this->check_caps();

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mpa_save')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;

        $id       = sanitize_text_field($_POST['id'] ?? '');
        $name     = sanitize_text_field($_POST['package_name'] ?? '');
        $limit    = intval($_POST['monthly_message_limit'] ?? 0);
        $price    = floatval($_POST['price'] ?? 0);
        $currency = sanitize_text_field($_POST['currency_id'] ?? '');
        $user     = wp_get_current_user()->user_login;
        $now      = current_time('mysql', true);

        if (!$name || !$limit || !$price || !$currency) {
            wp_redirect(add_query_arg(['page' => 'message_packages_form', 'error' => 'required'], admin_url('admin.php')));
            exit;
        }

        if ($id) {
            // UPDATE
            $wpdb->update(
                $this->table,
                [
                    'package_name'          => $name,
                    'monthly_message_limit' => $limit,
                    'price'                 => $price,
                    'currency_id'           => $currency,
                    'modified_on'           => $now,
                    'modified_by'           => $user,
                ],
                ['id' => $id],
                ['%s','%d','%f','%s','%s','%s'],
                ['%s']
            );
        } else {
            // INSERT
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$this->table} (id, package_name, monthly_message_limit, price, currency_id, created_on, created_by) 
                     VALUES (UUID(), %s, %d, %f, %s, %s, %s)",
                    $name, $limit, $price, $currency, $now, $user
                )
            );
        }

        wp_redirect(admin_url('admin.php?page=message_packages_list'));
        exit;
    }

    /** ---------------- ELIMINAR ---------------- */
    public function handle_delete() {
        $this->check_caps();

        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'mpa_delete')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;
        $id = sanitize_text_field($_GET['id'] ?? '');
        if ($id) {
            $wpdb->delete($this->table, ['id' => $id], ['%s']);
        }
        wp_redirect(admin_url('admin.php?page=message_packages_list'));
        exit;
    }
}

// Instancia global
global $hifix_mpa;
if ( ! isset($hifix_mpa) || ! ($hifix_mpa instanceof Message_Packages_Admin) ) {
    $hifix_mpa = new Message_Packages_Admin();
}

// Callbacks para menús
function hifix_render_message_packages_list() {
    global $hifix_mpa;
    $hifix_mpa->render_list_page();
}
function hifix_render_message_packages_form() {
    global $hifix_mpa;
    $hifix_mpa->render_form_page();
}
