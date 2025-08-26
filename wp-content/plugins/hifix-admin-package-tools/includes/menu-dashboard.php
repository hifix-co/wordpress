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
        '',
        'dashicons-admin-tools',
        60
    );

    // Submenú: Lista de feriados
    add_submenu_page(
        'hifix_admin',
        'Feriados',
        'Feriados',
        'manage_options',
        'hca_list',
        'hifix_render_feriados_list'
    );

    // Página "oculta" para el formulario (no aparece en el menú)
    add_submenu_page(
        null,
        'Feriado',
        'Feriado',
        'manage_options',
        'hca_form',
        'hifix_render_feriados_form'
    );
});
