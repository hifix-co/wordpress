<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HCA_Feriados_Admin extends HPT_Base_CRUD {

    public function __construct() {
        parent::__construct(
            $GLOBALS['wpdb']->prefix . 'feriados', // Nombre de la tabla existente
            'id',                                  // PK
            ['date', 'country', 'created_by']      // Campos que se pueden guardar
        );

        add_action( 'admin_menu', [ $this, 'menu' ] );
    }

    public function menu() {
        add_menu_page(
            'Administración Hifix',
            'Administración Hifix',
            'manage_options',
            'hca_list',
            [$this, 'render_list_page'],
            'dashicons-admin-alt',
            26
        );

        add_submenu_page(
            'hca_list',
            'Feriados',
            'Feriados',
            'manage_options',
            'hca_form',
            [$this, 'render_form_page']
        );
    }

    public function render_list_page() {
        $items = $this->get_all();
        ?>
        <div class="wrap">
            <h1>Feriados</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>País</th>
                        <th>Creado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <td><?php echo esc_html($item->id); ?></td>
                            <td><?php echo esc_html($item->date); ?></td>
                            <td><?php echo esc_html($item->country); ?></td>
                            <td><?php echo esc_html($item->created_by); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_form_page() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $this->save($_POST);
            echo '<div class="updated"><p>Guardado correctamente</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Nuevo Feriado</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="date">Fecha</label></th>
                        <td><input type="date" name="date" id="date" required></td>
                    </tr>
                    <tr>
                        <th><label for="country">País</label></th>
                        <td><input type="text" name="country" id="country" required></td>
                    </tr>
                    <tr>
                        <th><label for="created_by">Creado por</label></th>
                        <td><input type="text" name="created_by" id="created_by" value="<?php echo wp_get_current_user()->user_login; ?>" readonly></td>
                    </tr>
                </table>
                <?php submit_button('Guardar'); ?>
            </form>
        </div>
        <?php
    }
}
