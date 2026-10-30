<?php
/** Vue commune aux modules, addons et thèmes. Variables préparées par Admin_Page. */
defined( 'ABSPATH' ) || exit;
$identifier = $type . '-' . $slug;
$can_install = current_user_can( $is_theme ? 'install_themes' : 'install_plugins' );
$directory_url = $is_theme ? 'https://wordpress.org/themes/' . $slug . '/' : 'https://fr.wordpress.org/plugins/' . $slug . '/';
?>
<li class="wsp-module-item">
	<article class="wsp-module <?php echo $is_theme ? 'wsp-theme' : ''; ?>" data-slug="<?php echo esc_attr( $slug ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
		<div class="wsp-icon">
			<img class="wsp-resource-image" <?php if ( ! empty( $module['icon'] ) ) : ?>src="<?php echo esc_url( $module['icon'] ); ?>"<?php else : ?>hidden<?php endif; ?> alt="" width="64" height="64" loading="lazy" referrerpolicy="no-referrer">
			<span aria-hidden="true" <?php echo empty( $module['icon'] ) ? '' : 'hidden'; ?>><?php echo esc_html( substr( $module['name'], 0, 1 ) ); ?></span>
		</div>
		<div class="wsp-module-copy">
			<span class="wsp-category"><?php echo esc_html( $is_theme ? __( 'Thème WordPress', 'wp-starter-pack' ) : $module['category'] ); ?></span>
			<h3 id="wsp-name-<?php echo esc_attr( $identifier ); ?>"><?php echo esc_html( $module['name'] ); ?></h3>
			<p><?php echo esc_html( $module['description'] ); ?> <a class="wsp-learn-more" href="<?php echo esc_url( $directory_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'En savoir plus', 'wp-starter-pack' ); ?><span class="screen-reader-text"> : <?php echo esc_html( $module['name'] ); ?><?php esc_html_e( ' (nouvel onglet)', 'wp-starter-pack' ); ?></span></a></p>
			<dl class="wsp-metadata" data-type="<?php echo esc_attr( $type ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>" aria-label="<?php esc_attr_e( 'Informations WordPress.org', 'wp-starter-pack' ); ?>" aria-busy="true">
				<div><dt><span aria-hidden="true">★ </span><?php esc_html_e( 'Note', 'wp-starter-pack' ); ?></dt><dd data-field="rating">… / 5</dd></div>
				<div><dt><?php esc_html_e( 'Version disponible', 'wp-starter-pack' ); ?></dt><dd data-field="version">…</dd></div>
				<div><dt><?php esc_html_e( 'Installations actives', 'wp-starter-pack' ); ?></dt><dd data-field="activeInstalls">…</dd></div>
			</dl>
			<p class="wsp-metadata-error" hidden></p>
			<span class="wsp-module-status" id="wsp-status-<?php echo esc_attr( $identifier ); ?>"><?php echo $installed ? esc_html__( 'Déjà installé', 'wp-starter-pack' ) : esc_html__( 'Prêt à installer', 'wp-starter-pack' ); ?></span>
			<?php if ( ! $is_theme ) : ?>
				<div class="wsp-module-actions">
					<button type="button" class="button wsp-configure-button" data-slug="<?php echo esc_attr( $slug ); ?>" data-installed="<?php echo $installed ? 'true' : 'false'; ?>" data-allowed="<?php echo $can_activate ? 'true' : 'false'; ?>" <?php disabled( ! $installed || ! $can_activate ); ?> <?php echo $installed && $can_activate ? '' : 'hidden'; ?>><?php echo $active ? esc_html__( 'Configurer', 'wp-starter-pack' ) : esc_html__( 'Activer et configurer', 'wp-starter-pack' ); ?><span class="screen-reader-text"> : <?php echo esc_html( $module['name'] ); ?></span></button>
				</div>
			<?php endif; ?>
		</div>
		<label class="wsp-switch">
			<input type="checkbox" role="switch" name="<?php echo $is_theme ? 'themes[]' : 'plugins[]'; ?>" value="<?php echo esc_attr( $slug ); ?>" data-type="<?php echo esc_attr( $type ); ?>" data-installed="<?php echo $installed ? 'true' : 'false'; ?>" data-dependency="<?php echo esc_attr( $module['requires_parent'] ?? '' ); ?>" aria-labelledby="wsp-name-<?php echo esc_attr( $identifier ); ?>" aria-describedby="wsp-status-<?php echo esc_attr( $identifier ); ?>" <?php disabled( $installed || ! $can_install ); ?>>
			<span class="wsp-switch-track" aria-hidden="true"></span>
		</label>
	</article>
	<?php if ( $children ) : ?>
		<details class="wsp-addons">
			<summary><?php echo esc_html( sprintf( __( 'Modules complémentaires pour %s', 'wp-starter-pack' ), $module['name'] ) ); ?> <span>(<?php echo count( $children ); ?>)</span></summary>
			<?php if ( 'elementor' === $slug ) : ?><p class="wsp-addon-note"><?php esc_html_e( 'Choisissez les addons utiles à votre projet. Elementor sera installé en premier si nécessaire ; activez-le avant ses addons.', 'wp-starter-pack' ); ?></p><?php endif; ?>
			<ul class="wsp-modules wsp-child-modules">
				<?php foreach ( $children as $child_slug => $child ) { $this->render_item( 'plugin', $child_slug, $child ); } ?>
			</ul>
		</details>
	<?php endif; ?>
</li>
