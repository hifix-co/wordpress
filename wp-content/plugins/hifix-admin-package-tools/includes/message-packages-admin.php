<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Message_Packages_Admin {
    private $table;
    private $currency_table;

    public function __construct() {
        global $wpdb;
        $this->table = 'hfx_message_packages';
        $this->currency_table = 'hfx_currency';

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
        $this->check_caps();
        global $wpdb;

        $package_name = sanitize_text_field($_GET['package_name'] ?? '');
        $currency_id  = sanitize_text_field($_GET['currency_id'] ?? '');
        $monthly_message_limit = sanitize_text_field($_GET['monthly_message_limit'] ?? '');
        $price       = sanitize_text_field($_GET['price'] ?? '');
        $page         = max(1, intval($_GET['paged'] ?? 1));
        $per_page     = 20;
        $offset       = ($page - 1) * $per_page;

        $where = [];
        $params = [];

        if ($package_name !== '') {
            $where[]  = "p.package_name LIKE %s";
            $params[] = "%{$package_name}%";
        }
        if ($currency_id !== '') {
            $where[]  = "p.currency_id = %s";
            $params[] = $currency_id;
        }
        if ($monthly_message_limit !== '') {
            $where[]  = "p.monthly_message_limit = %s";
            $params[] = $monthly_message_limit;
        }
        if ($price !== '') {
            $where[]  = "p.price = %s";
            $params[] = $price;
        }

        $where_sql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        $sql = "SELECT SQL_CALC_FOUND_ROWS
                p.id, p.package_name, p.monthly_message_limit, p.price, 
                c.currency_name, c.currency_code, p.modified_on, p.modified_by
            FROM {$this->table} p
            LEFT JOIN {$this->currency_table} c ON p.currency_id = c.id
            {$where_sql}
            ORDER BY p.created_on DESC
            LIMIT %d OFFSET %d
        ";

        $prepare_args = array_merge([$sql], $params, [$per_page, $offset]);
        $prepared_sql = call_user_func_array([$wpdb, 'prepare'], $prepare_args);

        $rows = $wpdb->get_results($prepared_sql);
        $total_items = intval($wpdb->get_var("SELECT FOUND_ROWS()"));
        $total_pages = $per_page ? max(1, ceil($total_items / $per_page)) : 1; 

        $currencies = $wpdb->get_results("SELECT id, currency_name, currency_code FROM {$this->currency_table} ORDER BY currency_name ASC");

        $add_url = admin_url('admin.php?page=message_packages_form');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Paquetes de Mensajes</h1>
            <a href="<?php echo admin_url($add_url); ?>" class="page-title-action">Agregar nuevo paquete</a>
            <hr class="wp-header-end">

            <form method="get" style="margin:10px 0;">
                <input type="hidden" name="page" value="message_packages">
                <label>Moneda:
                    <select name="currency_id">
                        <option value="">-- Todos --</option>
                        <?php foreach ($currencies as $c): ?>
                            <option value="<?php echo esc_attr($c->id); ?>" <?php selected($currency_id, $c->id); ?>>
                                <?php echo esc_html($c->currency_code . " - " . $c->currency_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label style="margin-left:10px;">Nombre:
                    <input type="text" name="package_name" value="<?php echo esc_attr($package_name); ?>">
                </label>

                <button class="button">Filtrar</button>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=message_packages')); ?>">Limpiar</a>
            </form>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Límite mensual</th>
                        <th>Precio</th>
                        <th>Moneda</th>
                        <th>Modificado</th>
                        <th>Modificado por</th>
                        <th style="width:160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows): foreach ($rows as $r):
                        $edit_url = admin_url('admin.php?page=message_packages_form&id=' . urlencode($r->id));
                        $del_action_url = admin_url('admin-post.php?action=mpa_delete&id=' . urlencode($r->id));
                        $del_url = wp_nonce_url($del_action_url, 'mpa_delete');
                    ?>
                        <tr>
                            <td><?php echo esc_html($r->package_name); ?></td>
                            <td><?php echo esc_html($r->monthly_message_limit); ?></td>
                            <td><?php echo esc_html($r->price); ?></td>
                            <td><?php echo esc_html($r->currency_code . " - " . $r->currency_name); ?></td>
                            <td><?php echo esc_html($r->modified_on); ?></td>
                            <td><?php echo esc_html($r->modified_by); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url($edit_url); ?>">Editar</a>
                                <a class="button button-small button-link-delete" href="<?php echo esc_url($del_url); ?>"
                                   onclick="return confirm('¿Eliminar este paquete?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5">Sin resultados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $base = remove_query_arg('paged');
                        for ($p = 1; $p <= $total_pages; $p++):
                            $url = add_query_arg('paged', $p, $base);
                            $class = $p === $page ? ' class="page-numbers current"' : ' class="page-numbers"';
                            echo '<a' . $class . ' href="' . esc_url($url) . '">' . $p . '</a> ';
                        endfor;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /** ---------------- FORMULARIO ---------------- */
    public function render_form_page() {
        $this->check_caps();
        global $wpdb;

        $id = sanitize_text_field($_GET['id'] ?? '');
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %s", $id));
            if (!$row) {
                echo '<div class="notice notice-error"><p>No se encontró el registro.</p></div>';
                $row = null;
            }
        }

        $is_edit = (bool)$row;
        $nonce   = wp_create_nonce('mpa_save');
        $action_url = admin_url('admin-post.php');
        $currencies = $wpdb->get_results("SELECT id, currency_name, currency_code FROM {$this->currency_table} ORDER BY currency_name ASC");

        // Mostrar errores si vienen en query
        if (!empty($_GET['error']) && $_GET['error'] === 'required') {
            echo '<div class="notice notice-error"><p>Todos los campos son obligatorios.</p></div>';
        }
        ?>

        <div class="wrap">
            <h1><?php echo $is_edit ? 'Editar paquete' : 'Agregar paquete'; ?></h1>

            <form method="post" action="<?php echo esc_url($action_url); ?>" style="max-width:600px;">
                <input type="hidden" name="action" value="mpa_save">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="package_name">Nombre del paquete</label></th>
                        <td><input required type="text" id="package_name" name="package_name" value="<?php echo esc_attr($row->package_name ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="monthly_message_limit">Límite mensual de mensajes</label></th>
                        <td><input required type="number" id="monthly_message_limit" name="monthly_message_limit" value="<?php echo esc_attr($row->monthly_message_limit ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price">Precio</label></th>
                        <td><input required type="number" step="0.01" id="price" name="price" value="<?php echo esc_attr($row->price ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency_id">Moneda</label></th>
                        <td>
                            <select required id="currency_id" name="currency_id">
                                <option value="">-- Seleccionar moneda --</option>
                                <?php foreach ($currencies as $c): ?>
                                    <option value="<?php echo esc_attr($c->id); ?>" <?php selected($row->currency_id ?? '', $c->id); ?>>
                                        <?php echo esc_html($c->currency_code . " - " . $c->currency_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button($is_edit ? 'Guardar cambios' : 'Crear paquete'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=message_packages')); ?>">Volver</a>
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
                    'modified_by'           => $user
                ],
                ['id' => $id],
                ['%s','%d','%f','%s','%s','%s'],
                ['%s']
            );
        } else {
            // INSERT
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$this->table} (id, package_name, monthly_message_limit, price, currency_id, created_on, created_by, modified_on, modified_by) 
                     VALUES (UUID(), %s, %d, %f, %s, %s, %s, %s, %s)",
                    $name, $limit, $price, $currency, $now, $user, $now, $user
                )
            );
        }

        wp_redirect(admin_url('admin.php?page=message_packages'));
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
        wp_redirect(admin_url('admin.php?page=message_packages'));
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
