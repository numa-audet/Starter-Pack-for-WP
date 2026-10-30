<?php
namespace WPStarterPack;

defined( 'ABSPATH' ) || exit;

final class Theme_Catalog {
	public static function all() {
		return array(
			'hello-elementor' => array( 'name' => 'Hello Elementor', 'description' => __( 'Une base légère pour construire votre site avec Elementor.', 'wp-starter-pack' ) ),
			'hello-biz' => array( 'name' => 'Hello Biz', 'description' => __( 'Un thème conçu pour créer un site professionnel avec Elementor.', 'wp-starter-pack' ) ),
			'hello-commerce' => array( 'name' => 'Hello Commerce', 'description' => __( 'Une base pour créer une boutique avec WooCommerce et Elementor.', 'wp-starter-pack' ) ),
			'astra' => array( 'name' => 'Astra', 'description' => __( 'Un thème polyvalent et personnalisable pour vos sites et boutiques.', 'wp-starter-pack' ) ),
		);
	}

	public static function is_installed( $slug ) {
		return isset( self::all()[ $slug ] ) && wp_get_theme( $slug )->exists();
	}
}
