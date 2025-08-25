<?php
/**
 * Plugin Name: WP Azure SQL CRUD
 * Description: CRUD directo contra Azure SQL (SQL Server) desde wp-admin.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/includes/class-azdb.php';
require_once __DIR__ . '/includes/class-tables.php';
require_once __DIR__ . '/includes/class-azmenu.php';

// Requiere rol admin o un rol personalizado
add_action('admin_menu', ['AZ_Menu','register']);

add_action('admin_post_az_delete', function () {
  if (!current_user_can('manage_options')) wp_die('No autorizado');
  if (!isset($_GET['az_nonce']) || !wp_verify_nonce($_GET['az_nonce'],'az_delete')) wp_die('Nonce inválido');

  $table = sanitize_text_field($_GET['table'] ?? '');
  $pk    = sanitize_text_field($_GET['pk'] ?? '');
  $id    = sanitize_text_field($_GET['id'] ?? '');
  if (!$table || !$pk || !$id) wp_die('Parámetros incompletos');

  $sql = "DELETE FROM $table WHERE [$pk]=?";
  AZ_DB::query($sql, [$id]);
  wp_redirect(wp_get_referer() ?: admin_url('admin.php?page=az-crud'));
  exit;
});