<?php
namespace WPStarterPack;

defined( 'ABSPATH' ) || exit;

final class Installation_Controller {
	public function register() {
		add_action( 'wp_ajax_wp_starter_pack_start', array( $this, 'start' ) );
		add_action( 'wp_ajax_wp_starter_pack_install', array( $this, 'install' ) );
		add_action( 'wp_ajax_wp_starter_pack_finish', array( $this, 'finish' ) );
	}

	private function authorize() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			$this->fail( __( 'Vous ne pouvez pas installer d’extensions.', 'wp-starter-pack' ), 403 );
		}
		if ( ! check_ajax_referer( 'wp_starter_pack_install', 'nonce', false ) ) {
			$this->fail( __( 'La session de sécurité a expiré. Rechargez la page.', 'wp-starter-pack' ), 403 );
		}
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	private function fail( $message, $status = 400 ) {
		wp_send_json_error( array( 'message' => wp_strip_all_tags( $message ) ), $status );
	}

	private function session_key( $token ) {
		return 'wp_starter_pack_' . get_current_user_id() . '_' . $token;
	}

	private function session() {
		$token = isset( $_POST['token'] ) && is_string( $_POST['token'] ) ? sanitize_key( wp_unslash( $_POST['token'] ) ) : '';
		$session = $token ? get_transient( $this->session_key( $token ) ) : false;
		if ( ! is_array( $session ) ) {
			$this->fail( __( 'Cette installation a expiré. Rechargez la page et recommencez.', 'wp-starter-pack' ) );
		}
		return array( $token, $session );
	}

	/** Aucun formulaire FTP ne doit être injecté dans une réponse JSON. */
	private function prepare_filesystem() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( 'direct' !== get_filesystem_method( array(), WP_PLUGIN_DIR ) ) {
			$this->fail( __( 'Votre hébergement exige des identifiants FTP/SSH. Demandez à votre hébergeur un accès direct en écriture pour WordPress, ou installez les extensions depuis Extensions > Ajouter.', 'wp-starter-pack' ) );
		}
		if ( ! WP_Filesystem() ) {
			$this->fail( __( 'WordPress ne peut pas écrire dans le dossier des extensions. Vérifiez les permissions avec votre hébergeur.', 'wp-starter-pack' ) );
		}
	}

	public function start() {
		$this->authorize();
		$selected = isset( $_POST['plugins'] ) ? wp_unslash( $_POST['plugins'] ) : array();
		if ( ! is_array( $selected ) || empty( $selected ) || count( $selected ) > 5 ) {
			$this->fail( __( 'Sélectionnez entre un et cinq modules.', 'wp-starter-pack' ) );
		}
		$catalog = Plugin_Catalog::all();
		foreach ( $selected as $slug ) {
			if ( ! is_string( $slug ) || ! isset( $catalog[ $slug ] ) ) {
				$this->fail( __( 'Un module sélectionné ne figure pas dans le catalogue.', 'wp-starter-pack' ) );
			}
		}
		$remove = isset( $_POST['remove'] ) && '1' === $_POST['remove'];
		if ( $remove && ! current_user_can( 'delete_plugins' ) ) {
			$this->fail( __( 'Vous ne pouvez pas supprimer Starter Pack.', 'wp-starter-pack' ), 403 );
		}
		$this->prepare_filesystem();
		$token = wp_generate_uuid4();
		$session = array( 'plugins' => array_values( array_unique( $selected ) ), 'remove' => $remove );
		if ( ! set_transient( $this->session_key( $token ), $session, HOUR_IN_SECONDS ) ) {
			$this->fail( __( 'Impossible de préparer l’installation. Réessayez.', 'wp-starter-pack' ) );
		}
		wp_send_json_success( array( 'token' => $token ) );
	}

	public function install() {
		$this->authorize();
		list( $token, $session ) = $this->session();
		$slug = isset( $_POST['slug'] ) && is_string( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		if ( ! in_array( $slug, $session['plugins'], true ) ) {
			$this->fail( __( 'Ce module ne fait pas partie de votre sélection.', 'wp-starter-pack' ) );
		}
		wp_clean_plugins_cache( false );
		if ( Plugin_Catalog::is_installed( $slug ) ) {
			wp_send_json_success( array( 'installed' => true ) );
		}
		$this->prepare_filesystem();
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$information = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
		if ( is_wp_error( $information ) ) {
			$this->fail( $information->get_error_message() );
		}
		if ( empty( $information->download_link ) ) {
			$this->fail( __( 'WordPress.org n’a pas fourni de téléchargement pour ce module.', 'wp-starter-pack' ) );
		}
		if ( ! empty( $information->requires ) && ! is_wp_version_compatible( $information->requires ) ) {
			$this->fail( __( 'Mettez à jour WordPress avant d’installer ce module.', 'wp-starter-pack' ) );
		}
		if ( ! empty( $information->requires_php ) && ! is_php_version_compatible( $information->requires_php ) ) {
			$this->fail( __( 'La version PHP de votre hébergement est trop ancienne pour ce module.', 'wp-starter-pack' ) );
		}
		$skin = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		// La skin AJAX conserve les messages sans les afficher dans le flux JSON.
		$result = $upgrader->install( $information->download_link );
		if ( is_wp_error( $result ) ) {
			$this->fail( $result->get_error_message() );
		}
		if ( is_wp_error( $skin->result ) ) {
			$this->fail( $skin->result->get_error_message() );
		}
		$errors = $skin->get_errors();
		if ( $errors->has_errors() ) {
			$this->fail( $errors->get_error_message() );
		}
		wp_clean_plugins_cache( false );
		if ( true !== $result || ! Plugin_Catalog::is_installed( $slug ) ) {
			$this->fail( __( 'L’installation n’a pas abouti. Vérifiez l’espace disque et les permissions du serveur.', 'wp-starter-pack' ) );
		}
		wp_send_json_success( array( 'installed' => true ) );
	}

	public function finish() {
		$this->authorize();
		list( $token, $session ) = $this->session();
		wp_clean_plugins_cache( false );
		// Ne jamais se fier à un bilan de réussite envoyé par le navigateur.
		foreach ( $session['plugins'] as $slug ) {
			if ( ! Plugin_Catalog::is_installed( $slug ) ) {
				$this->fail( __( 'Au moins un module manque. Starter Pack a été conservé.', 'wp-starter-pack' ) );
			}
		}
		if ( ! $session['remove'] ) {
			delete_transient( $this->session_key( $token ) );
			wp_send_json_success( array( 'deleted' => false ) );
		}
		if ( ! current_user_can( 'delete_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			$this->fail( __( 'Les modules sont installés, mais vous ne pouvez pas supprimer Starter Pack.', 'wp-starter-pack' ), 403 );
		}
		$this->prepare_filesystem();
		$file = plugin_basename( WP_STARTER_PACK_FILE );
		// Un fichier placé directement dans plugins/ ne doit pas supprimer un dossier partagé.
		if ( '.' === dirname( $file ) || is_link( dirname( WP_STARTER_PACK_FILE ) ) || is_link( WP_STARTER_PACK_FILE ) ) {
			$this->fail( __( 'Pour sa suppression automatique, Starter Pack doit être installé dans son propre dossier, sans lien symbolique.', 'wp-starter-pack' ) );
		}
		$network_active = is_multisite() && is_plugin_active_for_network( $file );
		deactivate_plugins( $file, true, $network_active );
		$result = delete_plugins( array( $file ) );
		if ( true !== $result ) {
			// Restaurer l’accès à l’interface si la suppression a échoué.
			if ( file_exists( WP_STARTER_PACK_FILE ) ) {
				activate_plugin( $file, '', $network_active, true );
			}
			$this->fail( __( 'Les modules sont installés, mais Starter Pack n’a pas pu être supprimé. Vérifiez son état dans Extensions.', 'wp-starter-pack' ) );
		}
		delete_transient( $this->session_key( $token ) );
		wp_send_json_success( array( 'deleted' => true ) );
	}
}
