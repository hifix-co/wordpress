<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'hifix' );

/** Database username */
define( 'DB_USER', 'dbadmin' );

/** Database password */
define( 'DB_PASSWORD', 'tP573u%u-0q2' );

/** Database hostname */
define( 'DB_HOST', 'mssql-weastus2-hifix-prd.mysql.database.azure.com:3306' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb3' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

// ================================================
// 🔐 Configuración SSL para MySQL en Azure
// ================================================
define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL );

// Ruta al certificado raíz de Azure
// 👉 Descarga desde: https://dl.cacerts.digicert.com/BaltimoreCyberTrustRoot.crt.pem
// y guárdalo en /certs dentro de tu proyecto
define( 'DB_SSL_CA', __DIR__ . '/certs/BaltimoreCyberTrustRoot.crt.pem' );

// Forzar que WP use SSL en la conexión MySQL
if ( ! defined( 'PDO::MYSQL_ATTR_SSL_CA' ) ) {
    define( 'PDO::MYSQL_ATTR_SSL_CA', DB_SSL_CA );
}


/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'iK%wR!4C]O7C*D]^:DR9~R&A@-;sjK-]{vH{$aOUt>@pn^[=vHJq#`h]/s&SPqJ/' );
define( 'SECURE_AUTH_KEY',  'l>x3>dSeB%+Zqvt+r{b_p~#v/{EICN7Z6<_IE&HDH`4Cb:8+[[i)3yKHw;WkeH*V' );
define( 'LOGGED_IN_KEY',    ')3)LjtKzd,6fkIXk7zL=5i72+Q=w)IGuh7F_s;xO+Ht,c_6mWvCt/yE@*3dBL-~t' );
define( 'NONCE_KEY',        ')k*7vs@2}T> >dA$!HH7?7m77]_=6PqL3iAU:uVT!RX=bxu6E$D)4)Ry3=BP[,YC' );
define( 'AUTH_SALT',        '~rjjLU!6,okT)O:0-Zk3<t_9v9#=KycxQ~y%UOWjuM|8IJc.NPA$<,CS9D ^J37z' );
define( 'SECURE_AUTH_SALT', ')}Hek+:|Ve)(dU2P=mfs?s#dYIL`/[^:T;Kgi^Xxq*`Cd%D|q0*7:&hXCPihd7rB' );
define( 'LOGGED_IN_SALT',   'TqDHG~E|BY--a#oMw2dk:?D9ur[A!tuwP/rry>m9Y>y@kWRgXO[!v`4kh!|lfUiG' );
define( 'NONCE_SALT',       ':S3Pb0X^Frjh~9$>UG1Xuw9B;Ju^Ptk$sfV.u2KDzG4NTO?7UZ^C!U_p !$qsNWg' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

// Forzar WordPress a usar localhost
define('WP_HOME','http://localhost/wordpress');
define('WP_SITEURL','http://localhost/wordpress');

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

// ================================================
// ⚡ Hook para aplicar SSL al handler mysqli
// ================================================
add_action( 'wpdb_init', function( $wpdb ) {
    if ( defined('DB_SSL_CA') && file_exists(DB_SSL_CA) ) {
        mysqli_ssl_set( $wpdb->dbh, NULL, NULL, DB_SSL_CA, NULL, NULL );
    }
});