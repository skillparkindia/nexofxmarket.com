<?php
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
define( 'DB_NAME', 'nexo' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         '/[?{4(;6_O<ha2-?*0xA0?oM~n#a0:G+Dc%a?3=W%A* U_MD`I3qgW.#mC+`FF&-' );
define( 'SECURE_AUTH_KEY',  '(3%];m>3a>cZVlW$5VycFxiM@ 9I%DrAxZ)MiyF4*g-f{yRlyZZw{ZSLxQf8/<?=' );
define( 'LOGGED_IN_KEY',    'QST%tOT.!TiN#(hh>GJ #p<s/EVLkn.nXKAWaN/L00rr#n),c:4!/;:1U,.S*8$P' );
define( 'NONCE_KEY',        'v%0b6AMNJe>:GG9eW2TFm)>a#3GBvEWWl6+U#_,g2th,ni$+ mR(6{(f,@Aak$8Y' );
define( 'AUTH_SALT',        '( -#Z6N3(z,Y1|3jx`UIq-X(NrTE-]noPUI_|d1+3bf^4sCEzsj$A=aY!3!obQU:' );
define( 'SECURE_AUTH_SALT', 'V<T:/,VTN{_:ze-0H9OJ_*E<_1$*#/w4@M1N+#_pTEM;_j^|uYkN=h<S^05G0{1:' );
define( 'LOGGED_IN_SALT',   'j1Px)[o4chF%@aflc+~FaklpHJ]YVUc>^{{XLKm7(ifQEr]fS[z2c-Mf)dQn(hRg' );
define( 'NONCE_SALT',       'RX : %Qdww~OgTSOxsT?LPU,o-Z|!1UW]$>twK l>[=jQ@u)ow#8u4o|4JcI|7J?' );

/**#@-*/

/**
 * WordPress database table prefix.
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
