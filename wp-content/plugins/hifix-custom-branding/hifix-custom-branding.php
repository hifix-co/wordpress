<?php
/**
 * Plugin Name: Hifix Custom Branding
 * Description: Cambia el logo del login y el del admin bar.
 * Author: Hifix
 * Version: 1.2
 */

/* ─────────────── LOGIN LOGO ─────────────── */

function hifix_custom_login_logo() { ?>
    <style type="text/css">
        #login h1 a {
            background-image: url('<?php echo plugins_url('logo-login.png', __FILE__); ?>');
            background-size: contain;
            width: 300px;
            height: 80px;
        }
    </style>
<?php }
add_action('login_enqueue_scripts', 'hifix_custom_login_logo');

/* Enlaces del logo en login (opcional) */
add_filter('login_headerurl', function () { return home_url('/'); });
add_filter('login_headertext', function () { return get_bloginfo('name'); });

/* ─────────────── ADMIN BAR LOGO ─────────────── */

function hifix_custom_admin_bar_logo($wp_admin_bar) {
    $wp_admin_bar->remove_node('wp-logo');
    $wp_admin_bar->add_node([
        'id'    => 'wp-logo',
        'title' => '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'logo-admin.png') . '" style="height:20px; width:auto; margin-top:5px;" alt="Logo" />',
        'href'  => admin_url(),
        'meta'  => ['title' => __('Ir al Escritorio')],
    ]);
}
add_action('admin_bar_menu', 'hifix_custom_admin_bar_logo', 11);
