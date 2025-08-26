<?php
/**
 * Plugin Name: Hifix Admin Package tools
 * Description: Paquete de funcionales para la administración hifix.
 * Version: 1.0.3
 * Author: Cristian Barajas
 * Text Domain: hifix-admin-package-tools
 */


if ( ! defined( 'ABSPATH' ) ) exit;

// Menú principal y dashboard
require_once plugin_dir_path(__FILE__) . 'includes/menu-dashboard.php';

// CRUDs
require_once plugin_dir_path(__FILE__) . 'includes/feriados-admin.php';
