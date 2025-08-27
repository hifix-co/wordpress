<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Holiday_Calendar_Admin {
    private $table;
    private $country_table;

    public function __construct() {
        // Tablas tal como las indicaste en tu BD
        $this->table = 'hfx_holiday_calendar';
        $this->country_table = 'hfx_country';

        // Endpoints para guardar / borrar (admin_post)
        add_action('admin_post_hca_save', [$this, 'handle_save']);
        add_action('admin_post_hca_delete', [$this, 'handle_delete']);
    }

    private function check_caps() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes.');
        }
    }

    /**
     * LISTADO
     */
    public function render_list_page() {
        $this->check_caps();
        global $wpdb;

        // Paginación y filtros
        $country_id = sanitize_text_field($_GET['country_id'] ?? '');
        $date_q     = sanitize_text_field($_GET['date'] ?? '');
        $page       = max(1, intval($_GET['paged'] ?? 1));
        $per_page   = 20;
        $offset     = ($page - 1) * $per_page;

        $where = [];
        $params = [];

        if ($country_id !== '') {
            $where[]  = "hc.country_id = %s";
            $params[] = $country_id;
        }
        if ($date_q !== '') {
            $where[]  = "hc.`date` = %s";
            $params[] = $date_q;
        }

        $where_sql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        // Query con placeholders para prepare()
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
                hc.id,
                hc.`date`,
                c.country_name,
                hc.modified_on,
                hc.modified_by
            FROM {$this->table} hc
            LEFT JOIN {$this->country_table} c ON hc.country_id = c.id
            {$where_sql}
            ORDER BY hc.`date` DESC
            LIMIT %d OFFSET %d
        ";

        // Preparar argumentos para $wpdb->prepare de forma segura
        $prepare_args = array_merge([$sql], $params, [$per_page, $offset]);
        // call_user_func_array permite pasar un array de argumentos a prepare()
        $prepared_sql = call_user_func_array([$wpdb, 'prepare'], $prepare_args);

        $rows = $wpdb->get_results($prepared_sql);
        $total_items = intval($wpdb->get_var("SELECT FOUND_ROWS()"));

        $total_pages = $per_page ? max(1, ceil($total_items / $per_page)) : 1;

        // Para filtro: obtener países
        $countries = $wpdb->get_results("SELECT id, country_name FROM {$this->country_table} ORDER BY country_name ASC");

        $add_url = admin_url('admin.php?page=hca_form');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Administración de feriados</h1>
            <a href="<?php echo esc_url($add_url); ?>" class="page-title-action">Agregar feriado</a>
            <hr class="wp-header-end">

            <form method="get" style="margin-top:10px;margin-bottom:10px;">
                <input type="hidden" name="page" value="hca_list">
                <label>País:
                    <select name="country_id">
                        <option value="">-- Todos --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?php echo esc_attr($c->id); ?>" <?php selected($country_id, $c->id); ?>>
                                <?php echo esc_html($c->country_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label style="margin-left:10px;">Fecha:
                    <input type="date" name="date" value="<?php echo esc_attr($date_q); ?>">
                </label>

                <button class="button">Filtrar</button>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=hca_list')); ?>">Limpiar</a>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>País</th>
                        <th>Modificado</th>
                        <th>Modificado por</th>
                        <th style="width:160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows): foreach ($rows as $r):
                    $edit_url = admin_url('admin.php?page=hca_form&id=' . urlencode($r->id));
                    $del_action_url = admin_url('admin-post.php?action=hca_delete&id=' . urlencode($r->id));
                    $del_url = wp_nonce_url($del_action_url, 'hca_delete');
                ?>
                    <tr>
                        <td><?php echo esc_html($r->date); ?></td>
                        <td><?php echo esc_html($r->country_name ?? $r->country_id); ?></td>
                        <td><?php echo esc_html($r->modified_on); ?></td>
                        <td><?php echo esc_html($r->modified_by); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url($edit_url); ?>">Editar</a>
                            <a class="button button-small button-link-delete" href="<?php echo esc_url($del_url); ?>"
                               onclick="return confirm('¿Eliminar este feriado?');">Eliminar</a>
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

    /**
     * FORMULARIO
     */
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
        $nonce   = wp_create_nonce('hca_save');
        $action_url = admin_url('admin-post.php');

        $countries = $wpdb->get_results("SELECT id, country_name FROM {$this->country_table} ORDER BY country_name ASC");
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? 'Editar feriado' : 'Agregar feriado'; ?></h1>

            <form method="post" action="<?php echo esc_url($action_url); ?>" style="max-width:600px;">
                <input type="hidden" name="action" value="hca_save">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="date">Fecha</label></th>
                        <td><input required type="date" id="date" name="date" value="<?php echo esc_attr($row->date ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="country_id">País</label></th>
                        <td>
                            <select required id="country_id" name="country_id">
                                <option value="">-- Seleccionar país --</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo esc_attr($c->id); ?>" <?php selected($row->country_id ?? '', $c->id); ?>>
                                        <?php echo esc_html($c->country_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button($is_edit ? 'Guardar cambios' : 'Crear feriado'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=hca_list')); ?>">Volver</a>
            </form>
        </div>
        <?php
    }

    /**
     * GUARDAR
     */
    public function handle_save() {
        $this->check_caps();

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'hca_save')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;

        $id          = sanitize_text_field($_POST['id'] ?? '');
        $date        = sanitize_text_field($_POST['date'] ?? '');
        $country_id  = sanitize_text_field($_POST['country_id'] ?? '');
        $created_by  = sanitize_text_field($_POST['created_by'] ?? '');
        $modified_by = sanitize_text_field($_POST['modified_by'] ?? '');

        if (!$date || !$country_id) {
            wp_redirect(add_query_arg(['page' => 'hca_form', 'error' => 'required'], admin_url('admin.php')));
            exit;
        }

        $now = current_time('mysql', true); // UTC MySQL format
        $user_login = wp_get_current_user()->user_login;

        if ($id) {
            // UPDATE
            $wpdb->update(
                $this->table,
                [
                    'date'        => $date,
                    'country_id'  => $country_id,
                    'modified_on' => $now,
                    'modified_by' => $modified_by ?: $user_login,
                ],
                ['id' => $id],
                ['%s','%s','%s','%s'],
                ['%s']
            );
        } else {
            // INSERT (genera UUID)
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$this->table} (id, `date`, country_id, created_on, created_by, modified_on, modified_by)
                     VALUES (UUID(), %s, %s, %s, %s, %s, %s)",
                    $date,
                    $country_id,
                    $now,
                    $created_by ?: $user_login,
                    $now,
                    $modified_by ?: $user_login
                )
            );
        }

        wp_redirect(admin_url('admin.php?page=hca_list'));
        exit;
    }

    /**
     * ELIMINAR
     */
    public function handle_delete() {
        $this->check_caps();

        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'hca_delete')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;
        $id = sanitize_text_field($_GET['id'] ?? '');
        if ($id) {
            $wpdb->delete($this->table, ['id' => $id], ['%s']);
        }
        wp_redirect(admin_url('admin.php?page=hca_list'));
        exit;
    }
}

// Instancia global
global $hifix_hca;
if (!isset($hifix_hca) || !($hifix_hca instanceof Holiday_Calendar_Admin)) {
    $hifix_hca = new Holiday_Calendar_Admin();
}

/**
 * Callbacks requeridos por add_submenu_page (si los usas desde otro archivo)
 */
function hifix_render_feriados_list() {
    global $hifix_hca;
    $hifix_hca->render_list_page();
}

function hifix_render_feriados_form() {
    global $hifix_hca;
    $hifix_hca->render_form_page();
}
