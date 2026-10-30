<?php
/**
 * Tests du contrôleur avec doublures WordPress ; aucune extension réelle supprimée.
 * Exécution : php tests/installation-controller-test.php
 */

define( 'ABSPATH', sys_get_temp_dir() . '/wsp-controller-test-' . getmypid() . '/' );
define( 'WP_PLUGIN_DIR', ABSPATH . 'plugins' );
define( 'WP_STARTER_PACK_FILE', dirname( __DIR__ ) . '/wp-starter-pack.php' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );

foreach ( array( 'plugin.php', 'file.php', 'plugin-install.php', 'class-wp-upgrader.php', 'theme.php' ) as $include ) {
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
	public function get_error_message() { return 'Activation refusée'; }
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
function plugins_api( $action, $args ) {
	$GLOBALS['api_calls'] = ( $GLOBALS['api_calls'] ?? 0 ) + 1;
	if ( ! empty( $GLOBALS['api_error'] ) ) { return new WP_Error(); }
	return (object) array( 'download_link' => 'https://downloads.wordpress.org/plugin/' . $args['slug'] . '.zip', 'requires' => '6.5', 'requires_php' => '7.4', 'rating' => 94, 'version' => '9.8.7', 'active_installs' => 1000000 );
}
function themes_api( $action, $args ) { return (object) array( 'download_link' => 'https://downloads.wordpress.org/theme/' . $args['slug'] . '.zip', 'version' => '1.2.3', 'rating' => 88, 'active_installs' => 10000 ); }
function get_theme_root() { return ABSPATH . 'themes'; }
function wp_clean_themes_cache( $clear ) {}
function wp_get_theme( $slug ) { return new Fake_Theme( $slug ); }
class Fake_Theme {
	private $slug;
	public function __construct( $slug ) { $this->slug = $slug; }
	public function exists() { return ! empty( $GLOBALS['installed_themes'][ $this->slug ] ); }
}
function number_format_i18n( $value, $decimals = 0 ) { return number_format( $value, $decimals, ',', ' ' ); }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function esc_url_raw( $value, $protocols ) { return $value; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function is_wp_version_compatible( $version ) { return true; }
function is_php_version_compatible( $version ) { return true; }
function plugin_basename( $file ) { return 'wp-starter-pack/wp-starter-pack.php'; }
function is_multisite() { return $GLOBALS['multisite'] ?? false; }
function is_plugin_active_for_network( $file ) { return ! empty( $GLOBALS['network_active'][ $file ] ); }
function is_plugin_active( $file ) { return ! empty( $GLOBALS['active'][ $file ] ) || is_plugin_active_for_network( $file ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function deactivate_plugins( $file, $silent, $network ) { $GLOBALS['deactivated'] = true; }
function delete_plugins( $files ) { $GLOBALS['deleted'] = $files; return $GLOBALS['delete_result']; }
function activate_plugin( $file, $redirect = '', $network = false, $silent = false ) {
	$GLOBALS['restored'] = true;
	if ( ! empty( $GLOBALS['activation_error'] ) ) { return new WP_Error(); }
	$GLOBALS['activation_calls'][] = $file;
	$GLOBALS[ $network ? 'network_active' : 'active' ][ $file ] = true;
}
class WP_Ajax_Upgrader_Skin {
	public $result = true;
	public function get_errors() { return new WP_Error(); }
}
class Plugin_Upgrader {
	public function __construct( $skin ) {}
	public function install( $url ) {
		$GLOBALS['downloads'][] = $url;
		$slug = basename( parse_url( $url, PHP_URL_PATH ), '.zip' );
		$catalog = \WPStarterPack\Plugin_Catalog::all();
		$GLOBALS['installed'][ $catalog[ $slug ]['file'] ] = array( 'Name' => $catalog[ $slug ]['name'] );
		return true;
	}
}
class Theme_Upgrader {
	public function __construct( $skin ) {}
	public function install( $url ) {
		$GLOBALS['theme_downloads'][] = $url;
		$GLOBALS['installed_themes'][ basename( parse_url( $url, PHP_URL_PATH ), '.zip' ) ] = true;
		return true;
	}
}

require dirname( __DIR__ ) . '/includes/class-plugin-catalog.php';
require dirname( __DIR__ ) . '/includes/class-theme-catalog.php';
require dirname( __DIR__ ) . '/includes/class-directory-metadata.php';
require dirname( __DIR__ ) . '/includes/class-installation-controller.php';
require dirname( __DIR__ ) . '/includes/class-activation-controller.php';

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
	$controller = new \WPStarterPack\Activation_Controller();
	$valid_nonce = false;
	check( ! invoke( 'activate', array( 'slug' => 'elementor' ) )->success, 'Activation protégée par nonce' );
	$valid_nonce = true;
	check( ! invoke( 'activate', array( 'slug' => '../inconnu' ) )->success, 'Activation hors catalogue refusée' );
	check( ! invoke( 'activate', array( 'slug' => array( 'elementor' ) ) )->success, 'Slug d’activation malformé refusé' );
	$denied = array( 'activate_plugin' );
	check( ! invoke( 'activate', array( 'slug' => 'elementor' ) )->success, 'Permission d’activation obligatoire' );
	$denied = array();
	check( ! invoke( 'activate', array( 'slug' => 'wpforms-lite' ) )->success, 'Activation d’un module absent refusée' );
	$activation_error = true;
	check( ! invoke( 'activate', array( 'slug' => 'elementor' ) )->success, 'Erreur WordPress transmise sans redirection' );
	$activation_error = false;
	$response = invoke( 'activate', array( 'slug' => 'elementor' ) );
	check( $response->success && 'https://example.test/wp-admin/admin.php?page=elementor' === $response->data['url'], 'Activation puis URL de configuration locale' );
	$before = count( $activation_calls );
	check( invoke( 'activate', array( 'slug' => 'elementor' ) )->success && count( $activation_calls ) === $before, 'Module déjà actif : configuration sans réactivation' );
	$multisite = true;
	$denied = array( 'manage_network_plugins' );
	check( ! invoke( 'activate', array( 'slug' => 'wordfence' ) )->success, 'Activation réseau réservée aux droits réseau' );
	$denied = array();
	check( invoke( 'activate', array( 'slug' => 'wordfence' ) )->success && is_plugin_active_for_network( 'wordfence/wordfence.php' ), 'Activation réseau explicite en multisite' );
	$multisite = false;
	$controller = new \WPStarterPack\Installation_Controller();
	check( ! invoke( 'start', array( 'themes' => array( '../theme-inconnu' ) ) )->success, 'Thème hors catalogue refusé' );
	$denied = array( 'install_themes' );
	check( ! invoke( 'start', array( 'themes' => array( 'astra' ) ) )->success, 'Permission d’installation des thèmes obligatoire' );
	$denied = array();
	check( invoke( 'start', array( 'themes' => array( 'astra' ), 'remove' => '1' ) )->success, 'Sélection de thèmes seuls autorisée' );
	$deactivated = false;
	$deleted = array();
	check( ! invoke( 'finish', array( 'token' => $token ) )->success && ! $deactivated && ! $deleted, 'Thème manquant : suppression de Starter Pack interdite' );
	check( ! invoke( 'install', array( 'token' => $token, 'slug' => 'hello-biz', 'type' => 'theme' ) )->success, 'Thème hors sélection refusé' );
	check( invoke( 'install', array( 'token' => $token, 'slug' => 'astra', 'type' => 'theme' ) )->success, 'Installation d’un thème via Theme_Upgrader' );
	check( invoke( 'install', array( 'token' => $token, 'slug' => 'astra', 'type' => 'theme' ) )->success && 1 === count( $theme_downloads ), 'Thème déjà présent non réinstallé' );
	check( invoke( 'finish', array( 'token' => $token ) )->success, 'Finalisation après installation des thèmes' );
	$installed = array();
	$response = invoke( 'start', array( 'plugins' => array( 'elementskit-lite' ) ) );
	check( $response->success && array( 'elementor', 'elementskit-lite' ) === $response->data['plugins'], 'Addon : Elementor ajouté en premier côté serveur' );
	check( ! invoke( 'install', array( 'token' => $token, 'slug' => 'elementskit-lite' ) )->success, 'Addon non installé si Elementor a échoué' );
	check( invoke( 'install', array( 'token' => $token, 'slug' => 'elementor' ) )->success && invoke( 'install', array( 'token' => $token, 'slug' => 'elementskit-lite' ) )->success, 'Installation ordonnée du parent et de l’addon' );
	$controller = new \WPStarterPack\Activation_Controller();
	$active = array();
	$network_active = array();
	check( ! invoke( 'activate', array( 'slug' => 'elementskit-lite' ) )->success, 'Activation de l’addon bloquée tant qu’Elementor est inactif' );
	check( invoke( 'activate', array( 'slug' => 'elementor' ) )->success && invoke( 'activate', array( 'slug' => 'elementskit-lite' ) )->success, 'Activation de l’addon après Elementor' );
	$controller = new \WPStarterPack\Directory_Metadata();
	check( ! invoke( 'retrieve', array( 'type' => 'theme', 'slug' => 'inconnu' ) )->success, 'Métadonnées limitées au catalogue' );
	$response = invoke( 'retrieve', array( 'type' => 'plugin', 'slug' => 'woocommerce' ) );
	check( $response->success && '4,7 / 5' === $response->data['rating'] && '9.8.7' === $response->data['version'] && '1 000 000+' === $response->data['activeInstalls'], 'Métadonnées : conversion sur cinq, version et installations' );
	$before = $api_calls;
	invoke( 'retrieve', array( 'type' => 'plugin', 'slug' => 'woocommerce' ) );
	check( $before === $api_calls, 'Cache des métadonnées : aucun nouvel appel au répertoire' );
	$api_error = true;
	check( ! invoke( 'retrieve', array( 'type' => 'plugin', 'slug' => 'wordfence' ) )->success, 'Panne WordPress.org signalée sans chiffres inventés' );
	$api_error = false;
	$response = invoke( 'retrieve', array( 'type' => 'theme', 'slug' => 'hello-biz' ) );
	check( $response->success && '1.2.3' === $response->data['version'], 'Métadonnées des thèmes via themes_api' );
	echo $checks . ' vérifications réussies.' . PHP_EOL;
} finally {
	foreach ( glob( ABSPATH . 'wp-admin/includes/*' ) as $file ) { unlink( $file ); }
	rmdir( ABSPATH . 'wp-admin/includes' );
	rmdir( ABSPATH . 'wp-admin' );
	rmdir( ABSPATH );
}
