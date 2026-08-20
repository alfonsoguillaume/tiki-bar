<?php
/**
 * Gestion du rôle personnalisé "Gestionnaire" et de ses capacités.
 *
 * Principe : le rôle Administrateur natif de WordPress garde TOUS ses droits
 * (y compris sur les soirées/réservations, qu'on lui ajoute explicitement
 * ci-dessous, car WordPress ne les donne pas automatiquement dès qu'on
 * personnalise le capability_type d'un CPT). Le rôle "Gestionnaire" ne reçoit
 * QUE les capacités liées aux soirées et réservations : rien sur le thème,
 * les plugins, les réglages ou les autres comptes utilisateurs.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TikiBar_Roles {

	const ROLE_SLUG = 'gestionnaire_tikibar';

	/**
	 * Construit la liste complète des capacités générées par WordPress pour
	 * un CPT dont le capability_type est un tableau (singulier, pluriel).
	 * C'est le jeu de capacités "standard" (le même schéma que pour les
	 * articles de blog, mais avec nos propres noms).
	 */
	private static function get_cpt_capabilities( $pluriel ) {
		return array(
			"edit_{$pluriel}",
			"edit_others_{$pluriel}",
			"publish_{$pluriel}",
			"read_private_{$pluriel}",
			"delete_{$pluriel}",
			"delete_private_{$pluriel}",
			"delete_published_{$pluriel}",
			"delete_others_{$pluriel}",
			"edit_private_{$pluriel}",
			"edit_published_{$pluriel}",
		);
	}

	/**
	 * Toutes les capacités liées à NOS deux CPT (soirées + réservations).
	 * Utilisé à la fois pour l'administrateur et pour le Gestionnaire :
	 * les deux ont un accès complet au contenu métier du site, seule la
	 * partie "réglages généraux du site" les différenciera.
	 */
	public static function get_all_business_capabilities() {
		return array_merge(
			self::get_cpt_capabilities( 'soirees' ),
			self::get_cpt_capabilities( 'reservations' )
		);
	}

	/**
	 * Donne au rôle Administrateur natif les capacités sur nos CPT.
	 * Sans ça, même l'admin perdrait l'accès aux soirées/réservations dès
	 * qu'on personnalise le capability_type (WordPress ne fait jamais cette
	 * association automatiquement).
	 */
	public static function grant_capabilities_to_administrator() {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( self::get_all_business_capabilities() as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Crée le rôle "Gestionnaire" s'il n'existe pas déjà, avec uniquement :
	 * - la capacité de base pour accéder à l'admin ('read')
	 * - la capacité d'uploader des images (pour les images de soirées)
	 * - toutes les capacités liées aux soirées et réservations
	 * Volontairement AUCUNE capacité générale (thème, plugins, utilisateurs...).
	 */
	public static function create_role() {
		// On ne réécrase pas un rôle existant : si l'admin a manuellement
		// ajusté ses capacités depuis la création, on ne veut pas tout écraser
		// à chaque réactivation du plugin.
		if ( get_role( self::ROLE_SLUG ) ) {
			return;
		}

		add_role(
			self::ROLE_SLUG,
			__( 'Gestionnaire', 'tiki-bar-activites' ),
			array(
				'read'         => true,
				'upload_files' => true,
			)
		);

		$role = get_role( self::ROLE_SLUG );
		foreach ( self::get_all_business_capabilities() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Supprime le rôle personnalisé. Appelé uniquement à la désinstallation
	 * complète du plugin (pas à une simple désactivation), pour ne pas
	 * perdre le rôle si l'admin désactive/réactive le plugin par erreur.
	 */
	public static function remove_role() {
		remove_role( self::ROLE_SLUG );
	}
}
