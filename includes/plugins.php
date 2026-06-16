<?php
/**
 * Third-party plugin style registry and helpers.
 *
 * @package DarkAdmin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns all built-in plugin style definitions.
 *
 * Each entry:
 *  - label         Human-readable plugin name.
 *  - description   Short note shown on the settings page.
 *  - css           Filename inside assets/css/plugins/.
 *  - is_installed  Callable that returns true when the plugin is installed and active.
 *
 * @return array<string, array{label: string, description: string, css: string, is_installed: callable(): bool}>
 */
function darkadmin_plugin_registry(): array {
	return array(
		'yoast'          => array(
			'label'        => 'Yoast SEO',
			'description'  => __( 'SEO settings, metaboxes and dashboard widgets.', 'darkadmin-dark-mode-for-adminpanel' ),
			'css'          => 'yoast.css',
			'is_installed' => static function (): bool {
				return defined( 'WPSEO_VERSION' );
			},
		),
		'wordfence'      => array(
			'label'        => 'Wordfence',
			'description'  => __( 'Firewall, scan and security options pages.', 'darkadmin-dark-mode-for-adminpanel' ),
			'css'          => 'wordfence.css',
			'is_installed' => static function (): bool {
				return defined( 'WORDFENCE_VERSION' );
			},
		),
		'updraftplus'    => array(
			'label'        => 'UpdraftPlus',
			'description'  => __( 'Backup, restore and migration screens.', 'darkadmin-dark-mode-for-adminpanel' ),
			'css'          => 'updraftplus.css',
			'is_installed' => static function (): bool {
				return defined( 'UPDRAFTPLUS_VERSION' ) || class_exists( 'UpdraftPlus', false );
			},
		),
		'wp-optimize'    => array(
			'label'        => 'WP-Optimize',
			'description'  => __( 'Database, cache and image optimization UI.', 'darkadmin-dark-mode-for-adminpanel' ),
			'css'          => 'wp-optimize.css',
			'is_installed' => static function (): bool {
				return defined( 'WPO_VERSION' ) || class_exists( 'WP_Optimize', false );
			},
		),
		'contact-form-7' => array(
			'label'        => 'Contact Form 7',
			'description'  => __( 'Form editor, tag generator, integration and list table.', 'darkadmin-dark-mode-for-adminpanel' ),
			'css'          => 'contact-form-7.css',
			'is_installed' => static function (): bool {
				return defined( 'WPCF7_VERSION' );
			},
		),
	);
}

/**
 * Whether a registry slug refers to an installed, active plugin.
 *
 * @param string $slug Plugin style slug.
 * @return bool
 */
function darkadmin_plugin_is_installed( string $slug ): bool {
	$registry = darkadmin_plugin_registry();
	if ( ! isset( $registry[ $slug ] ) ) {
		return false;
	}
	$callback = $registry[ $slug ]['is_installed'];
	return is_callable( $callback ) && (bool) call_user_func( $callback );
}

/**
 * Sanitize callback for darkadmin_plugins.
 *
 * @param mixed $value Raw input.
 * @return string[]
 */
function darkadmin_sanitize_plugins( $value ): array {
	$allowed = array_keys( darkadmin_plugin_registry() );
	$slugs   = array_map( 'sanitize_key', (array) $value );
	$slugs   = array_values( array_filter( $slugs ) );
	return array_values( array_intersect( $slugs, $allowed ) );
}

/**
 * Enqueues plugin stylesheets for enabled, installed plugins.
 *
 * @return void
 */
function darkadmin_enqueue_plugin_styles(): void {
	$enabled = (array) get_option( 'darkadmin_plugins', array() );
	if ( empty( $enabled ) ) {
		return;
	}

	foreach ( darkadmin_plugin_registry() as $slug => $meta ) {
		if ( ! in_array( $slug, $enabled, true ) ) {
			continue;
		}
		if ( ! darkadmin_plugin_is_installed( $slug ) ) {
			continue;
		}

		$path = DARKADMIN_PATH . 'assets/css/plugins/' . $meta['css'];
		if ( ! is_readable( $path ) ) {
			continue;
		}

		wp_enqueue_style(
			'darkadmin-plugin-' . $slug,
			DARKADMIN_URL . 'assets/css/plugins/' . $meta['css'],
			array( 'darkadmin-darkmode' ),
			DARKADMIN_VERSION
		);
	}
}
