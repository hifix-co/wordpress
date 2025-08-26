<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Holiday_Calendar_Admin {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = 'hfx_holiday_calendar';

        add_action('admin_post_hca_save', [$this, 'handle_save']);
        add_action('admin_post_hca_delete', [$this, 'handle_delete']);
    }

    private function check_caps() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes.');
        }
    }

    public function render_list_page() {
        $this->check_caps();
        global $wpdb;

        // Paginación simple
        $per_page = 20;
        $page = max(1, intval($_GET['paged'] ?? 1));
        $offset = ($page - 1) * $per_page;

        // Filtros básicos
        $country = sanitize_text_field($_GET['country'] ?? '');
        $date_q = sanitize_text_field($_GET['date'] ?? '');

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

        $sql = "SELECT SQL_CALC_FOUND_ROWS id, `date`, country, created_on, modified_on 
                FROM {$this->table} 
                $where_sql 
                ORDER BY `date` DESC 
                LIMIT %d OFFSET %d";

        // Añadimos paginación al final
        $params[] = $per_page;
        $params[] = $offset;

        $query = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($query);
        $total = intval($wpdb->get_var("SELECT FOUND_ROWS()"));

        $total_pages = max(1, ceil($total / $per_page));

        $nonce_delete = wp_create_nonce('hca_delete');
        $add_url = admin_url('admin.php?page=hca_form');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Holiday Calendar</h1>
            <a href="<?php echo esc_url($add_url); ?>" class="page-title-action">Agregar feriado</a>
            <hr class="wp-header-end">

            <form method="get" style="margin-top:10px;margin-bottom:10px;">
                <input type="hidden" name="page" value="hca_list">
                <label>País: 
                    <input type="text" name="country" value="<?php echo esc_attr($country); ?>" placeholder="CO">
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
                        <th>Creado</th>
                        <th>Modificado</th>
                        <th style="width:160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows): foreach ($rows as $r): 
                    $edit_url = admin_url('admin.php?page=hca_form&id=' . urlencode($r->id));
                    $del_url = wp_nonce_url(
                        admin_url('admin-post.php?action=hca_delete&id=' . urlencode($r->id)),
                        'hca_delete'
                    );
                ?>
                    <tr>
                        <td><?php echo esc_html($r->date); ?></td>
                        <td><?php echo esc_html($r->country); ?></td>
                        <td><?php echo esc_html($r->created_on); ?></td>
                        <td><?php echo esc_html($r->modified_on); ?></td>
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
                        for ($p=1; $p<=$total_pages; $p++):
                            $url = add_query_arg('paged', $p, $base);
                            $class = $p === $page ? ' class="page-numbers current"' : ' class="page-numbers"';
                            echo '<a'.$class.' href="'.esc_url($url).'">'.$p.'</a> ';
                        endfor;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_form_page() {
        $this->check_caps();
        global $wpdb;

        $id = sanitize_text_field($_GET['id'] ?? '');
        $is_edit = $id !== '';

        $row = null;
        if ($is_edit) {
            $row = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %s", $id)
            );
            if (!$row) {
                echo '<div class="notice notice-error"><p>No se encontró el registro.</p></div>';
                $is_edit = false;
            }
        }

        $nonce = wp_create_nonce('hca_save');
        $action_url = admin_url('admin-post.php');
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
                        <th scope="row"><label for="country">País</label></th>
                        <td><input required type="text" id="country" name="country" value="<?php echo esc_attr($row->country ?? 'CO'); ?>" class="regular-text" maxlength="100"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="created_by">Creado por</label></th>
                        <td><input type="text" id="created_by" name="created_by" value="<?php echo esc_attr($row->created_by ?? wp_get_current_user()->user_login); ?>" class="regular-text" maxlength="100"></td>
                    </tr>
                    <?php if ($is_edit): ?>
                    <tr>
                        <th scope="row"><label for="modified_by">Modificado por</label></th>
                        <td><input type="text" id="modified_by" name="modified_by" value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>" class="regular-text" maxlength="100"></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php submit_button($is_edit ? 'Guardar cambios' : 'Crear feriado'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=hca_list')); ?>">Volver</a>
            </form>
        </div>
        <?php
    }

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

        $now = current_time('mysql', true); // UTC en formato MySQL

        if ($id) {
            // UPDATE
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
            // INSERT (genera UUID)
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
if ( ! isset($hifix_hca) || ! ($hifix_hca instanceof Holiday_Calendar_Admin) ) {
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
