<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Menú principal y dashboard de Hifix Admin Package Tools
 */
add_action('admin_menu', function() {
    // Menú principal
    add_menu_page(
        'Hifix Admin',
        'Hifix Admin',
        'manage_options',
        'hifix_admin',
        function () {
            echo '<div class="wrap"><h1>Hifix Admin</h1><p>Bienvenido al paquete de herramientas de administración.</p></div>';
        },
        'dashicons-admin-tools',
        60
    );

    // Submenú: Lista de feriados
    add_submenu_page(
        'hifix_admin',
        'Feriados',
        'Feriados',
        'manage_options',
        'holiday_calendar',
        'hifix_render_feriados_list'
    );

    // Submenú: Lista de paquetes de mensajes
    add_submenu_page(
        'hifix_admin',
        'Paquetes de Mensajes',
        'Paquetes de Mensajes',
        'manage_options',
        'message_packages',
        'hifix_render_message_packages_list'
    );

    // Ocultar formularios en submenú
    add_submenu_page(
        null,
        'Feriado',
        'Feriado',
        'manage_options',
        'holiday_calendar_form',
        'hifix_render_feriados_form'
    );

    add_submenu_page(
        null,
        'Editar Paquete',
        'Editar Paquete',
        'manage_options',
        'message_packages_form',
        'hifix_render_message_packages_form'
    );
    // Eliminar el submenú duplicado "Hifix Admin"
    remove_submenu_page('hifix_admin', 'hifix_admin');
});
