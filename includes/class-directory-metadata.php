<?php
namespace WPStarterPack;

defined( 'ABSPATH' ) || exit;

/** Informations publiques du répertoire, chargées après affichage et mises en cache. */
final class Directory_Metadata {
	public function register() {
		add_action( 'wp_ajax_wp_starter_pack_metadata', array( $this, 'retrieve' ) );
	}

	public function retrieve() {
		if ( ! current_user_can( 'install_plugins' ) || ! check_ajax_referer( 'wp_starter_pack_metadata', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Accès refusé. Rechargez la page.', 'wp-starter-pack' ) ), 403 );
		}
		$slug = isset( $_POST['slug'] ) && is_string( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
		$type = isset( $_POST['type'] ) && is_string( $_POST['type'] ) ? $_POST['type'] : '';
		$catalog = 'theme' === $type ? Theme_Catalog::all() : Plugin_Catalog::all();
		if ( ! in_array( $type, array( 'plugin', 'theme' ), true ) || ! isset( $catalog[ $slug ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Élément inconnu.', 'wp-starter-pack' ) ), 400 );
		}
		$data = self::get( $type, $slug );
		if ( false === $data ) {
			wp_send_json_error( array( 'message' => __( 'Informations WordPress.org momentanément indisponibles.', 'wp-starter-pack' ) ), 502 );
		}
		wp_send_json_success( $data );
	}

	public static function get( $type, $slug ) {
		$key = 'wsp_directory_' . $type . '_' . $slug;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return empty( $cached['unavailable'] ) ? $cached : false;
		}
		$args = array( 'slug' => $slug, 'fields' => array( 'sections' => false, 'rating' => true, 'ratings' => true, 'active_installs' => true, 'icons' => true, 'screenshot_url' => true ) );
		if ( 'theme' === $type ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
			$info = themes_api( 'theme_information', $args );
		} else {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			$info = plugins_api( 'plugin_information', $args );
		}
		if ( is_wp_error( $info ) || ! is_object( $info ) ) {
			set_transient( $key, array( 'unavailable' => true ), 5 * MINUTE_IN_SECONDS );
			return false;
		}
		$rating = isset( $info->rating ) && is_numeric( $info->rating ) ? max( 0, min( 5, (float) $info->rating / 20 ) ) : null;
		$icons = isset( $info->icons ) ? (array) $info->icons : array();
		$icon = 'theme' === $type ? ( $info->screenshot_url ?? '' ) : ( $icons['2x'] ?? $icons['1x'] ?? $icons['svg'] ?? '' );
		$data = array(
			'rating' => isset( $info->num_ratings ) && 0 === (int) $info->num_ratings ? __( 'Aucun avis', 'wp-starter-pack' ) : ( null === $rating ? '—' : number_format_i18n( $rating, 1 ) . ' / 5' ),
			'version' => isset( $info->version ) ? sanitize_text_field( $info->version ) : '—',
			'activeInstalls' => isset( $info->active_installs ) && is_numeric( $info->active_installs ) ? number_format_i18n( (int) $info->active_installs ) . '+' : '—',
			'image' => esc_url_raw( $icon, array( 'https' ) ),
		);
		set_transient( $key, $data, 6 * HOUR_IN_SECONDS );
		return $data;
	}
}
