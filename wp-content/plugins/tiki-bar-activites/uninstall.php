<?php
/**
 * Ce fichier est automatiquement exécuté par WordPress quand l'admin
 * SUPPRIME complètement le plugin depuis Extensions (pas à une simple
 * désactivation). C'est le bon endroit pour nettoyer ce qui ne doit pas
 * traîner indéfiniment, comme un rôle personnalisé.
 */

// Sécurité : ce fichier ne doit être exécuté que par WordPress lui-même
// lors d'une désinstallation, jamais appelé directement.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-roles.php';
TikiBar_Roles::remove_role();
