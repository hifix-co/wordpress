<?php
if ( ! defined('ABSPATH') ) exit; // Seguridad

class Holiday_Calendar_Admin {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . "holiday_calendar";

        add_action('admin_post_hca_save', [$this, 'handle_save']);
        add_action('admin_post_hca_delete', [$this, 'handle_delete']);
    }

    private function check_caps() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes.');
        }
    }

    /**
     * Página de listado
     */
    public function render_list_page() {
        global $wpdb;

        // Filtros
        $country = sanitize_text_field($_GET['country'] ?? '');
        $date_q  = sanitize_text_field($_GET['date'] ?? '');

        $where = [];
        $params = [];

        if ($country !== '') {
            $where[] = "country = %s";
            $params[] = $country;
        }
        if ($date_q !== '') {
            $where[] = "`date` = %s";
            $params[] = $date_q;
        }

        $where_sql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        $sql = "SELECT SQL_CALC_FOUND_ROWS id, `date`, country, modified_on, modified_by 
                FROM {$this->table} 
                $where_sql 
                ORDER BY `date` DESC";

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        ?>
        <div class="wrap">
            <h1>Feriados <a href="<?php echo esc_url(admin_url('admin.php?page=hca_form')); ?>" class="page-title-action">Agregar nuevo</a></h1>

            <form method="get">
                <input type="hidden" name="page" value="hca_list">
                <input type="text" name="country" placeholder="País" value="<?php echo esc_attr($country); ?>">
                <input type="date" name="date" value="<?php echo esc_attr($date_q); ?>">
                <button class="button">Filtrar</button>
            </form>

            <table class="widefat fixed striped">
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
                <?php if ($rows): foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row->date); ?></td>
                        <td><?php echo esc_html($row->country); ?></td>
                        <td><?php echo esc_html($row->modified_on); ?></td>
                        <td><?php echo esc_html($row->modified_by); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=hca_form&id=' . $row->id)); ?>">Editar</a> |
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=hca_delete&id=' . $row->id), 'hca_delete'); ?>" onclick="return confirm('¿Eliminar este feriado?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">No se encontraron registros.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Formulario creación/edición
     */
    public function render_form_page() {
        global $wpdb;

        $id = sanitize_text_field($_GET['id'] ?? '');
        $row = null;
        $is_edit = false;

        if ($id) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %s", $id));
            $is_edit = true;
        }

        $action_url = admin_url('admin-post.php');
        $nonce = wp_create_nonce('hca_save');
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? 'Editar feriado' : 'Agregar feriado'; ?></h1>

            <?php if (!empty($_GET['error']) && $_GET['error'] === 'required'): ?>
                <div class="notice notice-error"><p>⚠️ Todos los campos son obligatorios.</p></div>
            <?php endif; ?>

            <?php if (!empty($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
                <div class="notice notice-error"><p>⚠️ Ya existe un feriado con esa fecha para ese país.</p></div>
            <?php endif; ?>

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
                        <th scope="row"><label for="country">País</label></th>
                        <td><input required type="text" id="country" name="country" value="<?php echo esc_attr($row->country ?? 'CO'); ?>" class="regular-text" maxlength="100"></td>
                    </tr>
                </table>

                <?php submit_button($is_edit ? 'Guardar cambios' : 'Crear feriado'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=hca_list')); ?>">Volver</a>
            </form>
        </div>
        <?php
    }

    /**
     * Guardar (insert/update)
     */
    public function handle_save() {
        $this->check_caps();

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'hca_save')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;

        $id          = sanitize_text_field($_POST['id'] ?? '');
        $date        = sanitize_text_field($_POST['date'] ?? '');
        $country     = sanitize_text_field($_POST['country'] ?? '');
        $created_by  = sanitize_text_field($_POST['created_by'] ?? '');
        $modified_by = sanitize_text_field($_POST['modified_by'] ?? '');

        if (!$date || !$country) {
            wp_redirect(add_query_arg(['page' => 'hca_form', 'error' => 'required'], admin_url('admin.php')));
            exit;
        }

        // 🔎 Validación de duplicados
        if ($id) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table} WHERE `date` = %s AND country = %s AND id <> %s",
                    $date, $country, $id
                )
            );
        } else {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table} WHERE `date` = %s AND country = %s",
                    $date, $country
                )
            );
        }

        if ($exists > 0) {
            wp_redirect(add_query_arg(['page' => 'hca_form', 'error' => 'duplicate', 'id' => $id], admin_url('admin.php')));
            exit;
        }

        $now = current_time('mysql', true);

        if ($id) {
            $wpdb->update(
                $this->table,
                [
                    'date'        => $date,
                    'country'     => $country,
                    'modified_on' => $now,
                    'modified_by' => $modified_by ?: wp_get_current_user()->user_login,
                ],
                ['id' => $id],
                ['%s','%s','%s','%s'],
                ['%s']
            );
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$this->table} (id, `date`, country, created_on, created_by) 
                     VALUES (UUID(), %s, %s, %s, %s)",
                    $date,
                    $country,
                    $now,
                    $created_by ?: wp_get_current_user()->user_login
                )
            );
        }

        wp_redirect(admin_url('admin.php?page=hca_list'));
        exit;
    }

    /**
     * Eliminar
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
if ( ! isset($hifix_hca) || ! ($hifix_hca instanceof Holiday_Calendar_Admin) ) {
    $hifix_hca = new Holiday_Calendar_Admin();
}

// Callbacks
function hifix_render_feriados_list() {
    global $hifix_hca;
    $hifix_hca->render_list_page();
}

function hifix_render_feriados_form() {
    global $hifix_hca;
    $hifix_hca->render_form_page();
}
