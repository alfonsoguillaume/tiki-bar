<?php
/**
 * En-tête du site : ouvre le HTML, affiche le logo/titre et le menu principal.
 * Ce fichier est appelé par get_header() dans tous les autres templates.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php wp_head(); // Hook indispensable : c'est ici que WP, les plugins et le SEO injectent leur code. ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); // Hook recommandé depuis WP 5.2 (accessibilité, tracking propre). ?>

<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Aller au contenu principal', 'tiki-bar' ); ?></a>

<header class="site-header">
	<div class="site-header-inner container">

		<a class="site-branding" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php endif; ?>
			<span>
				<p class="site-title"><?php bloginfo( 'name' ); ?></p>
				<?php $description = get_bloginfo( 'description', 'display' ); ?>
				<?php if ( $description ) : ?>
					<p class="site-description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</span>
		</a>

		<button class="primary-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
			<?php esc_html_e( 'Menu', 'tiki-bar' ); ?>
		</button>

		<nav class="primary-navigation" id="primary-menu" aria-label="<?php esc_attr_e( 'Menu principal', 'tiki-bar' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => false, // pas de menu par défaut moche si rien n'est configuré
			) );
			?>
		</nav>

	</div>
	<div class="frond-divider" aria-hidden="true"></div>
</header>

<main id="main-content">
