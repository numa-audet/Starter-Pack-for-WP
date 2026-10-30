<?php
namespace WPStarterPack;

defined( 'ABSPATH' ) || exit;

/** Activation explicite d’un module et accès à sa configuration. */
final class Activation_Controller {
	public function register() {
		add_action( 'wp_ajax_wp_starter_pack_activate', array( $this, 'activate' ) );
	}

	public function activate() {
		if ( ! check_ajax_referer( 'wp_starter_pack_activate', 'nonce', false ) ) {
			$this->fail( __( 'La session de sécurité a expiré. Rechargez la page.', 'wp-starter-pack' ), 403 );
		}
		$slug = isset( $_POST['slug'] ) && is_string( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
		$catalog = Plugin_Catalog::all();
		if ( ! isset( $catalog[ $slug ] ) ) {
			$this->fail( __( 'Ce module ne figure pas dans le catalogue.', 'wp-starter-pack' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$file = $catalog[ $slug ]['file'];
		if ( ! current_user_can( 'activate_plugin', $file ) || ( is_multisite() && ! current_user_can( 'manage_network_plugins' ) ) ) {
			$this->fail( __( 'Vous ne pouvez pas activer ce module.', 'wp-starter-pack' ), 403 );
		}
		wp_clean_plugins_cache( false );
		if ( ! Plugin_Catalog::is_installed( $slug ) ) {
			$this->fail( __( 'Installez ce module avant de l’activer.', 'wp-starter-pack' ) );
		}
		$active = is_multisite() ? is_plugin_active_for_network( $file ) : is_plugin_active( $file );
		if ( ! $active ) {
			if ( ! empty( $catalog[ $slug ]['requires_parent'] ) ) {
				$parent_file = $catalog[ $catalog[ $slug ]['requires_parent'] ]['file'];
				$parent_active = is_multisite() ? is_plugin_active_for_network( $parent_file ) : is_plugin_active( $parent_file );
				if ( ! $parent_active ) {
					$this->fail( __( 'Activez et configurez Elementor avant d’activer cet addon.', 'wp-starter-pack' ) );
				}
			}
			$result = activate_plugin( $file, '', is_multisite() );
			if ( is_wp_error( $result ) ) {
				$this->fail( $result->get_error_message() );
			}
			$active = is_multisite() ? is_plugin_active_for_network( $file ) : is_plugin_active( $file );
			if ( ! $active ) {
				$this->fail( __( 'L’activation n’a pas abouti. Rechargez la page pour vérifier l’état du module.', 'wp-starter-pack' ) );
			}
		}
		wp_send_json_success( array( 'url' => admin_url( $catalog[ $slug ]['configuration_path'] ) ) );
	}

	private function fail( $message, $status = 400 ) {
		wp_send_json_error( array( 'message' => wp_strip_all_tags( $message ) ), $status );
	}
}
