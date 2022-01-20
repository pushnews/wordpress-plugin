<?php

/**
 * Plugin Name:        Pushnews
 * Author:             Pushnews <developers@pushnews.eu>
 * Plugin URI:         https://www.pushnews.eu/
 * Description:        Increase your website traffic with Pushnews Web Push Notifications.
 * Version:            3.4.0
 * Author URI:         https://www.pushnews.eu/
 * License:            GPLv2 or later
 * Text Domain:        pushnews
 * Domain Path:        /languages
 **/

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

define( 'PUSHNEWS_VERSION', '3.4.0' );
define( 'PUSHNEWS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( PUSHNEWS__PLUGIN_DIR . 'class.pushnews.php' );
require_once( PUSHNEWS__PLUGIN_DIR . 'class.pushnewsbase64url.php' );

// pushnews admin styles
add_action( 'admin_enqueue_scripts', array( 'Pushnews', 'admin_styles' ) );

// initialize plugin
add_action( 'init', array( 'Pushnews', 'init' ) );
add_action( 'plugins_loaded', array( 'Pushnews', 'translations_init' ) );
add_action( 'admin_notices', array( 'Pushnews', 'display_admin_notices' ));

// woo-commerce integration
add_action( 'woocommerce_add_to_cart', array( 'Pushnews', 'woocommerce_add_to_cart' ) );
add_action( 'woocommerce_thankyou', array( 'Pushnews', 'woocommerce_thankyou' ) );

// plugin installation events
register_activation_hook( __FILE__, array( 'Pushnews', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Pushnews', 'plugin_deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Pushnews', 'plugin_uninstall' ) );

// inject pushnews js tag
add_action( 'wp_footer', array( 'Pushnews', 'inject_tag' ) );

// send push on post publish
add_action( 'add_meta_boxes', array( 'Pushnews', 'add_custom_meta_box' ) );
add_action( 'future_post', array( 'Pushnews', 'future_post_custom_hook' ), 10, 1 );
add_action( 'save_post', array( 'Pushnews', 'save_post_custom_hook' ), 10, 3 );
