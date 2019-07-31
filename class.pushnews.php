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

class Pushnews
{
	const VERSION = '1.10.1';
	const RESOURCES_VERSION = '1';
	const API_URL = 'https://api.pushnews.eu';
	const CDN_DOMAIN = 'cdn.pn.vg';

	const TAG = <<<MYHTML
<!-- Pushnews v{%%version%%} -->
<script src="//{%%cdn_domain%%}/sites/{%%app_id%%}.js" data-pn-plugin-url="{%%plugin_url%%}" data-pn-wp-plugin-version="{%%version%%}" async></script>
<!-- / Pushnews -->
MYHTML;

	const OPTION_NAME_MAX_CHARS_PUSH_TITLE = 'maxchars_push_title';
	const OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE = 50;

	const OPTION_NAME_MAX_CHARS_PUSH_BODY = 'maxchars_push_body';
	const OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY = 145;

	public static function init()
	{
		add_action('admin_menu', array(__CLASS__, 'add_admin_page'));
		add_action('admin_init', array(__CLASS__, 'settings_init'));
	}

	public static function translations_init()
	{
		load_plugin_textdomain('pushnews', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	public static function plugin_activation()
	{
		self::translations_init();

		$siteUrl = get_option('siteurl');
		$siteUrl64Encoded = PushnewsBase64Url::encode($siteUrl);

		$endpoint = self::API_URL . "/v1/sites/{$siteUrl64Encoded}?filterBy=base64_url";
		$response = wp_remote_get($endpoint, array('headers' => array('X-Pushnews-Wp-Version' => self::VERSION)));
		$pushnewsSite = wp_remote_retrieve_body($response);
		$pushnewsSite = json_decode($pushnewsSite, true);
		$options = array(
			self::OPTION_NAME_MAX_CHARS_PUSH_TITLE => self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE,
			self::OPTION_NAME_MAX_CHARS_PUSH_BODY => self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY,
		);
		if (JSON_ERROR_NONE == json_last_error() && isset($pushnewsSite['success']) && true == $pushnewsSite['success']) {
			$pushnewsSite = $pushnewsSite['data'];
			$options['active'] = 'true';
			$options['app_id'] = $pushnewsSite['app_id'];
			$options['auth_token'] = $pushnewsSite['auth_token'];
		} else {
			$options['active'] = 'false';
		}

		add_option('pushnews_options', $options);
	}

	public static function plugin_deactivation()
	{
	}

	public static function custom_meta_box_markup()
	{
		require_once(plugin_dir_path(__FILE__).'/views/metabox.php');
	}

	public function future_post_custom_hook($post_id)
	{
		$sendNotification = $_POST['pushnews_send_notification'];
		$sendEmail        = $_POST['pushnews_send_email'];

		if ($sendNotification) {
			update_post_meta(
				$post_id,
				'sendNotification',
				$sendNotification
			);
		} else {
			delete_post_meta($post_id, 'sendNotification');
		}

		if ($sendEmail) {
			update_post_meta(
				$post_id,
				'sendEmail',
				$sendEmail
			);
		} else {
			delete_post_meta($post_id, 'sendEmail');
		}
	}

	/**
	 *
	 * @param int           $post_id The post ID.
	 * @param WP_Post|array $post The post object.
	 * @param bool          $update Whether this is an existing post being updated or not.
	 */
	function save_post_custom_hook($post_id, $post, $update)
	{
		$sendNotification = filter_var($_POST['pushnews_send_notification'] || get_post_meta($post_id, 'sendNotification'), FILTER_VALIDATE_BOOLEAN);
		$sendEmail        = filter_var($_POST['pushnews_send_email'] || get_post_meta($post_id, 'sendEmail'), FILTER_VALIDATE_BOOLEAN);
		$options          = get_option('pushnews_options');
		$now              = current_time("mysql", 1);
		$postDate         = $post->post_date_gmt;

		if (!isset($options['auth_token']) || "" == $options['auth_token']) {
			// token not set, abort
			return;
		}

		switch ($post->post_status) {
			case "publish":
				if ($postDate <= $now /* it's not a future post */) {
					$body = self::buildNotificationBodyFromPost($post);

					if (true === $sendNotification) {
						self::sendNotification($options['app_id'], $options['auth_token'], $body);
						delete_post_meta($post_id, "sendNotification");
					}
					if (true === $sendEmail) {
						if (get_the_post_thumbnail_url($post)) {
							$body['message']['image'] = get_the_post_thumbnail_url($post);
						}
						self::sendEmail($options['app_id'], $options['auth_token'], $body['message']);
						delete_post_meta($post_id, "sendEmail");
					}
				}
				break;
			case "draft":
			case "future":
				// since post is still a draft, let's check if user has selected "send push" or "send email" checkboxes
				self::future_post_custom_hook($post->ID);
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
	private static function buildNotificationBodyFromPost($post)
	{
	    // get options
		$options = get_option('pushnews_options');
		$option_max_chars_push_title = isset($options[self::OPTION_NAME_MAX_CHARS_PUSH_TITLE]) ? (int)$options[self::OPTION_NAME_MAX_CHARS_PUSH_TITLE] : self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE;
		$option_max_chars_push_body = isset($options[self::OPTION_NAME_MAX_CHARS_PUSH_BODY]) ? (int)$options[self::OPTION_NAME_MAX_CHARS_PUSH_BODY] : self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY;
		if (0 === $option_max_chars_push_title) {
			$option_max_chars_push_title = self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_TITLE;
		}
		if (0 === $option_max_chars_push_body) {
			$option_max_chars_push_body = self::OPTION_DEFAULT_VALUE_MAX_CHARS_PUSH_BODY;
		}

		// prepare fields
		$title = strip_tags(get_the_title($post));
		$body = strip_tags(get_post_field('post_content', $post->ID));
		$url = get_permalink($post);
		$bigImage = get_the_post_thumbnail_url($post);

		// trim long title or body
		$title = mb_strimwidth($title, 0, $option_max_chars_push_title, '...');
		$body = mb_strimwidth($body, 0, $option_max_chars_push_body, '...');

		// build the message
		$message = array(
			"title" => $title,
			"body" => $body,
			"url" => $url,
		);
		if ($bigImage) {
			$message['bigImage'] = $bigImage;
		}

		// return the Notification Body
		$body = array(
			"message" => $message,
		);

		return $body;
	}

	/**
	 * Call Pushnews API: send push notification
	 *
	 * @param $appId
	 * @param $authToken
	 * @param $body
	 */
	private static function sendNotification($appId, $authToken, $body)
	{
		wp_remote_post(self::API_URL."/v2/push/".$appId, array(
			"body"    => json_encode($body),
			"headers" => array(
				'X-Auth-Token' => $authToken,
				"Content-Type" => "application/json",
			),
		));
	}

	/**
	 * Call Pushnews API: send email
	 *
	 * @param $appId
	 * @param $authToken
	 * @param $message
	 */
	private static function sendEmail($appId, $authToken, $message)
	{
		wp_remote_post(self::API_URL."/v2/mail/".$appId, array(
			"body"    => json_encode($message),
			"headers" => array(
				'X-Auth-Token' => $authToken,
				"Content-Type" => "application/json",
			),
		));
	}

	public static function add_custom_meta_box()
	{
		// add pushnews meta box to "post"
		add_meta_box(
			"pushnews-meta-box",
			"Pushnews",
			array(__CLASS__, "custom_meta_box_markup"),
			"post",
			"side",
			"high",
			null
		);

		// also add pushnews meta box on all other post types that are public but not built in to WordPress
		$args = array(
			'public' => true,
			'_builtin' => false
		);
		$output = 'names';
		$operator = 'and';
		$post_types = get_post_types($args, $output, $operator);
		foreach ($post_types as $post_type) {
			add_meta_box(
				"pushnews-meta-box",
				"Pushnews",
				array(__CLASS__, "custom_meta_box_markup"),
				$post_type,
				"side",
				"high",
				null
			);
		}
	}

	public static function plugin_uninstall()
	{
		delete_option('pushnews_options');
	}

	public static function add_admin_page()
	{
		add_menu_page(
			'pushnews',
			__('Pushnews', 'pushnews'),
			'manage_options',
			'pushnews',
			array(__CLASS__, 'admin_menu')
		);
	}

	public static function admin_menu()
	{
		require_once(plugin_dir_path(__FILE__).'/views/config.php');
	}

	public static function admin_styles()
	{
		wp_enqueue_style('pushnews-admin-styles', plugin_dir_url(__FILE__).'views/css/pushnews-admin-styles.css', false, self::RESOURCES_VERSION);
	}

	public static function settings_init()
	{
		register_setting("pushnews", "pushnews_options");

		$arr = array(
			'basic' => array(
				'active' => __("Active", "pushnews"),
				'app_id' => __("App ID", "pushnews"),
				'auth_token' => __("Auth token", "pushnews"),
			),
			'advanced' => array(
				self::OPTION_NAME_MAX_CHARS_PUSH_TITLE => __("Max Push Title Characters", "pushnews"),
				self::OPTION_NAME_MAX_CHARS_PUSH_BODY => __("Max Push Body Characters", "pushnews"),
			),
		);

		foreach ($arr as $section_name => $section_items) {

		    $translation = __(ucfirst($section_name));

			if ('basic' == $section_name) {
				$translation = __("Configuration", "pushnews");
			}

			add_settings_section(
				$section_name,
				$translation,
				function () {
				},
				'pushnews'
			);

			foreach ($section_items as $k => $translation) {
				$id = "pushnews_field_{$k}";

				$callback_function = array(__CLASS__, 'input_cb');
				if ('basic' == $section_name && 'active' == $k) {
					$callback_function = array(__CLASS__, 'checkbox_cb');
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
						'supplemental' => array(
							'app_id'     => array(
								__("To find your app id click", "pushnews"),
								__("here", "pushnews"),
							),
							'auth_token' => array(
								__("To find your auth token click", "pushnews"),
								__("here", "pushnews"),
							),
						),
					)
				);

			}
		}
	}

	public static function input_cb($args)
	{
		$options = get_option('pushnews_options');
		?>
        <input
                type="text"
                id="<?= esc_attr($args['label_for']); ?>"
                name="pushnews_options[<?= esc_attr($args['label_for']); ?>]"
                value="<?= isset($options[$args['label_for']]) ? $options[$args['label_for']] : '' ?>"
        >
		<?php

		if ($args['label_for'] == "app_id" && $supplimental = $args['supplemental']['app_id']) {
			printf('<p class="description">%s <a href="https://ajuda.pushnews.com.br/integracao-e-configuracao/como-saber-qual-o-seu-app-id" target="_blank">%s</a></p>', $supplimental[0], $supplimental[1]);
		} elseif ($args['label_for'] == "auth_token" && $supplimental = $args['supplemental']['auth_token']) {
			printf('<p class="description">%s <a href=" http://ajuda.pushnews.com.br/integracao-e-configuracao/como-saber-qual-o-seu-token-de-autorizacao" target="_blank">%s</a></p>', $supplimental[0], $supplimental[1]);
		}
	}

	public static function checkbox_cb($args)
	{
		$options = get_option('pushnews_options');
		$checked = isset($options[$args['label_for']]) && true == filter_var($options[$args['label_for']],
			FILTER_VALIDATE_BOOLEAN) ? true : false;
		?>
        <input
                type="checkbox"
                id="<?= esc_attr($args['label_for']); ?>"
                name="pushnews_options[<?= esc_attr($args['label_for']); ?>]"
                value="true"
			<?= $checked == true ? 'checked' : '' ?>
        >
		<?php
	}

	public static function inject_tag()
	{

		$options = get_option('pushnews_options');

		if ( ! isset($options['active']) || true != filter_var($options['active'], FILTER_VALIDATE_BOOLEAN)) {
			return;
		}

		$html = self::TAG;

		$replaces = array(
			'{%%cdn_domain%%}' => self::CDN_DOMAIN,
			'{%%app_id%%}'     => trim($options['app_id']),
			'{%%version%%}'    => self::VERSION,
			'{%%plugin_url%%}' => plugin_dir_url(__FILE__),
		);

		echo str_replace(array_keys($replaces), $replaces, $html);
	}

}