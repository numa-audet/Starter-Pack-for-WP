<?php
/**
 * Plugin Name: WP Starter Pack
 * Description: Installez une sélection d’extensions WordPress depuis une interface simple, puis supprimez éventuellement Starter Pack.
 * Version: 0.0.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Numa Audet
 * License: GPL-2.0-or-later
 * Text Domain: wp-starter-pack
 */

namespace WPStarterPack;

defined( 'ABSPATH' ) || exit;

define( 'WP_STARTER_PACK_FILE', __FILE__ );
define( 'WP_STARTER_PACK_VERSION', '0.0.2' );

require_once __DIR__ . '/includes/class-plugin-catalog.php';
require_once __DIR__ . '/includes/class-theme-catalog.php';
require_once __DIR__ . '/includes/class-directory-metadata.php';
require_once __DIR__ . '/includes/class-installation-controller.php';
require_once __DIR__ . '/includes/class-activation-controller.php';
require_once __DIR__ . '/includes/class-admin-page.php';

add_action( 'plugins_loaded', static function () {
	if ( is_admin() ) {
		( new Admin_Page() )->register();
		( new Installation_Controller() )->register();
		( new Activation_Controller() )->register();
		( new Directory_Metadata() )->register();
	}
} );
