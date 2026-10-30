<?php
/**
 * Tests du contrôleur avec doublures WordPress ; aucune extension réelle supprimée.
 * Exécution : php tests/installation-controller-test.php
 */

define( 'ABSPATH', sys_get_temp_dir() . '/wsp-controller-test-' . getmypid() . '/' );
define( 'WP_PLUGIN_DIR', ABSPATH . 'plugins' );
define( 'WP_STARTER_PACK_FILE', dirname( __DIR__ ) . '/wp-starter-pack.php' );
define( 'HOUR_IN_SECONDS', 3600 );

foreach ( array( 'plugin.php', 'file.php', 'plugin-install.php', 'class-wp-upgrader.php' ) as $include ) {
	if ( ! is_dir( ABSPATH . 'wp-admin/includes' ) ) {
		mkdir( ABSPATH . 'wp-admin/includes', 0700, true );
	}
	file_put_contents( ABSPATH . 'wp-admin/includes/' . $include, '<?php' );
}

final class Json_Response extends Exception {
	public $success;
	public $data;
	public function __construct( $success, $data ) {
		$this->success = $success;
		$this->data = $data;
	}
}
class WP_Error {
	public function has_errors() { return false; }
}
function __( $text, $domain = '' ) { return $text; }
function current_user_can( $capability ) { return ! in_array( $capability, $GLOBALS['denied'], true ); }
function check_ajax_referer( $action, $field, $die ) { return $GLOBALS['valid_nonce']; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function wp_send_json_error( $data, $status = 400 ) { throw new Json_Response( false, $data ); }
function wp_send_json_success( $data ) { throw new Json_Response( true, $data ); }
function wp_unslash( $value ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function get_current_user_id() { return $GLOBALS['user_id']; }
function get_transient( $key ) { return $GLOBALS['sessions'][ $key ] ?? false; }
function set_transient( $key, $value, $expiry ) { $GLOBALS['sessions'][ $key ] = $value; return true; }
function delete_transient( $key ) { unset( $GLOBALS['sessions'][ $key ] ); }
function wp_generate_uuid4() { return 'test-session'; }
function get_filesystem_method( $args, $context ) { return $GLOBALS['filesystem']; }
function WP_Filesystem() { return true; }
function wp_clean_plugins_cache( $clear ) {}
function get_plugins() { return $GLOBALS['installed']; }
function plugins_api( $action, $args ) { return (object) array( 'download_link' => 'https://downloads.wordpress.org/plugin/' . $args['slug'] . '.zip', 'requires' => '6.5', 'requires_php' => '7.4' ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function is_wp_version_compatible( $version ) { return true; }
function is_php_version_compatible( $version ) { return true; }
function plugin_basename( $file ) { return 'wp-starter-pack/wp-starter-pack.php'; }
function is_multisite() { return false; }
function is_plugin_active_for_network( $file ) { return false; }
function deactivate_plugins( $file, $silent, $network ) { $GLOBALS['deactivated'] = true; }
function delete_plugins( $files ) { $GLOBALS['deleted'] = $files; return $GLOBALS['delete_result']; }
function activate_plugin( $file, $redirect, $network, $silent ) { $GLOBALS['restored'] = true; }
class WP_Ajax_Upgrader_Skin {
	public $result = true;
	public function get_errors() { return new WP_Error(); }
}
class Plugin_Upgrader {
	public function __construct( $skin ) {}
	public function install( $url ) {
		$GLOBALS['downloads'][] = $url;
		$GLOBALS['installed']['elementor/elementor.php'] = array( 'Name' => 'Elementor' );
		return true;
	}
}

require dirname( __DIR__ ) . '/includes/class-plugin-catalog.php';
require dirname( __DIR__ ) . '/includes/class-installation-controller.php';

$controller = new \WPStarterPack\Installation_Controller();
$denied = array();
$valid_nonce = true;
$filesystem = 'direct';
$user_id = 1;
$sessions = array();
$installed = array();
$downloads = array();
$deleted = array();
$deactivated = false;
$restored = false;
$delete_result = true;
$checks = 0;

function invoke( $method, $post ) {
	$_POST = $post;
	try {
		$GLOBALS['controller']->$method();
	} catch ( Json_Response $response ) {
		return $response;
	}
	throw new RuntimeException( 'Réponse JSON manquante.' );
}
function check( $condition, $label ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'ÉCHEC : ' . $label );
	}
	++$GLOBALS['checks'];
	echo 'OK : ' . $label . PHP_EOL;
}

try {
	$denied = array( 'install_plugins' );
	check( ! invoke( 'start', array( 'plugins' => array( 'elementor' ) ) )->success, 'Droits d’installation obligatoires' );
	$denied = array();
	$valid_nonce = false;
	check( ! invoke( 'start', array( 'plugins' => array( 'elementor' ) ) )->success, 'Nonce obligatoire' );
	$valid_nonce = true;
	check( ! invoke( 'start', array( 'plugins' => array() ) )->success, 'Sélection vide refusée' );
	check( ! invoke( 'start', array( 'plugins' => array( '../inconnu' ) ) )->success, 'Catalogue fermé' );
	check( ! invoke( 'start', array( 'plugins' => array( array( 'elementor' ) ) ) )->success, 'Entrée imbriquée refusée sans erreur PHP' );
	$filesystem = 'ftpext';
	check( ! invoke( 'start', array( 'plugins' => array( 'elementor' ) ) )->success, 'Hébergement FTP signalé avant installation' );
	$filesystem = 'direct';
	$denied = array( 'delete_plugins' );
	check( ! invoke( 'start', array( 'plugins' => array( 'elementor' ), 'remove' => '1' ) )->success, 'Droits de suppression obligatoires' );
	$denied = array();
	$start = invoke( 'start', array( 'plugins' => array( 'elementor', 'wordfence' ), 'remove' => '1' ) );
	$token = $start->data['token'];
	check( $start->success, 'Création d’une session serveur' );
	$user_id = 2;
	check( ! invoke( 'finish', array( 'token' => $token ) )->success, 'Session isolée par utilisateur' );
	$user_id = 1;
	check( ! invoke( 'install', array( 'token' => $token, 'slug' => 'wpforms-lite' ) )->success, 'Module hors sélection refusé' );
	check( invoke( 'install', array( 'token' => $token, 'slug' => 'elementor' ) )->success, 'Installation via l’API WordPress' );
	check( invoke( 'install', array( 'token' => $token, 'slug' => 'elementor' ) )->success && 1 === count( $downloads ), 'Module présent non réinstallé' );
	check( ! invoke( 'finish', array( 'token' => $token ) )->success && ! $deactivated && ! $deleted, 'Installation partielle : aucune désactivation ni suppression' );
	$installed['wordfence/wordfence.php'] = array( 'Name' => 'Wordfence' );
	$delete_result = false;
	check( ! invoke( 'finish', array( 'token' => $token ) )->success && $restored, 'Échec de suppression : restauration de Starter Pack' );
	$delete_result = true;
	$finish = invoke( 'finish', array( 'token' => $token ) );
	check( $finish->success && $finish->data['deleted'] && array( 'wp-starter-pack/wp-starter-pack.php' ) === $deleted, 'Suppression limitée à Starter Pack après réussite totale' );
	check( empty( $sessions ), 'Session supprimée après finalisation' );
	$deleted = array();
	invoke( 'start', array( 'plugins' => array( 'elementor' ), 'remove' => '0' ) );
	$finish = invoke( 'finish', array( 'token' => $token ) );
	check( $finish->success && ! $finish->data['deleted'] && empty( $deleted ), 'Option non cochée : plugin conservé' );
	echo $checks . ' vérifications réussies.' . PHP_EOL;
} finally {
	foreach ( glob( ABSPATH . 'wp-admin/includes/*' ) as $file ) { unlink( $file ); }
	rmdir( ABSPATH . 'wp-admin/includes' );
	rmdir( ABSPATH . 'wp-admin' );
	rmdir( ABSPATH );
}
