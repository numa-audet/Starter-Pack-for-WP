<?php
/** Catalogue fermé : aucune extension ni URL de téléchargement fournie par le navigateur. */
namespace WPStarterPack;

defined( 'ABSPATH' ) || exit;

final class Plugin_Catalog {
	public static function all() {
		return array(
			'wordfence' => array(
				'name' => 'Wordfence',
				'file' => 'wordfence/wordfence.php',
				'category' => 'Sécurité',
				'description' => __( 'Protégez votre site avec un pare-feu, un scanner de logiciels malveillants et des outils de sécurité de connexion.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/wordfence/assets/icon.svg',
			),
			'elementor' => array(
				'name' => 'Elementor',
				'file' => 'elementor/elementor.php',
				'category' => 'Création de pages',
				'description' => __( 'Composez vos pages visuellement grâce à un éditeur par glisser-déposer et des mises en page personnalisables.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/elementor/assets/icon-256x256.gif',
			),
			'wordpress-seo' => array(
				'name' => 'Yoast SEO',
				'file' => 'wordpress-seo/wp-seo.php',
				'category' => 'Référencement',
				'description' => __( 'Améliorez le référencement de vos contenus avec des conseils SEO, des métadonnées et des plans de site XML.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/wordpress-seo/assets/icon-256x256.gif',
			),
			'wpforms-lite' => array(
				'name' => 'WPForms Lite',
				'file' => 'wpforms-lite/wpforms.php',
				'category' => 'Formulaires',
				'description' => __( 'Créez des formulaires de contact avec un éditeur visuel et des modèles prêts à personnaliser.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/wpforms-lite/assets/icon.svg',
			),
			'google-site-kit' => array(
				'name' => 'Site Kit par Google',
				'file' => 'google-site-kit/google-site-kit.php',
				'category' => 'Statistiques',
				'description' => __( 'Retrouvez les données de vos services Google, dont Analytics et Search Console, dans votre tableau de bord WordPress.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/google-site-kit/assets/icon-256x256.png',
			),
		);
	}

	public static function is_installed( $slug ) {
		$catalog = self::all();
		if ( ! isset( $catalog[ $slug ] ) ) {
			return false;
		}
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = get_plugins();
		return isset( $plugins[ $catalog[ $slug ]['file'] ] );
	}
}
