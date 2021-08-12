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
	const RESOURCES_VERSION = '2020072001';
	const API_URL = 'https://api.pushnews.eu';
	const CDN_DOMAIN = 'cdn.pn.vg';

	const TAG = <<<MYHTML
<script src="//{%%cdn_domain%%}/sites/{%%app_id%%}.js" data-pn-plugin-url="{%%plugin_url%%}" data-pn-wp-plugin-version="{%%version%%}" type="text/javascript" async></script>
MYHTML;

	const OPTION_NAME_MAX_CHARS_PUSH_TITLE = 'maxchars_push_title';
	const OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE = 50;

	const OPTION_NAME_MAX_CHARS_PUSH_BODY = 'maxchars_push_body';
	const OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY = 145;

	const OPTION_NAME_WOO_COMMERCE_HOURS = 'hours_woo_commerce';
	const OPTION_DEFAULT_VALUE_WOO_COMMERCE_HOURS = 24;

	const OPTION_NAME_WOO_COMMERCE_ACTIVE = 'active_woo_commerce';
	const OPTION_DEFAULT_VALUE_WOO_COMMERCE_ACTIVE = false;

	const OPTION_NAME_WOO_COMMERCE_TITLE = 'title_woo_commerce';
	const OPTION_DEFAULT_VALUE_WOO_COMMERCE_TITLE = '';

	const OPTION_NAME_WOO_COMMERCE_BODY = 'body_woo_commerce';
	const OPTION_DEFAULT_VALUE_WOO_COMMERCE_BODY = '';

	const SESSION_KEY_ECOMMERCE_CHECKOUT = 'pushnews:ecommerce.checkoutCompleted';
	const SESSION_KEY_ECOMMERCE_PRODUCT_ADDED = 'pushnews:ecommerce.itemAddedToCart';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings_init' ) );
	}

	public static function translations_init() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = apply_filters( 'plugin_locale', determine_locale(), 'pushnews' );
		} else {
			$locale = apply_filters( 'plugin_locale', get_user_locale(), 'pushnews' );
		}
		$mofile = WP_PLUGIN_DIR . '/pushnews/languages/' . $locale . '.mo';

		load_textdomain( 'pushnews', $mofile );
	}

	public static function plugin_activation() {
		self::translations_init();

		$siteUrl          = get_option( 'siteurl' );
		$siteUrl64Encoded = PushnewsBase64Url::encode( $siteUrl );

		$endpoint     = self::API_URL . "/v1/sites/{$siteUrl64Encoded}?filterBy=base64_url";
		$response     = wp_remote_get( $endpoint, array( 'headers' => array( 'X-Pushnews-Wp-Version' => PUSHNEWS_VERSION ) ) );
		$pushnewsSite = wp_remote_retrieve_body( $response );
		$pushnewsSite = json_decode( $pushnewsSite, true );
		$options      = array(
			self::OPTION_NAME_MAX_CHARS_PUSH_TITLE => self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE,
			self::OPTION_NAME_MAX_CHARS_PUSH_BODY  => self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY,
			self::OPTION_NAME_WOO_COMMERCE_ACTIVE  => self::OPTION_DEFAULT_VALUE_WOO_COMMERCE_ACTIVE,
			self::OPTION_NAME_WOO_COMMERCE_HOURS   => self::OPTION_DEFAULT_VALUE_WOO_COMMERCE_HOURS,
			self::OPTION_NAME_WOO_COMMERCE_TITLE   => self::OPTION_DEFAULT_VALUE_WOO_COMMERCE_TITLE,
			self::OPTION_NAME_WOO_COMMERCE_BODY    => self::OPTION_DEFAULT_VALUE_WOO_COMMERCE_BODY
		);
		if ( JSON_ERROR_NONE == json_last_error() && isset( $pushnewsSite['success'] ) && true == $pushnewsSite['success'] ) {
			$pushnewsSite          = $pushnewsSite['data'];
			$options['active']     = 'true';
			$options['app_id']     = $pushnewsSite['app_id'];
			$options['auth_token'] = $pushnewsSite['auth_token'];
		} else {
			$options['active'] = 'false';
		}

		add_option( 'pushnews_options', $options );
	}

	public static function plugin_deactivation() {
	}

	public static function custom_meta_box_markup() {
		require_once( plugin_dir_path( __FILE__ ) . '/views/metabox.php' );
	}

	public static function future_post_custom_hook( $post_id ) {
		$sendNotification = $_POST['pushnews_send_notification'];
		$sendEmail        = $_POST['pushnews_send_email'];

		if ( $sendNotification ) {
			update_post_meta(
				$post_id,
				'sendNotification',
				$sendNotification
			);
		} else {
			delete_post_meta( $post_id, 'sendNotification' );
		}

		if ( $sendEmail ) {
			update_post_meta(
				$post_id,
				'sendEmail',
				$sendEmail
			);
		} else {
			delete_post_meta( $post_id, 'sendEmail' );
		}
	}

	/**
	 *
	 * @param int $post_id The post ID.
	 * @param WP_Post|array $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	public static function save_post_custom_hook( $post_id, $post, $update ) {
		$sendNotification = filter_var( $_POST['pushnews_send_notification'] || get_post_meta( $post_id, 'sendNotification' ), FILTER_VALIDATE_BOOLEAN );
		$sendEmail        = filter_var( $_POST['pushnews_send_email'] || get_post_meta( $post_id, 'sendEmail' ), FILTER_VALIDATE_BOOLEAN );
		$options          = get_option( 'pushnews_options' );
		$now              = current_time( "mysql", 1 );
		$postDate         = $post->post_date_gmt;

		if ( ! isset( $options['auth_token'] ) || "" == $options['auth_token'] ) {
			// token not set, abort
			return;
		}

		switch ( $post->post_status ) {
			case "publish":
				if ( $postDate <= $now /* it's not a future post */ ) {
					$body = self::buildNotificationBodyFromPost( $post );

					if ( true === $sendNotification ) {
						self::sendNotification( $options['app_id'], $options['auth_token'], $body );
						delete_post_meta( $post_id, "sendNotification" );
					}
					if ( true === $sendEmail ) {
						if ( get_the_post_thumbnail_url( $post ) ) {
							$body['message']['image'] = get_the_post_thumbnail_url( $post );
						}
						self::sendEmail( $options['app_id'], $options['auth_token'], $body['message'] );
						delete_post_meta( $post_id, "sendEmail" );
					}
				}
				break;
			case "draft":
			case "future":
				// since post is still a draft, let's check if user has selected "send push" or "send email" checkboxes
				self::future_post_custom_hook( $post->ID );
				break;
		}

	}

	/**
	 * Build notification object to be sent to Pushnews API
	 *
	 * @param WP_Post| $post
	 *
	 * @return array
	 */
	private static function buildNotificationBodyFromPost( $post ) {
		// get options
		$options                     = get_option( 'pushnews_options' );
		$option_max_chars_push_title = isset( $options[ self::OPTION_NAME_MAX_CHARS_PUSH_TITLE ] ) ? (int) $options[ self::OPTION_NAME_MAX_CHARS_PUSH_TITLE ] : self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE;
		$option_max_chars_push_body  = isset( $options[ self::OPTION_NAME_MAX_CHARS_PUSH_BODY ] ) ? (int) $options[ self::OPTION_NAME_MAX_CHARS_PUSH_BODY ] : self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY;
		if ( 0 === $option_max_chars_push_title ) {
			$option_max_chars_push_title = self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE;
		}
		if ( 0 === $option_max_chars_push_body ) {
			$option_max_chars_push_body = self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY;
		}

		// prepare fields
		$title    = strip_shortcodes( html_entity_decode( strip_shortcodes( strip_tags( get_the_title( $post ) ) ) ) );
		$body     = strip_shortcodes( html_entity_decode( strip_shortcodes( strip_tags( get_post_field( 'post_content', $post->ID ) ) ) ) );
		$url      = get_permalink( $post );
		$bigImage = get_the_post_thumbnail_url( $post );

		// trim long title or body
		if ( function_exists( 'mb_strimwidth' ) ) {
			$title = mb_strimwidth( $title, 0, $option_max_chars_push_title, '...' );
			$body  = mb_strimwidth( $body, 0, $option_max_chars_push_body, '...' );
		} else {
			$title = substr( $title, 0, $option_max_chars_push_title );
			$body  = substr( $title, 0, $option_max_chars_push_body );
		}

		// build the message
		$message = array(
			"title" => $title,
			"body"  => $body,
			"url"   => $url,
		);
		if ( $bigImage ) {
			$message['bigImage'] = $bigImage;
		}

		// return the Notification Body
		return array(
			"message" => $message,
		);
	}

	/**
	 * Call Pushnews API: send push notification
	 *
	 * @param $appId
	 * @param $authToken
	 * @param $body
	 */
	private static function sendNotification( $appId, $authToken, $body ) {
		wp_remote_post( self::API_URL . "/v2/push/" . $appId, array(
			"body"    => json_encode( $body ),
			"headers" => array(
				'X-Auth-Token' => $authToken,
				"Content-Type" => "application/json",
			),
		) );
	}

	/**
	 * Call Pushnews API: send email
	 *
	 * @param $appId
	 * @param $authToken
	 * @param $message
	 */
	private static function sendEmail( $appId, $authToken, $message ) {
		wp_remote_post( self::API_URL . "/v2/mail/" . $appId, array(
			"body"    => json_encode( $message ),
			"headers" => array(
				'X-Auth-Token' => $authToken,
				"Content-Type" => "application/json",
			),
		) );
	}

	public static function add_custom_meta_box() {
		// add pushnews meta box to "post"
		add_meta_box(
			"pushnews-meta-box",
			"Pushnews",
			array( __CLASS__, "custom_meta_box_markup" ),
			"post",
			"side",
			"high",
			null
		);

		// also add pushnews meta box on all other post types that are public but not built in to WordPress
		$args       = array(
			'public'   => true,
			'_builtin' => false
		);
		$output     = 'names';
		$operator   = 'and';
		$post_types = get_post_types( $args, $output, $operator );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				"pushnews-meta-box",
				"Pushnews",
				array( __CLASS__, "custom_meta_box_markup" ),
				$post_type,
				"side",
				"high",
				null
			);
		}
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

		$woo_commerce_section = array(
			'_name'                               => __( "WooCommerce - Abandoned cart recovery Push Notification", "pushnews" ),
			'_callback'                           => function () {
				echo __( "<p>Here you can setup up a Push Notification to be sent to users who have added items to the shopping cart but did not finish the purchase within a certain time.</p><p class=\"description\">You can use the following product variables on Notification title and Notification content: <b>%name%</b> for product name, <b>%price%</b> for product price.</p>", "pushnews" );
			},
			self::OPTION_NAME_WOO_COMMERCE_ACTIVE => __( "Active", "pushnews" ),
			self::OPTION_NAME_WOO_COMMERCE_HOURS  => __( "Hours to wait before sending Notification", "pushnews" ),
			self::OPTION_NAME_WOO_COMMERCE_TITLE  => __( "Notification title", "pushnews" ),
			self::OPTION_NAME_WOO_COMMERCE_BODY   => __( "Notification content", "pushnews" )
		);
		if ( false === self::_isWooCommercePluginInstalled() ) {
			$woo_commerce_section = null;
		}

		$sections = array(
			'basic'    => array(
				'_name'      => __( "Configuration", "pushnews" ),
				'_callback'  => function () {
				},
				'active'     => __( "Active", "pushnews" ),
				'app_id'     => __( "App ID", "pushnews" ),
				'auth_token' => __( "Private token", "pushnews" ),
			),
			'advanced' => array(
				'_name'                                => __( "Notification properties", "pushnews" ),
				'_callback'                            => function () {
				},
				self::OPTION_NAME_MAX_CHARS_PUSH_TITLE => __( "Maximum title characters", "pushnews" ),
				self::OPTION_NAME_MAX_CHARS_PUSH_BODY  => __( "Maximum content characters", "pushnews" ),
			)
		);
		if ( ! is_null( $woo_commerce_section ) ) {
			$sections['woo_commerce'] = $woo_commerce_section;
		}

		foreach ( $sections as $section_name => $section_items ) {

			$translation = $section_items['_name'];
			unset( $section_items['_name'] );

			$section_callback = $section_items['_callback'];
			unset( $section_items['_callback'] );

			add_settings_section(
				$section_name,
				$translation,
				$section_callback,
				'pushnews'
			);

			foreach ( $section_items as $k => $translation ) {
				$id = "pushnews_field_{$k}";

				$callback_function = array( __CLASS__, 'input_cb' );
				if ( preg_match( "/^active/", $k ) ) {
					$callback_function = array( __CLASS__, 'checkbox_cb' );
				}

				add_settings_field(
					$id,
					$translation,
					$callback_function,
					'pushnews',
					$section_name,
					array(
						'label_for'    => $k,
						'class'        => 'pushnews_row',
						'input_type'   => array(
							self::OPTION_NAME_MAX_CHARS_PUSH_TITLE => 'number',
							self::OPTION_NAME_MAX_CHARS_PUSH_BODY  => 'number',
							self::OPTION_NAME_WOO_COMMERCE_HOURS   => 'number'
						),
						'supplemental' => array(
							'app_id'             => array(
								__( "To find your app id click", "pushnews" ),
								__( "here", "pushnews" ),
							),
							'auth_token'         => array(
								__( "To find your private token click", "pushnews" ),
								__( "here", "pushnews" ),
							),
							'hours_woo_commerce' => array(
								__( "If user finishes the purchase before this time has passed, Push Notification will be canceled.", "pushnews" ),
							)
						)
					)
				);

			}
		}
	}

	public static function input_cb( $args ) {
		$options      = get_option( 'pushnews_options' );
		$type         = isset( $args['input_type'][ $args['label_for'] ] ) ? $args['input_type'][ $args['label_for'] ] : 'text';
		$supplemental = isset( $args['supplemental'][ $args['label_for'] ] ) ? $args['supplemental'][ $args['label_for'] ] : null;
		?>
		<input
			type="<?= $type ?>"
			id="<?= esc_attr( $args['label_for'] ); ?>"
			name="pushnews_options[<?= esc_attr( $args['label_for'] ); ?>]"
			value="<?= isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '' ?>"
		>
		<?php

		if ( $args['label_for'] == "app_id" ) {
			printf( '<p class="description">%s <a href="https://ajuda.pushnews.com.br/integracao-e-configuracao/como-saber-qual-o-seu-app-id" target="_blank">%s</a></p>', $supplemental[0], $supplemental[1] );
		} elseif ( $args['label_for'] == "auth_token" ) {
			printf( '<p class="description">%s <a href=" http://ajuda.pushnews.com.br/integracao-e-configuracao/como-saber-qual-o-seu-token-de-autorizacao" target="_blank">%s</a></p>', $supplemental[0], $supplemental[1] );
		} elseif ( ! is_null( $supplemental ) ) {
			printf( '<p class="description">%s</p>', $supplemental[0] );
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
			'{%%cdn_domain%%}' => self::CDN_DOMAIN,
			'{%%app_id%%}'     => trim( $options['app_id'] ),
			'{%%version%%}'    => PUSHNEWS_VERSION,
			'{%%plugin_url%%}' => plugin_dir_url( __FILE__ ),
		);

		echo str_replace( array_keys( $replaces ), $replaces, $html );

		if ( isset( $_SESSION[ self::SESSION_KEY_ECOMMERCE_PRODUCT_ADDED ] ) ) {
			$data = json_encode( $_SESSION[ self::SESSION_KEY_ECOMMERCE_PRODUCT_ADDED ] );
			unset( $_SESSION[ self::SESSION_KEY_ECOMMERCE_PRODUCT_ADDED ] );
			echo <<<HTML
<script>
var IlabsPush = IlabsPush || [];
IlabsPush.push(["ecommerce.itemAddedToCart", $data]);
</script>
HTML;

		}
		if ( isset( $_SESSION[ self::SESSION_KEY_ECOMMERCE_CHECKOUT ] ) ) {
			unset( $_SESSION[ self::SESSION_KEY_ECOMMERCE_CHECKOUT ] );
			echo <<<HTML
<script>
var IlabsPush = IlabsPush || [];
IlabsPush.push(["ecommerce.checkoutCompleted"]);
</script>
HTML;

		}
	}

	/**
	 * @param $my_cart_item_key
	 */
	public static function woocommerce_add_to_cart( $my_cart_item_key ) {
		global $woocommerce;

		if ( false === self::_isWooCommercePluginInstalled() || false == self::_isWooCommerceOptionActive() ) {
			return;
		}

		$options = get_option( 'pushnews_options' );

		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $product ) {
			$product = $product['data'];
			if ( ! $product instanceof WC_Abstract_Legacy_Product ) {
				continue;
			}
			if ( $my_cart_item_key === $cart_item_key ) {

				$notification = array();

				// context variables
				$context = array(
					'id'        => $product->get_id(),
					'name'      => $product->get_name(),
					'price'     => $product->get_price(),
					'permalink' => $product->get_permalink()
				);

				// default bigImage
				$attachment_image = wp_get_attachment_image_src( $product->get_image_id() );
				if ( is_array( $attachment_image ) && isset( $attachment_image[0] ) ) {
					$notification['bigImage'] = $attachment_image[0];
				}

				// default url
				$notification['url'] = wc_get_cart_url();

				$notification['title'] = $options[ self::OPTION_NAME_WOO_COMMERCE_TITLE ];
				$notification['body']  = $options[ self::OPTION_NAME_WOO_COMMERCE_BODY ];

				$data = array(
					"notification" => $notification,
					"delayMinutes" => $options[ self::OPTION_NAME_WOO_COMMERCE_HOURS ] * 60,
					"context"      => $context
				);

				$_SESSION[ self::SESSION_KEY_ECOMMERCE_PRODUCT_ADDED ] = $data;

				self::_debug( "enqueued notification: " . print_r( $_SESSION[ self::SESSION_KEY_ECOMMERCE_PRODUCT_ADDED ], true ) );

				return;
			}

		};
	}

	/**
	 * @param $order_id
	 */
	public static function woocommerce_thankyou( $order_id ) {

		if ( false === self::_isWooCommercePluginInstalled() || false == self::_isWooCommerceOptionActive() ) {
			return;
		}

		self::_debug( "woocommerce_thankyou: {$order_id}" );

		$_SESSION[ self::SESSION_KEY_ECOMMERCE_CHECKOUT ] = true;
	}

	/**
	 * Checks if WooCommerce is installed and active
	 *
	 * @return bool
	 */
	private static function _isWooCommercePluginInstalled() {

		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );

	}

	/**
	 * Checks of WooCommerce option is enabled
	 *
	 * @return bool
	 */
	private static function _isWooCommerceOptionActive() {
		$options = get_option( 'pushnews_options' );

		return isset( $options[ self::OPTION_NAME_WOO_COMMERCE_ACTIVE ] )
		       &&
		       true === filter_var( $options[ self::OPTION_NAME_WOO_COMMERCE_ACTIVE ], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Replaces {{variables}} in the $template
	 *
	 * @param $template
	 * @param $variables
	 *
	 * @return mixed
	 */
	public static function _replace_variables( $template, $variables ) {
		if ( preg_match_all( "/%(.*?)%/", $template, $m ) ) {
			foreach ( $m[1] as $i => $varname ) {
				$replace = '';
				if ( isset( $variables[ $varname ] ) ) {
					$replace = $variables[ $varname ];
				}
				$template = str_replace( $m[0][ $i ], $replace, $template );
			}
		}

		return $template;
	}

	/**
	 * Appends message to a log file
	 *
	 * @param string|null $msg
	 */
	private static function _debug( $msg = null ) {
	    return;
		$msg = '[' . date( 'Y-m-d H:i:s' ) . "] {$msg}\n";
		$h   = fopen( "/var/www/log", "a+" );
		fwrite( $h, $msg );
		fclose( $h );
	}

}
