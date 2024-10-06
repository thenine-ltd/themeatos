<?php
define( 'WP_CACHE', false ); // By Speed Optimizer by SiteGround

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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbso50gaegspdg' );

/** Database username */
define( 'DB_USER', 'ubuywumysigz8' );

/** Database password */
define( 'DB_PASSWORD', '^3>;4e11i3h(' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'Yq5b/yXgqLm.A,,!TisiMnefI6@#;:ZVdJP:u)e]3gFz+i4W&-lDe}b}A 1j zgb' );
define( 'SECURE_AUTH_KEY',   'fjy/<5be3bY/.B`AX.0OctcA=l61|)jfTkLq2-t65Hy77vVY^`[`{eEMmsre&:5M' );
define( 'LOGGED_IN_KEY',     'dPq?C48k1L]+i}@XM/h)Y85+k!VHr,yB-sSn4hX4zv6/-S,_~0nNUZ,I&D]^A$lJ' );
define( 'NONCE_KEY',         'ZIaQfzD|K!fFdrrlA4@7|B=zXh#K>WX3nkSb/6X&HG#Nk9ZpV_jE0HJR/kN*C@&Z' );
define( 'AUTH_SALT',         'iaRKBfz,ktXD)6@ ~8%^!p*#9 rWC@KCkhM]0zkz6}0.(!$P|L~n8`r7hB5$2%h0' );
define( 'SECURE_AUTH_SALT',  'Q>OBcf:d8[rOqe{[V!?q~q9OvT+y h1&mv.sW[6of0REv:7NV`{QP9eP|uhD)c!+' );
define( 'LOGGED_IN_SALT',    'vkE:t/C)]u_}FI<fUYh1n>PfL_Qj7>*_da:_Nx_iQXq7aF|R03B[O>`=R6:U$7u0' );
define( 'NONCE_SALT',        'UzcqRZA-u-9VQbRp}/HUtU.?`/^o8<%yNa*oerOm77OF#^&z-@7<&yV`w*CkG<p;' );
define( 'WP_CACHE_KEY_SALT', 'S1v_b.9L,{|~-l7EDB ]i6C24aTGOr.H.LswKo]ex/0(F.e}+4W5:RVply`Xe: Q' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'idm_';

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
define( 'WP_DEBUG', true );


/* Add any custom values between this line and the "stop editing" line. */
define( 'AS3CF_SETTINGS', serialize( array(
    'provider' => 'aws',
    'access-key-id' => 'AKIA4Z7K5PXJBW5UU76I',
    'secret-access-key' => '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',
) ) );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
