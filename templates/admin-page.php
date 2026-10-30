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
		<div class="wsp-catalog-columns">
			<section class="wsp-panel" aria-labelledby="wsp-modules-heading">
				<div class="wsp-panel-heading"><h2 id="wsp-modules-heading"><?php esc_html_e( 'Vos modules', 'wp-starter-pack' ); ?></h2><span><?php esc_html_e( '6 modules et 5 compléments', 'wp-starter-pack' ); ?></span></div>
				<div class="wsp-section-selection">
					<label for="wsp-select-all-plugins" id="wsp-select-all-plugins-label"><?php esc_html_e( 'Tout sélectionner', 'wp-starter-pack' ); ?></label>
					<label class="wsp-switch">
						<input type="checkbox" role="switch" id="wsp-select-all-plugins" aria-labelledby="wsp-select-all-plugins-label wsp-modules-heading">
						<span class="wsp-switch-track" aria-hidden="true"></span>
					</label>
				</div>
				<ul class="wsp-modules">
					<?php foreach ( $catalog as $slug => $module ) { if ( empty( $module['parent'] ) ) { $this->render_item( 'plugin', $slug, $module ); } } ?>
				</ul>
			</section>
			<section class="wsp-panel wsp-themes-panel" aria-labelledby="wsp-themes-heading">
				<div class="wsp-panel-heading"><h2 id="wsp-themes-heading"><?php esc_html_e( 'Vos thèmes', 'wp-starter-pack' ); ?></h2><span><?php esc_html_e( '4 thèmes au choix', 'wp-starter-pack' ); ?></span></div>
				<div class="wsp-section-selection">
					<label for="wsp-select-all-themes" id="wsp-select-all-themes-label"><?php esc_html_e( 'Tout sélectionner', 'wp-starter-pack' ); ?></label>
					<label class="wsp-switch">
						<input type="checkbox" role="switch" id="wsp-select-all-themes" aria-labelledby="wsp-select-all-themes-label wsp-themes-heading">
						<span class="wsp-switch-track" aria-hidden="true"></span>
					</label>
				</div>
				<p class="wsp-theme-note"><?php esc_html_e( 'Installez les thèmes de votre choix. Le thème actif reste inchangé. Activez ensuite votre thème depuis Apparence.', 'wp-starter-pack' ); ?></p>
				<ul class="wsp-modules">
					<?php foreach ( $themes as $slug => $theme ) { $this->render_item( 'theme', $slug, $theme ); } ?>
				</ul>
			</section>
		</div>
		<div class="wsp-cleanup">
			<input type="checkbox" id="wsp-remove-after-install" <?php disabled( ! current_user_can( 'delete_plugins' ) ); ?>>
			<label for="wsp-remove-after-install"><strong><?php esc_html_e( 'Supprimer le module Starter Pack automatiquement après l’installation', 'wp-starter-pack' ); ?></strong><span><?php esc_html_e( 'Oui si la case est cochée, non sinon. La suppression aura lieu uniquement si tous les modules et thèmes sélectionnés sont installés.', 'wp-starter-pack' ); ?></span></label>
		</div>
		<div class="wsp-actions">
			<button type="submit" class="button button-primary button-hero" id="wsp-install-button"><?php esc_html_e( 'Installer les modules', 'wp-starter-pack' ); ?></button>
			<span id="wsp-selection-count" aria-live="polite"></span>
		</div>
		<p class="wsp-note"><?php esc_html_e( 'Installation depuis WordPress.org, sans activation automatique. Si Starter Pack est conservé, utilisez les boutons « Activer et configurer » pour ouvrir la configuration de chaque module. Les modules déjà installés sont conservés.', 'wp-starter-pack' ); ?></p>
		<noscript><p class="notice notice-warning"><?php esc_html_e( 'Activez JavaScript pour utiliser l’installation des modules.', 'wp-starter-pack' ); ?></p></noscript>
			<p class="wsp-note"><?php esc_html_e( 'Notes, versions disponibles et installations actives : données WordPress.org, actualisées au maximum toutes les 6 heures. Les nombres d’installations sont des estimations du répertoire.', 'wp-starter-pack' ); ?></p>
	</form>
	<div id="wsp-progress" class="wsp-progress" hidden><label for="wsp-progress-bar"><?php esc_html_e( 'Progression de l’installation', 'wp-starter-pack' ); ?></label><progress id="wsp-progress-bar" value="0" max="15"></progress></div>
	<div id="wsp-result" class="wsp-result" role="status" aria-live="polite" aria-atomic="true" hidden></div>
	<p id="wsp-extensions-link" hidden><a class="button" href="<?php echo esc_url( network_admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Gérer mes extensions', 'wp-starter-pack' ); ?></a></p>
</div>
