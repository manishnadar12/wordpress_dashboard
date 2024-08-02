<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress_dashboard' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'Ber@11031' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define('FS_METHOD','direct');

define('WP_MEMORY_LIMIT', '64M');


/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '-!7- QJ8u6^LB0 BFqgyUI48s|`Y|rC/R`&J_t|wzkJ9RQ;_ow`U:`8tnboE$}$v' );
define( 'SECURE_AUTH_KEY',  ':2Tnv7R:0Yi!G.*w.nO:#I{EL<=c|4-J)1HC(sl&hb:Mx|TDvlzn}%r/|Cqo<s7)' );
define( 'LOGGED_IN_KEY',    'E10ne*;=P^5iws_ZfYEh~)5yv9Z#e!yZ]DUtdE6Oj6c8DA<VU1PJ<~ZVs[,UVm{Q' );
define( 'NONCE_KEY',        'd6  {N^rMA2r>U,<,:d/a=Ns>ltMH&BX_V^8;a)-Wc]5m:GJ&yv^VPE#QZaXd&;I' );
define( 'AUTH_SALT',        '?vz,6gygMfZ(eJ~`ssOZ2q2(^pWB]f->V{uIxdbj<Znl@}{I<n-p:rG(w?4l4qb6' );
define( 'SECURE_AUTH_SALT', '}Tj[r3}^NL{ n.=C0fif%:-vxh)K]q] IH5Mjt#QCX[D}E/S`YwBb27QN}./sn60' );
define( 'LOGGED_IN_SALT',   '0b$z~5m-0e=XR8|jC@yo~(zKw>YLy51RA@*KPHERRn]5Vl]Pu09I+&|~eDtVp-ju' );
define( 'NONCE_SALT',       'yF*w=tbv@r;%7S]8-C<!tBhOUtOo+f.8F}>iH(x!%19XM;_klo/j6/67}]j[A^6q' );

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

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
