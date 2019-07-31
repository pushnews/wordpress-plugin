<?php

/**
* Plugin Name:        Pushnews
* Author:             Pushnews <developers@pushnews.eu>
* Plugin URI:         https://www.pushnews.eu/
* Description:        Send Web Push Notifications to your visitors. Increase your website traffic - Simple and fast UI - Automate push notifications via Facebook Page integration.
* Version:            1.10.1
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

define( 'PUSHNEWS_VERSION', '1.10.1' );
define( 'PUSHNEWS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( PUSHNEWS__PLUGIN_DIR . 'class.pushnews.php' );
require_once( PUSHNEWS__PLUGIN_DIR . 'class.pushnewsbase64url.php' );

add_action( 'admin_enqueue_scripts', array( 'Pushnews', 'admin_styles' ) );

add_action( 'init', array( 'Pushnews', 'init' ) );
add_action( 'plugins_loaded', array( 'Pushnews', 'translations_init' ) );

register_activation_hook( __FILE__, array( 'Pushnews', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Pushnews', 'plugin_deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Pushnews', 'plugin_uninstall' ) );

add_action( 'wp_footer', array( 'Pushnews', 'inject_tag' ) );

// Send push on post publish
add_action( 'add_meta_boxes', array( 'Pushnews',  'add_custom_meta_box' ));
add_action( 'future_post', array('Pushnews', 'future_post_custom_hook' ), 10, 1);
add_action( 'save_post', array('Pushnews', 'save_post_custom_hook' ), 10, 3 );