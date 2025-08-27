<?php
class Holiday_Calendar_Admin {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'holiday_calendar';
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

        $country_id = sanitize_text_field($_GET['country_id'] ?? '');
        $date_q     = sanitize_text_field($_GET['date'] ?? '');
        $paged      = max(1, intval($_GET['paged'] ?? 1));
        $per_page   = 20;
        $offset     = ($paged - 1) * $per_page;

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

        $sql = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS hc.id, hc.`date`, hc.country_id, 
                    hc.created_on, hc.created_by, hc.modified_on, hc.modified_by,
                    c.name as country_name
             FROM {$this->table} hc
             LEFT JOIN {$wpdb->prefix}hfx_country c ON hc.country_id = c.id
             $where_sql
             ORDER BY hc.`date` DESC
             LIMIT %d OFFSET %d",
            ...$params,
            $per_page,
            $offset
        );

        $rows = $wpdb->get_results($sql);
        $total_items = $wpdb->get_var("SELECT FOUND_ROWS()");

        ?>
        <div class="wrap">
            <h1>Feriados <a href="<?php echo esc_url(admin_url('admin.php?page=hca_form')); ?>" class="page-title-action">Agregar nuevo</a></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>País</th>
                    <th>Modificado</th>
                    <th>Modificado por</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($rows): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->date); ?></td>
                            <td><?php echo esc_html($row->country_name ?? $row->country_id); ?></td>
                            <td><?php echo esc_html($row->modified_on); ?></td>
                            <td><?php echo esc_html($row->modified_by); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=hca_form&id=' . $row->id)); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=hca_delete&id=' . $row->id), 'hca_delete'); ?>" onclick="return confirm('¿Eliminar este feriado?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No se encontraron registros.</td></tr>
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
        $this->check_caps();

        global $wpdb;

        $id = sanitize_text_field($_GET['id'] ?? '');
        $row = null;
        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %s", $id));
        }
        $is_edit = (bool)$row;
        $nonce   = wp_create_nonce('hca_save');
        $action_url = admin_url('admin-post.php');

        // Lista de países
        $countries = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hfx_country ORDER BY name ASC");

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
                                <option value="">Seleccione</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo esc_attr($c->id); ?>" <?php selected($row->country_id ?? '', $c->id); ?>>
                                        <?php echo esc_html($c->name); ?>
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

        $now = current_time('mysql', true); // UTC en formato MySQL
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

// Instancia global de la clase para usar en los callbacks de menú
global $hifix_hca;
if (!isset($hifix_hca) || !($hifix_hca instanceof Holiday_Calendar_Admin)) {
    $hifix_hca = new Holiday_Calendar_Admin();
}

/**
 * Callbacks requeridos por add_submenu_page (definidos en menu-dashboard.php)
 */
function hifix_render_feriados_list() {
    global $hifix_hca;
    $hifix_hca->render_list_page();
}

function hifix_render_feriados_form() {
    global $hifix_hca;
    $hifix_hca->render_form_page();
}
