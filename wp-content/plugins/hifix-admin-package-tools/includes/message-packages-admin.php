<?php
/**
 * Admin: CRUD de Paquetes de Mensajes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Hifix_Message_Packages_Admin {
    private $table;
    private $currency_table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . "hfx_message_packages";
        $this->currency_table = $wpdb->prefix . "hfx_currency";

        add_action('admin_post_hmp_save', [$this, 'handle_save']);
        add_action('admin_post_hmp_delete', [$this, 'handle_delete']);
    }

    private function check_caps() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes.');
        }
    }

    /**
     * LISTA
     */
    public function render_list_page() {
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT p.id, p.package_name, p.monthly_message_limit, p.price, 
                   c.currency_code, p.modified_on, p.modified_by
            FROM {$this->table} p
            LEFT JOIN {$this->currency_table} c ON p.currency_id = c.id
            ORDER BY p.package_name ASC
        ");

        ?>
        <div class="wrap">
            <h1>Paquetes de Mensajes <a href="<?php echo esc_url(admin_url('admin.php?page=message_package_form')); ?>" class="page-title-action">Añadir nuevo</a></h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Límite de mensajes</th>
                        <th>Moneda</th>
                        <th>Precio</th>
                        <th>Modificado</th>
                        <th>Modificado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results): ?>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td><?php echo esc_html($r->package_name); ?></td>
                                <td><?php echo esc_html($r->monthly_message_limit); ?></td>
                                <td><?php echo esc_html($r->currency_code); ?></td>
                                <td><?php echo esc_html($r->price); ?></td>
                                <td><?php echo esc_html($r->modified_on); ?></td>
                                <td><?php echo esc_html($r->modified_by); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=message_package_form&id=' . $r->id)); ?>">Editar</a> | 
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hmp_delete&id=' . $r->id), 'hmp_delete')); ?>" onclick="return confirm('¿Eliminar paquete?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No hay paquetes registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * FORMULARIO
     */
    public function render_form_page() {
        global $wpdb;

        $id = sanitize_text_field($_GET['id'] ?? '');
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id=%s", $id));
        }

        $currencies = $wpdb->get_results("SELECT id, currency_name, currency_code FROM {$this->currency_table} ORDER BY currency_name ASC");

        $nonce = wp_create_nonce('hmp_save');
        $action_url = admin_url('admin-post.php');
        $is_edit = (bool)$row;

        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? 'Editar paquete' : 'Agregar paquete'; ?></h1>

            <form method="post" action="<?php echo esc_url($action_url); ?>" style="max-width:600px;">
                <input type="hidden" name="action" value="hmp_save">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="package_name">Nombre</label></th>
                        <td><input required type="text" name="package_name" value="<?php echo esc_attr($row->package_name ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="monthly_message_limit">Límite de mensajes</label></th>
                        <td><input required type="number" name="monthly_message_limit" value="<?php echo esc_attr($row->monthly_message_limit ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="currency_id">Moneda</label></th>
                        <td>
                            <select required name="currency_id">
                                <option value="">Seleccione moneda</option>
                                <?php foreach ($currencies as $c): ?>
                                    <option value="<?php echo esc_attr($c->id); ?>" <?php selected($row->currency_id ?? '', $c->id); ?>>
                                        <?php echo esc_html($c->currency_name . " ({$c->currency_code})"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="price">Precio</label></th>
                        <td><input required type="number" step="0.01" name="price" value="<?php echo esc_attr($row->price ?? ''); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <?php submit_button($is_edit ? 'Guardar cambios' : 'Crear paquete'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=message_packages')); ?>">Volver</a>
            </form>
        </div>
        <?php
    }

    /**
     * SAVE
     */
    public function handle_save() {
        $this->check_caps();

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'hmp_save')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;

        $id          = sanitize_text_field($_POST['id'] ?? '');
        $name        = sanitize_text_field($_POST['package_name'] ?? '');
        $limit       = intval($_POST['monthly_message_limit'] ?? 0);
        $currency_id = sanitize_text_field($_POST['currency_id'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $now         = current_time('mysql', true);
        $user        = wp_get_current_user()->user_login;

        if (!$name || !$limit || !$currency_id || !$price) {
            wp_redirect(add_query_arg(['page' => 'message_package_form', 'error' => 'required'], admin_url('admin.php')));
            exit;
        }

        if ($id) {
            $wpdb->update(
                $this->table,
                [
                    'package_name' => $name,
                    'monthly_message_limit' => $limit,
                    'currency_id' => $currency_id,
                    'price' => $price,
                    'modified_on' => $now,
                    'modified_by' => $user,
                ],
                ['id' => $id],
                ['%s','%d','%s','%f','%s','%s'],
                ['%s']
            );
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$this->table} (id, package_name, monthly_message_limit, currency_id, price, created_on, created_by) 
                     VALUES (UUID(), %s, %d, %s, %f, %s, %s)",
                    $name, $limit, $currency_id, $price, $now, $user
                )
            );
        }

        wp_redirect(admin_url('admin.php?page=message_packages'));
        exit;
    }

    /**
     * DELETE
     */
    public function handle_delete() {
        $this->check_caps();

        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'hmp_delete')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;
        $id = sanitize_text_field($_GET['id'] ?? '');
        if ($id) {
            $wpdb->delete($this->table, ['id' => $id], ['%s']);
        }
        wp_redirect(admin_url('admin.php?page=message_packages'));
        exit;
    }
}

// Instancia global
global $hifix_hmp;
if (!isset($hifix_hmp) || !($hifix_hmp instanceof Hifix_Message_Packages_Admin)) {
    $hifix_hmp = new Hifix_Message_Packages_Admin();
}

/**
 * Callbacks para el menú
 */
function hifix_render_message_packages_list() {
    global $hifix_hmp;
    $hifix_hmp->render_list_page();
}

function hifix_render_message_packages_form() {
    global $hifix_hmp;
    $hifix_hmp->render_form_page();
}
