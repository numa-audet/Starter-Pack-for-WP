<?php
namespace WPStarterPack;

defined( 'ABSPATH' ) || exit;

final class Admin_Page {
	private $hook = '';

	public function register() {
		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WP_STARTER_PACK_FILE ), array( $this, 'action_links' ) );
		add_filter( 'network_admin_plugin_action_links_' . plugin_basename( WP_STARTER_PACK_FILE ), array( $this, 'action_links' ) );
	}

	public function add_menu() {
		$this->hook = add_menu_page( 'WP Starter Pack', 'Starter Pack', 'install_plugins', 'wp-starter-pack', array( $this, 'render' ), plugin_dir_url( WP_STARTER_PACK_FILE ) . 'img/starter-pack-for-WP_icone.png', 65 );
	}

	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( network_admin_url( 'admin.php?page=wp-starter-pack' ) ) . '">' . esc_html__( 'Choisir les modules', 'wp-starter-pack' ) . '</a>' );
		return $links;
	}

	public function enqueue_assets( $hook ) {
		wp_add_inline_style( 'common', '#toplevel_page_wp-starter-pack .wp-menu-image img { width: 20px; height: 20px; object-fit: contain; }' );
		if ( $hook !== $this->hook ) {
			return;
		}
		$url = plugin_dir_url( WP_STARTER_PACK_FILE );
		wp_enqueue_style( 'wp-starter-pack', $url . 'assets/css/admin.css', array(), WP_STARTER_PACK_VERSION );
		wp_enqueue_script( 'wp-starter-pack', $url . 'assets/js/admin.js', array(), WP_STARTER_PACK_VERSION, true );
		wp_enqueue_script( 'wp-starter-pack-metadata', $url . 'assets/js/directory-metadata.js', array( 'wp-starter-pack' ), WP_STARTER_PACK_VERSION, true );
		wp_localize_script( 'wp-starter-pack', 'wpStarterPack', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wp_starter_pack_install' ),
			'activationNonce' => wp_create_nonce( 'wp_starter_pack_activate' ),
			'metadataNonce' => wp_create_nonce( 'wp_starter_pack_metadata' ),
			'pluginsUrl' => network_admin_url( 'plugins.php' ),
			'messages' => array(
				'networkError' => __( 'La réponse du serveur est invalide ou la connexion a été interrompue. Rechargez la page pour vérifier les installations avant de réessayer.', 'wp-starter-pack' ),
				'choose' => __( 'Sélectionnez au moins un module ou un thème.', 'wp-starter-pack' ),
				'metadataUnavailable' => __( 'Informations WordPress.org indisponibles pour le moment.', 'wp-starter-pack' ),
				'installing' => __( 'Installation en cours…', 'wp-starter-pack' ),
				'installed' => __( 'Installé', 'wp-starter-pack' ),
				'failed' => __( 'Échec', 'wp-starter-pack' ),
				'partial' => __( 'Certaines installations ont échoué. Starter Pack a été conservé. Vous pouvez réessayer les modules en échec.', 'wp-starter-pack' ),
				'done' => __( 'Votre sélection est installée. Les modules peuvent être activés avec « Activer et configurer ». Retrouvez les thèmes dans Apparence ; le thème actif n’a pas été modifié.', 'wp-starter-pack' ),
				'activating' => __( 'Activation en cours…', 'wp-starter-pack' ),
				'deleted' => __( 'Les modules sont installés et Starter Pack a été supprimé. Vous pouvez maintenant activer vos extensions.', 'wp-starter-pack' ),
				'finalizing' => __( 'Vérification des installations…', 'wp-starter-pack' ),
			),
		) );
	}

	public function render() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die( esc_html__( 'Vous ne pouvez pas installer d’extensions sur ce site.', 'wp-starter-pack' ) );
		}
		$catalog = Plugin_Catalog::all();
		$themes = Theme_Catalog::all();
		require dirname( __DIR__ ) . '/templates/admin-page.php';
	}

	public function render_item( $type, $slug, $module ) {
		$is_theme = 'theme' === $type;
		$installed = $is_theme ? Theme_Catalog::is_installed( $slug ) : Plugin_Catalog::is_installed( $slug );
		$active = ! $is_theme && $installed && is_plugin_active( $module['file'] );
		$can_activate = ! $is_theme && current_user_can( 'activate_plugin', $module['file'] ) && ( ! is_multisite() || current_user_can( 'manage_network_plugins' ) );
		$children = $is_theme ? array() : Plugin_Catalog::children( $slug );
		require dirname( __DIR__ ) . '/templates/catalog-item.php';
	}
}
