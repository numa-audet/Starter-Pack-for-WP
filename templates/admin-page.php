<?php
/** @var array $catalog Catalogue préparé par Admin_Page. */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wsp-page">
	<header class="wsp-header">
		<span class="wsp-eyebrow">WP STARTER PACK</span>
		<h1><?php esc_html_e( 'Les essentiels pour bien démarrer.', 'wp-starter-pack' ); ?></h1>
		<p><?php esc_html_e( 'Choisissez les modules dont votre site a besoin. Starter Pack s’occupe de leur installation.', 'wp-starter-pack' ); ?></p>
	</header>
	<form id="wsp-installation-form">
		<section class="wsp-panel" aria-labelledby="wsp-modules-heading">
			<div class="wsp-panel-heading">
				<h2 id="wsp-modules-heading"><?php esc_html_e( 'Vos modules', 'wp-starter-pack' ); ?></h2>
				<span><?php esc_html_e( '5 extensions gratuites', 'wp-starter-pack' ); ?></span>
			</div>
			<ul class="wsp-modules">
				<?php foreach ( $catalog as $slug => $module ) : ?>
					<?php $installed = \WPStarterPack\Plugin_Catalog::is_installed( $slug ); ?>
					<li class="wsp-module" data-slug="<?php echo esc_attr( $slug ); ?>">
						<div class="wsp-icon"><img src="<?php echo esc_url( $module['icon'] ); ?>" alt="" width="64" height="64" loading="lazy" referrerpolicy="no-referrer"><span aria-hidden="true" hidden><?php echo esc_html( substr( $module['name'], 0, 1 ) ); ?></span></div>
						<div class="wsp-module-copy">
							<span class="wsp-category"><?php echo esc_html( $module['category'] ); ?></span>
							<h3 id="wsp-name-<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $module['name'] ); ?></h3>
							<p><?php echo esc_html( $module['description'] ); ?> <a href="<?php echo esc_url( 'https://fr.wordpress.org/plugins/' . $slug . '/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'En savoir plus', 'wp-starter-pack' ); ?><span class="screen-reader-text"> <?php echo esc_html( $module['name'] ); ?><?php esc_html_e( ' (nouvel onglet)', 'wp-starter-pack' ); ?></span></a></p>
							<span class="wsp-module-status" id="wsp-status-<?php echo esc_attr( $slug ); ?>"><?php echo $installed ? esc_html__( 'Déjà installé', 'wp-starter-pack' ) : esc_html__( 'Prêt à installer', 'wp-starter-pack' ); ?></span>
						</div>
						<label class="wsp-switch">
							<input type="checkbox" role="switch" name="plugins[]" value="<?php echo esc_attr( $slug ); ?>" aria-labelledby="wsp-name-<?php echo esc_attr( $slug ); ?>" aria-describedby="wsp-status-<?php echo esc_attr( $slug ); ?>" <?php disabled( $installed ); ?>>
							<span class="wsp-switch-track" aria-hidden="true"></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<div class="wsp-cleanup">
			<input type="checkbox" id="wsp-remove-after-install" <?php disabled( ! current_user_can( 'delete_plugins' ) ); ?>>
			<label for="wsp-remove-after-install"><strong><?php esc_html_e( 'Supprimer le module Starter Pack automatiquement après l’installation', 'wp-starter-pack' ); ?></strong><span><?php esc_html_e( 'Oui si la case est cochée, non sinon. La suppression aura lieu uniquement si tous les modules sélectionnés sont installés.', 'wp-starter-pack' ); ?></span></label>
		</div>
		<div class="wsp-actions">
			<button type="submit" class="button button-primary button-hero" id="wsp-install-button"><?php esc_html_e( 'Installer les modules', 'wp-starter-pack' ); ?></button>
			<span id="wsp-selection-count" aria-live="polite"></span>
		</div>
		<p class="wsp-note"><?php esc_html_e( 'Installation depuis WordPress.org, sans activation automatique. Activez et configurez ensuite vos extensions dans le menu Extensions. Les modules déjà installés sont conservés.', 'wp-starter-pack' ); ?></p>
		<noscript><p class="notice notice-warning"><?php esc_html_e( 'Activez JavaScript pour utiliser l’installation des modules.', 'wp-starter-pack' ); ?></p></noscript>
	</form>
	<div id="wsp-progress" class="wsp-progress" hidden><label for="wsp-progress-bar"><?php esc_html_e( 'Progression de l’installation', 'wp-starter-pack' ); ?></label><progress id="wsp-progress-bar" value="0" max="5"></progress></div>
	<div id="wsp-result" class="wsp-result" role="status" aria-live="polite" aria-atomic="true" hidden></div>
	<p id="wsp-extensions-link" hidden><a class="button" href="<?php echo esc_url( network_admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Gérer mes extensions', 'wp-starter-pack' ); ?></a></p>
</div>
