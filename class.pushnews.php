<?php

/*
Author:             Pushnews <developers@pushnews.eu>
License:            GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class Pushnews {
	const VERSION = '1.2.0';
	const RESOURCES_VERSION = '1';
	const API_URL = 'https://app.pushnews.eu/api.php/v1';
	const CDN_DOMAIN = 'cdn.pn.vg';

	const TAG = <<<MYHTML
<!-- Pushnews -->
<script src="//{%%cloudflare_domain%%}/sites/{%%app_id%%}.js" async></script>
<!-- / Pushnews -->
MYHTML;


	/**
	 *
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings_init' ) );
	}


	public static function translations_init() {
		load_plugin_textdomain( 'pushnews', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	public static function plugin_activation() {

		self::translations_init();

		$siteUrl          = get_option( 'siteurl' );
		$siteUrl64Encoded = PushnewsBase64Url::encode( $siteUrl );

		$endpoint     = self::API_URL . "/sites/{$siteUrl64Encoded}?filterBy=base64_url";
		$response     = wp_remote_get( $endpoint, array( 'headers' => array( 'X-Pushnews-Wp-Version' => self::VERSION ) ) );
		$pushnewsSite = wp_remote_retrieve_body( $response );
		$pushnewsSite = json_decode( $pushnewsSite, true );
		if ( JSON_ERROR_NONE == json_last_error() ) {
			if ( true == $pushnewsSite['success'] ) {

				$pushnewsSite = $pushnewsSite['data'];
				$tmp          = parse_url( $pushnewsSite['optin_url'] );
				$url          = $tmp['host'];

				$options = array(
					'active'                                       => 'true',
					'app_id'                                       => $pushnewsSite['app_id'],
				);
			} else {
				$options = array(
					'active'                                       => 'false',
					'app_id'                                       => '0000-0000-0000-0000',
				);
			}

			add_option( 'pushnews_options', $options );
		}
	}

	public static function plugin_deactivation() {
	}

	public static function plugin_uninstall() {
		delete_option( 'pushnews_options' );
	}

	public static function add_admin_page() {
		add_menu_page(
			'pushnews',
			__( 'Pushnews', 'pushnews' ),
			'manage_options',
			'pushnews',
			array( __CLASS__, 'admin_menu' )
		);

		add_submenu_page(
			'pushnews',
			__( 'Send Push Notification', 'pushnews' ),
			__( 'Send Push Notification', 'pushnews' ),
			'manage_options',
			'pushnews_send',
			function () {
				require_once( plugin_dir_path( __FILE__ ) . '/views/send.php' );
			}
		);
	}

	public static function admin_menu() {
		require_once( plugin_dir_path( __FILE__ ) . '/views/config.php' );
	}

	public static function admin_styles() {
		wp_enqueue_style( 'pushnews-admin-styles', plugin_dir_url( __FILE__ ) . 'views/css/pushnews-admin-styles.css', false, self::RESOURCES_VERSION );
	}


	public static function settings_init() {
		register_setting( "pushnews", "pushnews_options" );

		$arr = array(
			'basic'                             => array(
				'active'                  => __( "Active", "pushnews" ),
				'app_id'                  => __( "App ID", "pushnews" ),

			),
		);

		foreach ( $arr as $section_name => $section_items ) {

			if ( 'basic' == $section_name ) {
				$translation = __( "Configuration", "pushnews" );
			}

			add_settings_section(
				$section_name,
				$translation,
				function () {
				},
				'pushnews'
			);

			foreach ( $section_items as $k => $translation ) {
				$id = "pushnews_field_{$k}";

				$callback_function = array( __CLASS__, 'input_cb' );
				if ( 'basic' == $section_name && 'active' == $k ) {
					$callback_function = array( __CLASS__, 'checkbox_cb' );
				}

				add_settings_field(
					$id,
					$translation,
					$callback_function,
					'pushnews',
					$section_name,
					array(
						'label_for' => $k,
						'class'     => 'pushnews_row',
						'supplemental' => array(
							__( "To find your app id click", "pushnews" ),
							__( "here", "pushnews" )
						)
                    )
				);

			}
		}
	}

	public static function input_cb( $args ) {
		$options = get_option( 'pushnews_options' );
		?>
		<input
			type="text"
			id="<?= esc_attr( $args['label_for'] ); ?>"
			name="pushnews_options[<?= esc_attr( $args['label_for'] ); ?>]"
			value="<?= isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '' ?>"
		>
		<?php

		if( $supplimental = $args['supplemental'] ){
	        printf( '<p class="description">%s <a href="https://www.pushnews.com.br/como-saber-qual-o-seu-app-id" target="_blank">%s</a></p>', $supplimental[0], $supplimental[1]); // Show it
	    }
	}

	public static function checkbox_cb( $args ) {
		$options = get_option( 'pushnews_options' );
		$checked = isset( $options[ $args['label_for'] ] ) && true == filter_var( $options[ $args['label_for'] ],
			FILTER_VALIDATE_BOOLEAN ) ? true : false;
		?>
		<input
			type="checkbox"
			id="<?= esc_attr( $args['label_for'] ); ?>"
			name="pushnews_options[<?= esc_attr( $args['label_for'] ); ?>]"
			value="true"
			<?= $checked == true ? 'checked' : '' ?>
		>
		<?php
	}


	public static function inject_tag() {

		$options = get_option( 'pushnews_options' );

		if ( ! isset( $options['active'] ) || true != filter_var( $options['active'], FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$html = self::TAG;

		$replaces = array(
			'{%%cloudflare_domain%%}'                          	=> self::CDN_DOMAIN,
			'{%%app_id%%}'                                		=> trim( $options['app_id'] ),
		);

		echo str_replace( array_keys( $replaces ), $replaces, $html );
	}

}