<?php
/**
 * Plugin Name: Hifix Admin Package tools
 * Description: Paquete de funcionales para la administración hifix.
 * Version: 1.0.1
 * Author: Cristian Barajas
 * Text Domain: hifix-admin-package-tools
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Evita acceso directo

// 🚀 Definir ruta base del plugin
define( 'HPT_PATH', plugin_dir_path( __FILE__ ) );
define( 'HPT_URL', plugin_dir_url( __FILE__ ) );

// ------------------------------------------------------------
// 1. Cargar clases de los módulos
// ------------------------------------------------------------
require_once HPT_PATH . 'includes/class-base-crud.php'; 
require_once HPT_PATH . 'includes/feriados/class-feriados-admin.php'; 
// Si luego agregas otros CRUDs (ej. empleados, turnos, etc)
// los vas agregando aquí con más require_once

// ------------------------------------------------------------
// 2. Inicializar módulos
// ------------------------------------------------------------
add_action( 'plugins_loaded', 'hpt_init_plugin' );

function hpt_init_plugin() {
    // ✅ Inicializa el módulo de Feriados
    new HCA_Feriados_Admin();

    // ✅ Cuando tengas más CRUDs, inicialízalos aquí
    // new HCA_Empleados_Admin();
    // new HCA_Turnos_Admin();
}
