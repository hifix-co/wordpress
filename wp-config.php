<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache


/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

//Using environment variables for memory limits
$wp_memory_limit = (getenv('WP_MEMORY_LIMIT') && preg_match("/^[0-9]+M$/", getenv('WP_MEMORY_LIMIT'))) ? getenv('WP_MEMORY_LIMIT') : '128M';
$wp_max_memory_limit = (getenv('WP_MAX_MEMORY_LIMIT') && preg_match("/^[0-9]+M$/", getenv('WP_MAX_MEMORY_LIMIT'))) ? getenv('WP_MAX_MEMORY_LIMIT') : '256M';

/** General WordPress memory limit for PHP scripts*/
define('WP_MEMORY_LIMIT', $wp_memory_limit );

/** WordPress memory limit for Admin panel scripts */
define('WP_MAX_MEMORY_LIMIT', $wp_max_memory_limit );


//Using environment variables for DB connection information

// ** Database settings - You can get this info from your web host ** //
$connectstr_dbhost = getenv('DATABASE_HOST');
$connectstr_dbname = getenv('DATABASE_NAME');
$connectstr_dbusername = getenv('DATABASE_USERNAME');
$connectstr_dbpassword = getenv('DATABASE_PASSWORD');

// Using managed identity to fetch MySQL access token
if (strtolower(getenv('ENABLE_MYSQL_MANAGED_IDENTITY')) === 'true') {
	try {
		require_once(ABSPATH . 'class_entra_database_token_utility.php');
		if (strtolower(getenv('CACHE_MYSQL_ACCESS_TOKEN')) !== 'true') {
			$connectstr_dbpassword = EntraID_Database_Token_Utilities::getAccessToken();
		} else {
			$connectstr_dbpassword = EntraID_Database_Token_Utilities::getOrUpdateAccessTokenFromCache();
		}
	} catch (Exception $e) {
		// An empty string displays a 502 HTTP error page rather than a database connection error page. So, using a dummy string instead.
		$connectstr_dbpassword = '<dummy-value>';
		error_log($e->getMessage());
	}
}

/** The name of the database for WordPress */
define('DB_NAME', $connectstr_dbname);

/** MySQL database username */
define('DB_USER', $connectstr_dbusername);

/** MySQL database password */
define('DB_PASSWORD',$connectstr_dbpassword);

/** MySQL hostname */
define('DB_HOST', $connectstr_dbhost);

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/** Enabling support for connecting external MYSQL over SSL*/
$mysql_sslconnect = (getenv('DB_SSL_CONNECTION')) ? getenv('DB_SSL_CONNECTION') : 'true';
if (strtolower($mysql_sslconnect) != 'false' && !is_numeric(strpos($connectstr_dbhost, "127.0.0.1")) && !is_numeric(strpos(strtolower($connectstr_dbhost), "localhost"))) {
	define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
}


/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'tWKI=aO_b#^ct[#r$Fet`OuBF$#Fcv@8uIh<m5|yQq/zAE-K_q~^@V}0c@1$Y|i:' );
define( 'SECURE_AUTH_KEY',  'Zk%aZe18>2cce67)hYim,MaoQVq$kA}P*+ 5ZFa4k2n#Ak;W<Zn `0j@: V%c.N>' );
define( 'LOGGED_IN_KEY',    '}EN&6 Yr@PL!NjpbToR|};I:p,Wi9xjh~{mEoB47$[A#F.AJFOYe:IN^h^_nL^YM' );
define( 'NONCE_KEY',        '`A{a^lY7B^HG-IeL5g4w(o44x!|i[|sG5gfs/<r$b/V 0[MK/)nTTN}C*+<<ziSs' );
define( 'AUTH_SALT',        'LI1%Q#Jk3TMM8n=J<Kt]:a4<Gh|YYL&!<2N}im#9$R&14+?/C%_212)<B3zg-mzA' );
define( 'SECURE_AUTH_SALT', ';DvKI~zwyf3!c2y()tj+E.zXK17izHRnmje=L#:5;y9@eJ{VW5U N_Itq-Rz4zyS' );
define( 'LOGGED_IN_SALT',   'W#j^F1PiTA4q3N,]DJ p}GG*cUldz(HGCt,vqflrHf?ld1Qnn%Mi1neF _Sr>gK~' );
define( 'NONCE_SALT',       '*|5vp^?wh>0$r4UQZ1$4r`%Sshtd|}r ^kC*;HsS=3L`M+L|AXQ.(L}<H6b;n!a[' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy blogging. */
/**https://developer.wordpress.org/reference/functions/is_ssl/ */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
	$_SERVER['HTTPS'] = 'on';

$http_protocol='http://';
if (!preg_match("/^localhost(:[0-9])*/", $_SERVER['HTTP_HOST']) && !preg_match("/^127\.0\.0\.1(:[0-9])*/", $_SERVER['HTTP_HOST'])) {
	$http_protocol='https://';
}

//Relative URLs for swapping across app service deployment slots
define('WP_HOME', $http_protocol . $_SERVER['HTTP_HOST']);
define('WP_SITEURL', $http_protocol . $_SERVER['HTTP_HOST']);
define('WP_CONTENT_URL', '/wp-content');
define('DOMAIN_CURRENT_SITE', $_SERVER['HTTP_HOST']);

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

// === Azure SQL (SQL Server) ===
define('AZSQL_HOST',    'tcp:sql-weastus2-prd-hifix.database.windows.net,1433');
define('AZSQL_DB',      'hifix');
define('AZSQL_USER',    'db-admin@sql-weastus2-prd-hifix'); // o solo 'wp_app_user'
define('AZSQL_PASS',    'rp<B53VhEq7.');
define('AZSQL_ENCRYPT', true);    // debe ser true
define('AZSQL_TRUST',   false);   // ideal false en prod