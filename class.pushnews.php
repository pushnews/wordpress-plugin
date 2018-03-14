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
	const VERSION = '1.5.0';
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
					'auth_token'                                   => $pushnewsSite['auth_token'],
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

	public static function custom_meta_box_markup() {
        require_once( plugin_dir_path( __FILE__ ) . '/views/metabox.php' );
	}

	function save_post_custom_hook($post_id, $post, $update)
	{
		$sendNotification 	= $_POST['pushnews_send_notification'];
		$sendEmail       	= $_POST['pushnews_send_email'];
		$options 	        = get_option( 'pushnews_options' );

		if(!$update && isset($options['auth_token']) && $options['auth_token'] != "") {
			$notification = array(
				"message" => array(
					"title" => get_the_title($post),
					"body" => substr(get_post_field('post_content', $post_id), 0, 400) . ' ...' ,
					"url" => get_permalink($post),
				)
			);

			if($sendNotification) {
				if(get_the_post_thumbnail_url($post)) {
					$notification['message']['icon'] = get_the_post_thumbnail_url($post);
				}

				wp_remote_post("https://api.pushnews.eu/v2/push/" . $options['app_id'], array(
		            "body" => json_encode($notification),
		            "headers" => array(
			            'X-Auth-Token' => $options['auth_token'],
			            "Content-Type" => "application/json"
		            )
	            ));
            }
			if($sendEmail) {
				if(get_the_post_thumbnail_url($post)) {
					$notification['message']['image'] = get_the_post_thumbnail_url($post);
				}

				wp_remote_post("https://api.pushnews.eu/v2/mail/" . $options['app_id'], array(
					"body" => json_encode($notification['message']),
					"headers" => array(
						'X-Auth-Token' => $options['auth_token'],
						"Content-Type" => "application/json"
					)
				));
			}
		}
	}

	public static function add_custom_meta_box()
	{
	    add_meta_box(
	    	"pushnews-meta-box", 
	    	"Pushnews", 
	    	array( __CLASS__, "custom_meta_box_markup" ), 
	    	"post", 
	    	"side", 
	    	"high", 
	    	null
	    );
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
				'auth_token'			  => __( "Auth token", "pushnews" ),
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
							'app_id' => array(
								__( "To find your app id click", "pushnews" ),
								__( "here", "pushnews" )
                            ),
							'auth_token' => array(
								__( "To find your auth token click", "pushnews" ),
								__( "here", "pushnews" )
                            ),
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

		if($args['label_for'] == "app_id" && $supplimental = $args['supplemental']['app_id']){
	        printf( '<p class="description">%s <a href="https://ajuda.pushnews.com.br/integracao-e-configuracao/como-saber-qual-o-seu-app-id" target="_blank">%s</a></p>', $supplimental[0], $supplimental[1]);
	    } elseif($args['label_for'] == "auth_token" && $supplimental = $args['supplemental']['auth_token']) {
	    	printf( '<p class="description">%s <a href=" http://ajuda.pushnews.com.br/integracao-e-configuracao/como-saber-qual-o-seu-token-de-autorizacao" target="_blank">%s</a></p>', $supplimental[0], $supplimental[1]);
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