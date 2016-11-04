<?php

/*
Author:             Tiago Carvalho <tiago.carvalho@impacting.digital>
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
	const VERSION = '1.0.0';
	const RESOURCES_VERSION = '1';
//	const API_URL = 'http://local.admin.pushnews.eu/api.php/v1';
	const API_URL = 'https://admin.pushnews.eu/api.php/v1';
	const CDN_DOMAIN = 'cdn.pushnews.eu';

	const TAG = <<<MYHTML
<!-- Pushnews -->
<script src="//{%%cloudfront_domain%%}/push/ilabspush.min.js" async></script>
<script type="text/javascript">
    var _ilabsPushConfig = {
        optin: {
            activation: {
                type: "{%%subscription_request.activation_type%%}"{%%subscription_request.activation_type.extra%%}
            },
            "%desktopImage%": "{%%optin.desktopImage%%}",
            "%desktopTxtTitle%": "{%%subscription_request.title%%}",
            "%desktopTxtBody%": "{%%subscription_request.body%%}",
            "%desktopTxtButtonNo%": "{%%subscription_request.btn_no%%}",
            "%desktopTxtButtonYes%": "{%%subscription_request.btn_yes%%}",
            "%mobileImage%": "{%%optin.mobileImage%%}",
            "%mobileTxtTitle%": "{%%subscription_request.title%%}",
            "%mobileTxtBody%": "{%%subscription_request.body%%}",
            "%mobileTxtButtonNo%": "{%%subscription_request.btn_no%%}",
            "%mobileTxtButtonYes%": "{%%subscription_request.btn_yes%%}"
        },
        popup: {
            name: "{%%popup.name%%}",
            domain: "{%%popup.domain%%}",
            appId: "{%%popup.appId%%}",
            actionMessage: "{%%confirmation_popup.message%%}",
            notificationIcon: "{%%popup.notificationIcon%%}",
            notificationTitle: "{%%confirmation_popup.title%%}",
            notificationMessage: "{%%confirmation_popup.body%%}",
            caption: "{%%confirmation_popup.caption%%}"
        }
    };
    var IlabsPush = IlabsPush || [];
    IlabsPush.push(["_initHttps", _ilabsPushConfig]);
</script>
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
					'website_name'                                 => $pushnewsSite['name'],
					'website_square_logo_url'                      => 'https://ilabs-static.s3.amazonaws.com/push/icon128_pushnews.jpg',
					'app_id'                                       => $pushnewsSite['app_id'],
					'pushnews_subdomain'                           => $url,
					'subscription_request.title'                   => $pushnewsSite['configuration']['subscription_request']['title'],
					'subscription_request.body'                    => $pushnewsSite['configuration']['subscription_request']['body'],
					'subscription_request.btn_yes'                 => $pushnewsSite['configuration']['subscription_request']['btn_yes'],
					'subscription_request.btn_no'                  => $pushnewsSite['configuration']['subscription_request']['btn_no'],
					'confirmation_popup.message'                   => $pushnewsSite['configuration']['confirmation_popup']['message'],
					'confirmation_popup.sample_notification_title' => $pushnewsSite['configuration']['confirmation_popup']['title'],
					'confirmation_popup.sample_notification_body'  => $pushnewsSite['configuration']['confirmation_popup']['body'],
					'confirmation_popup.caption'                   => $pushnewsSite['configuration']['confirmation_popup']['caption'],
				);
			} else {
				$options = array(
					'active'                                       => 'false',
					'website_name'                                 => get_option( 'blogname' ),
					'website_square_logo_url'                      => 'https://ilabs-static.s3.amazonaws.com/push/icon128_pushnews.jpg',
					'app_id'                                       => '0000-0000-0000-0000',
					'pushnews_subdomain'                           => 'example.pushnews.eu',
					'subscription_request.title'                   => __( "Get our latest news!", "pushnews" ),
					'subscription_request.body'                    => __( "Subscribe to our latest news via push notifications.", "pushnews" ),
					'subscription_request.btn_yes'                 => __( "Subscribe!", "pushnews" ),
					'subscription_request.btn_no'                  => __( "Not interested", "pushnews" ),
					'confirmation_popup.message'                   => __( "wants to send notifications:", "pushnews" ),
					'confirmation_popup.sample_notification_title' => __( "Sample notification", "pushnews" ),
					'confirmation_popup.sample_notification_body'  => __( "Will appear on your phone/desktop", "pushnews" ),
					'confirmation_popup.caption'                   => __( "(You can disable them at any time)", "pushnews" ),
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
				'website_name'            => __( "Website name", "pushnews" ),
				'website_square_logo_url' => __( "Square logo (url)", "pushnews" ),
				'app_id'                  => __( "App ID", "pushnews" ),
				'pushnews_subdomain'      => __( "Pushnews subdomain", "pushnews" ),

			),
			'translations_subscription_request' => array(
				'subscription_request.title'   => __( "Title", "pushnews" ),
				'subscription_request.body'    => __( "Body", "pushnews" ),
				'subscription_request.btn_yes' => __( "Button Yes", "pushnews" ),
				'subscription_request.btn_no'  => __( "Button No", "pushnews" ),
			),
			'translations_confirmation_popup'   => array(
				'confirmation_popup.message'                   => __( "Message", "pushnews" ),
				'confirmation_popup.sample_notification_title' => __( "Sample notification title", "pushnews" ),
				'confirmation_popup.sample_notification_body'  => __( "Sample notification body", "pushnews" ),
				'confirmation_popup.caption'                   => __( "Caption", "pushnews" ),
			),
		);

		foreach ( $arr as $section_name => $section_items ) {

			if ( 'basic' == $section_name ) {
				$translation = __( "Basic", "pushnews" );
			} elseif ( 'translations_subscription_request' == $section_name ) {
				$translation = __( "Subscription Request", "pushnews" );
			} elseif ( 'translations_confirmation_popup' == $section_name ) {
				$translation = __( "Confirmation Popup", "pushnews" );
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
					[
						'label_for' => $k,
						'class'     => 'pushnews_row',
					]
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
			'{%%cloudfront_domain%%}'                          => self::CDN_DOMAIN,
			'{%%subscription_request.activation_type%%}'       => 'auto',
			'{%%subscription_request.activation_type.extra%%}' => '',

			'{%%optin.desktopImage%%}'           => $options['website_square_logo_url'],
			'{%%optin.mobileImage%%}'            => $options['website_square_logo_url'],
			'{%%subscription_request.title%%}'   => $options['subscription_request.title'],
			'{%%subscription_request.body%%}'    => $options['subscription_request.body'],
			'{%%subscription_request.btn_no%%}'  => $options['subscription_request.btn_no'],
			'{%%subscription_request.btn_yes%%}' => $options['subscription_request.btn_yes'],

			'{%%popup.name%%}'                 => $options['website_name'],
			'{%%popup.domain%%}'               => $options['pushnews_subdomain'],
			'{%%popup.appId%%}'                => $options['app_id'],
			'{%%confirmation_popup.message%%}' => $options['confirmation_popup.message'],
			'{%%popup.notificationIcon%%}'     => $options['website_square_logo_url'],
			'{%%confirmation_popup.title%%}'   => $options['confirmation_popup.sample_notification_title'],
			'{%%confirmation_popup.body%%}'    => $options['confirmation_popup.sample_notification_body'],
			'{%%confirmation_popup.caption%%}' => $options['confirmation_popup.caption'],
		);

		echo str_replace( array_keys( $replaces ), $replaces, $html );
	}

}