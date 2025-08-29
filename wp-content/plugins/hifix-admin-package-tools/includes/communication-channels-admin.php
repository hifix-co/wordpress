<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Communication_Channels_Admin {
    private $table;
    private $currency_table;

    public function __construct() {
        global $wpdb;
        $this->table = 'hfx_communication_channels';
        $this->currency_table = 'hfx_currency';

        add_action('admin_post_cca_save', [$this, 'handle_save']);
        add_action('admin_post_cca_delete', [$this, 'handle_delete']);
    }

    private function check_caps() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No tienes permisos suficientes.' );
        }
    }

    /** ---------------- LISTADO ---------------- */
    public function render_list_page() {
        $this->check_caps();
        global $wpdb;

        $channel_name = sanitize_text_field( $_GET['channel_name'] ?? '' );
        $currency_id  = sanitize_text_field( $_GET['currency_id'] ?? '' );
        $price        = sanitize_text_field( $_GET['price'] ?? '' );
        $page         = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $per_page     = 20;
        $offset       = ( $page - 1 ) * $per_page;

        $where = [];
        $params = [];

        if ( $channel_name !== '' ) {
            $where[] = 'p.channel_name LIKE %s';
            $params[] = "%{$channel_name}%";
        }
        if ( $currency_id !== '' ) {
            $where[] = 'p.currency_id = %s';
            $params[] = $currency_id;
        }
        if ( $price !== '' ) {
            $where[] = 'p.price = %s';
            $params[] = $price;
        }

        $where_sql = $where ? ( "WHERE " . implode( " AND ", $where ) ) : "";

        $sql = "SELECT SQL_CALC_FOUND_ROWS
                p.id, p.channel_name, p.price,
                c.currency_name, c.currency_code, p.modified_on, p.modified_by
                FROM {$this->table} p
                LEFT JOIN {$this->currency_table} c ON p.currency_id = c.id
                {$where_sql}
                ORDER BY p.created_on DESC
                LIMIT %d OFFSET %d
            ";

            $prepare_args = array_merge( [$sql], $params, [$per_page, $offset]);
            $prepared_sql = call_user_func_array( [$wpdb, 'prepare'], $prepare_args);

            $rows = $wpdb->get_results( $prepared_sql);
            $total_items = intval( $wpdb->get_var( "SELECT FOUND_ROWS()" ) );
            $total_pages = $per_page ? max( 1, ceil( $total_items / $per_page ) ) : 1;

            $currencies = $wpdb->get_results( "SELECT id, currency_name, currency_code FROM {$this->currency_table} ORDER BY currency_name ASC" );

            $add_url = admin_url( 'admin.php?page=communication_channels_form' );
            ?>
            <div class="wrap">
            <h1 class="wp-heading-inline">Canales de comunicación</h1>
            <a href="<?php echo esc_url($add_url); ?>" class="page-title-action">Agregar nuevo canal</a>
            <hr class="wp-header-end">

            <form method="get" style="margin:10px 0;">
                <input type="hidden" name="page" value="communication_channels">
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
                    <input type="text" name="channel_name" value="<?php echo esc_attr($channel_name); ?>">
                </label>

                <button class="button">Filtrar</button>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=communication_channels')); ?>">Limpiar</a>
            </form>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Moneda</th>
                        <th>Modificado</th>
                        <th>Modificado por</th>
                        <th style="width:160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows): foreach ($rows as $r):
                        $edit_url = admin_url('admin.php?page=communication_channels_form&id=' . urlencode($r->id));
                        $del_action_url = admin_url('admin-post.php?action=cca_delete&id=' . urlencode($r->id));
                        $del_url = wp_nonce_url($del_action_url, 'cca_delete');
                    ?>
                        <tr>
                            <td><?php echo esc_html($r->channel_name); ?></td>
                            <td><?php echo esc_html($r->price); ?></td>
                            <td><?php echo esc_html($r->currency_code . " - " . $r->currency_name); ?></td>
                            <td><?php echo esc_html($r->modified_on); ?></td>
                            <td><?php echo esc_html($r->modified_by); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url($edit_url); ?>">Editar</a>
                                <a class="button button-small button-link-delete" href="<?php echo esc_url($del_url); ?>"
                                   onclick="return confirm('¿Eliminar este canal?');">Eliminar</a>
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
                echo '<div class="error"><p>Registro no encontrado</p></div>';
                $row = null;
            }
        }

        $is_edit = (bool)$row;
        $nonce   = wp_create_nonce('cca_save');
        $action_url = admin_url('admin-post.php');
        $currencies = $wpdb->get_results("SELECT id, currency_name, currency_code FROM {$this->currency_table} ORDER BY currency_name ASC");

        // Mostrar errores si vienen en query
        if (!empty($_GET['error']) && $_GET['error'] === 'duplicated') {
            echo '<div class="notice notice-error"><p>Ya existe un canal de comunicación con ese nombre para la moneda seleccionada.</p></div>';
        }

        if (!empty($_GET['error']) && $_GET['error'] === 'required') {
            echo '<div class="notice notice-error"><p>Todos los campos son obligatorios.</p></div>';
        }
        ?>

        <div class="wrap">
            <h1><?php echo $is_edit ? 'Editar canal' : 'Agregar canal'; ?></h1>

            <form method="post" action="<?php echo esc_url($action_url); ?>" style="max-width:600px;">
                <input type="hidden" name="action" value="cca_save">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="channel_name">Nombre del canal</label></th>
                        <td><input required type="text" id="channel_name" name="channel_name" value="<?php echo esc_attr($row->channel_name ?? ''); ?>" class="regular-text"></td>
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

                <?php submit_button($is_edit ? 'Guardar cambios' : 'Crear canal'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=communication_channels')); ?>">Volver</a>
            </form>
        </div>
        <?php
    }

    /** ---------------- GUARDAR ---------------- */
    public function handle_save() {
        $this->check_caps();

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'cca_save')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;

        $id       = sanitize_text_field($_POST['id'] ?? '');
        $name     = sanitize_text_field($_POST['channel_name'] ?? '');
        $price    = floatval($_POST['price'] ?? 0);
        $currency = sanitize_text_field($_POST['currency_id'] ?? '');
        $user     = wp_get_current_user()->user_login;
        $now      = current_time('mysql', true);

        if (!$name || !$price || !$currency) {
            wp_redirect(add_query_arg(['page' => 'communication_channels_form', 'error' => 'required'], admin_url('admin.php')));
            exit;
        }

        //Validación de duplicados channel name y currency id
        if ($id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE id = %s AND channel_name = %s AND currency_id = %s", 
                $id, $name, $currency
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE channel_name = %s AND currency_id = %s", 
                $name, $currency
            ));
        }

        if ($exists > 0) {
            $wp_redirect_url = add_query_arg([
                'page' => 'communication_channels_form', 
                'error' => 'duplicated',
                'id' => $id
            ], admin_url('admin.php'));
            wp_redirect($wp_redirect_url);
            exit;
        }

        if ($id) {
            // UPDATE
            $wpdb->update(
                $this->table,
                [
                    'channel_name'          => $name,
                    'price'                 => $price,
                    'currency_id'           => $currency,
                    'modified_on'           => $now,
                    'modified_by'           => $user
                ],
                ['id' => $id],
                ['%s','%f','%s','%s','%s'],
                ['%s']
            );
        } else {
            // INSERT
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$this->table} (id, channel_name, price, currency_id, created_on, created_by, modified_on, modified_by) 
                     VALUES (UUID(), %s, %f, %s, %s, %s, %s, %s)",
                    $name, $price, $currency, $now, $user, $now, $user
                )
            );
        }

        wp_redirect(admin_url('admin.php?page=communication_channels'));
        exit;
    }

    /** ---------------- ELIMINAR ---------------- */
    public function handle_delete() {
        $this->check_caps();

        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'cca_delete')) {
            wp_die('Nonce inválido.');
        }

        global $wpdb;
        $id = sanitize_text_field($_GET['id'] ?? '');
        if ($id) {
            $wpdb->delete($this->table, ['id' => $id], ['%s']);
        }
        wp_redirect(admin_url('admin.php?page=communication_channels'));
        exit;
    }
}

// Instancia global
global $hifix_cca;
if ( ! isset($hifix_cca) || ! ($hifix_cca instanceof Communication_Channels_Admin) ) {
    $hifix_cca = new Communication_Channels_Admin();
}

// Callbacks para menús
function hifix_render_communication_channels_list() {
    global $hifix_cca;
    $hifix_cca->render_list_page();
}
function hifix_render_communication_channels_form() {
    global $hifix_cca;
    $hifix_cca->render_form_page();
}