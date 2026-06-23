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
		'wordpress-ai'   => array(
			'label'        => __( 'WordPress AI & Connectors', 'darkadmin-dark-mode-for-adminpanel' ),
			'description'  => __( 'Connectors, AI settings, connector approvals and request logs.', 'darkadmin-dark-mode-for-adminpanel' ),
			'css'          => 'wordpress-ai.css',
			'is_installed' => static function (): bool {
				return function_exists( 'wp_options_connectors_wp_admin_render_page' )
					|| defined( 'WPAI_VERSION' );
			},
		),
	);
}

/**
 * Screen IDs for WordPress AI admin pages (Boot/WPDS and Tools screens).
 *
 * @return string[]
 */
function darkadmin_wordpress_ai_screen_ids(): array {
	return array(
		'options-connectors',
		'settings_page_ai-wp-admin',
		'tools_page_ai-connector-approval',
		'tools_page_ai-request-logs',
	);
}

/**
 * Whether the current admin screen is a WordPress AI page covered by wordpress-ai.css.
 *
 * @return bool
 */
function darkadmin_is_wordpress_ai_admin_screen(): bool {
	$screen = get_current_screen();
	if ( ! $screen instanceof WP_Screen ) {
		return false;
	}

	return in_array( $screen->id, darkadmin_wordpress_ai_screen_ids(), true );
}

/**
 * Enqueues WordPress AI dark styles on Connectors, AI settings and Tools screens.
 *
 * Loaded automatically on matching screens when dark mode is active so users
 * do not need to enable the optional plugin stylesheet separately.
 *
 * @return void
 */
function darkadmin_enqueue_wordpress_ai_styles(): void {
	if ( ! darkadmin_is_wordpress_ai_admin_screen() ) {
		return;
	}

	$path = DARKADMIN_PATH . 'assets/css/plugins/wordpress-ai.css';
	if ( ! is_readable( $path ) ) {
		return;
	}

	wp_enqueue_style(
		'darkadmin-wordpress-ai',
		DARKADMIN_URL . 'assets/css/plugins/wordpress-ai.css',
		array( 'darkadmin-darkmode' ),
		DARKADMIN_VERSION
	);
}

/**
 * Back-compat alias for darkadmin_wordpress_ai_screen_ids().
 *
 * @return string[]
 */
function darkadmin_boot_wpds_screen_ids(): array {
	return darkadmin_wordpress_ai_screen_ids();
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
