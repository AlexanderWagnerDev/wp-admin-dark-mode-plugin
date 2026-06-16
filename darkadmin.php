<?php
/**
 * Plugin Name: DarkAdmin - Dark Mode for Adminpanel
 * Plugin URI: https://wordpress.org/plugins/darkadmin-dark-mode-for-adminpanel/
 * Description: Simple, lightweight Dark Mode Plugin for the WordPress Admin Dashboard.
 * Version: 0.3.0
 * Requires at least: 6.3
 * Tested up to: 7.0
 * Requires PHP: 8.0
 * Author: AlexanderWagnerDev
 * Author URI: https://alexanderwagnerdev.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: darkadmin-dark-mode-for-adminpanel
 * Domain Path: /languages
 *
 * @package DarkAdmin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DARKADMIN_VERSION', '0.3.0' );
define( 'DARKADMIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DARKADMIN_PATH', plugin_dir_path( __FILE__ ) );

/** Default preset slug for new installations. */
define( 'DARKADMIN_DEFAULT_PRESET', 'modern' );

require_once DARKADMIN_PATH . 'includes/defaults.php';
require_once DARKADMIN_PATH . 'includes/user-settings.php';
require_once DARKADMIN_PATH . 'includes/plugins.php';
require_once DARKADMIN_PATH . 'includes/enqueue.php';
require_once DARKADMIN_PATH . 'includes/settings-page.php';

/**
 * One-time migrations for existing installations (preset rename, defaults).
 */
add_action(
	'admin_init',
	function () {
		if ( version_compare( (string) get_option( 'darkadmin_db_version', '0' ), '0.3.0', '>=' ) ) {
			return;
		}

		$preset = get_option( 'darkadmin_preset' );

		if ( 'default' === $preset ) {
			update_option( 'darkadmin_preset', 'classic' );
		} elseif ( false === $preset && darkadmin_is_existing_install() ) {
			// Pre-0.3.0 installs without a saved preset were using the classic palette.
			update_option( 'darkadmin_preset', 'classic' );
		}

		update_option( 'darkadmin_db_version', '0.3.0' );
	}
);

/**
 * Add a settings link in the Plugins list.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $actions ) {
		$url                 = admin_url( 'options-general.php?page=darkadmin' );
		$actions['settings'] = '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'darkadmin-dark-mode-for-adminpanel' ) . '</a>';
		return $actions;
	}
);

/**
 * Show an admin notice after settings are saved.
 * The WP Settings API redirects back with ?settings-updated=true after options.php.
 */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'darkadmin' !== $page ) {
			return;
		}

		if ( empty( sanitize_key( wp_unslash( $_GET['settings-updated'] ?? '' ) ) ) ) {
			return;
		}

		$enabled = (bool) get_option( 'darkadmin_dark_mode_enabled', false );
		$msg     = $enabled
			? __( '✓ Dark Mode is active. Settings have been saved.', 'darkadmin-dark-mode-for-adminpanel' )
			: __( '✓ Settings saved. Dark Mode is disabled.', 'darkadmin-dark-mode-for-adminpanel' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
);
