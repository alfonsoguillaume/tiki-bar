<?php
/**
 * Pied de page : ferme le <main> ouvert dans header.php, affiche le footer.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
</main><!-- #main-content -->

<footer class="site-footer">
	<div class="frond-divider" aria-hidden="true"></div>
	<div class="container">
		<div>
			<p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Adresse communiquée par e-mail après validation de la réservation.', 'tiki-bar' ); ?></p>
		</div>

		<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
			<div class="footer-widgets">
				<?php dynamic_sidebar( 'footer-1' ); ?>
			</div>
		<?php endif; ?>

		<nav aria-label="<?php esc_attr_e( 'Menu de pied de page', 'tiki-bar' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'container'      => false,
				'fallback_cb'    => false,
			) );
			?>
		</nav>
	</div>
</footer>

<?php wp_footer(); // Hook indispensable : scripts et plugins en ont besoin en fin de page. ?>
</body>
</html>
