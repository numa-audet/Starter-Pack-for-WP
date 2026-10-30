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
				'configuration_path' => 'admin.php?page=Wordfence',
				'category' => 'Sécurité',
				'description' => __( 'Protégez votre site avec un pare-feu, un scanner de logiciels malveillants et des outils de sécurité de connexion.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/wordfence/assets/icon.svg',
			),
			'elementor' => array(
				'name' => 'Elementor',
				'file' => 'elementor/elementor.php',
				'configuration_path' => 'admin.php?page=elementor',
				'category' => 'Création de pages',
				'description' => __( 'Composez vos pages visuellement grâce à un éditeur par glisser-déposer et des mises en page personnalisables.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/elementor/assets/icon-256x256.gif',
			),
			'wordpress-seo' => array(
				'name' => 'Yoast SEO',
				'file' => 'wordpress-seo/wp-seo.php',
				'configuration_path' => 'admin.php?page=wpseo_dashboard',
				'category' => 'Référencement',
				'description' => __( 'Améliorez le référencement de vos contenus avec des conseils SEO, des métadonnées et des plans de site XML.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/wordpress-seo/assets/icon-256x256.gif',
			),
			'wpforms-lite' => array(
				'name' => 'WPForms Lite',
				'file' => 'wpforms-lite/wpforms.php',
				'configuration_path' => 'admin.php?page=wpforms-overview',
				'category' => 'Formulaires',
				'description' => __( 'Créez des formulaires de contact avec un éditeur visuel et des modèles prêts à personnaliser.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/wpforms-lite/assets/icon.svg',
			),
			'google-site-kit' => array(
				'name' => 'Site Kit par Google',
				'file' => 'google-site-kit/google-site-kit.php',
				'configuration_path' => 'admin.php?page=googlesitekit-dashboard',
				'category' => 'Statistiques',
				'description' => __( 'Retrouvez les données de vos services Google, dont Analytics et Search Console, dans votre tableau de bord WordPress.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/google-site-kit/assets/icon-256x256.png',
			),
			'woocommerce' => array(
				'name' => 'WooCommerce', 'file' => 'woocommerce/woocommerce.php',
				'configuration_path' => 'admin.php?page=wc-admin', 'category' => 'Boutique en ligne',
				'description' => __( 'Créez votre boutique, gérez les produits, les commandes et les paiements depuis WordPress.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/woocommerce/assets/icon-256x256.png',
			),
			'essential-addons-for-elementor-lite' => array(
				'name' => 'Essential Addons for Elementor', 'file' => 'essential-addons-for-elementor-lite/essential_adons_elementor.php',
				'configuration_path' => 'admin.php?page=eael-settings', 'category' => 'Addon Elementor',
				'parent' => 'elementor', 'requires_parent' => 'elementor',
				'description' => __( 'Complétez Elementor avec des widgets et des modèles pour composer vos pages.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/essential-addons-for-elementor-lite/assets/icon-256x256.png',
			),
			'premium-addons-for-elementor' => array(
				'name' => 'Premium Addons for Elementor', 'file' => 'premium-addons-for-elementor/premium-addons-for-elementor.php',
				'configuration_path' => 'admin.php?page=premium-addons', 'category' => 'Addon Elementor',
				'parent' => 'elementor', 'requires_parent' => 'elementor',
				'description' => __( 'Ajoutez des widgets, des effets visuels et des modèles à votre éditeur Elementor.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/premium-addons-for-elementor/assets/icon-256x256.png',
			),
			'elementskit-lite' => array(
				'name' => 'ElementsKit Lite', 'file' => 'elementskit-lite/elementskit-lite.php',
				'configuration_path' => 'admin.php?page=elementskit', 'category' => 'Addon Elementor',
				'parent' => 'elementor', 'requires_parent' => 'elementor',
				'description' => __( 'Enrichissez Elementor avec des widgets, des méga-menus et des outils de création d’en-têtes et de pieds de page.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/elementskit-lite/assets/icon-256x256.png',
			),
			'header-footer-elementor' => array(
				'name' => 'Ultimate Addons for Elementor', 'file' => 'header-footer-elementor/header-footer-elementor.php',
				'configuration_path' => 'edit.php?post_type=elementor-hf', 'category' => 'Addon Elementor',
				'parent' => 'elementor', 'requires_parent' => 'elementor',
				'description' => __( 'Créez des en-têtes et des pieds de page et ajoutez des widgets Elementor avec Ultimate Addons.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/header-footer-elementor/assets/icon-256x256.png',
			),
			'wp-mail-smtp' => array(
				'name' => 'WP Mail SMTP', 'file' => 'wp-mail-smtp/wp_mail_smtp.php',
				'configuration_path' => 'admin.php?page=wp-mail-smtp', 'category' => 'Envoi des e-mails',
				'parent' => 'wpforms-lite',
				'description' => __( 'Configurez un service SMTP pour fiabiliser l’envoi des e-mails et des notifications de formulaires. Fonctionne aussi sans WPForms.', 'wp-starter-pack' ),
				'icon' => 'https://ps.w.org/wp-mail-smtp/assets/icon-256x256.png',
			),
		);
	}

	public static function children( $parent ) {
		return array_filter( self::all(), static function ( $module ) use ( $parent ) {
			return isset( $module['parent'] ) && $parent === $module['parent'];
		} );
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
