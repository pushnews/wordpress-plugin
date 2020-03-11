=== Pushnews ===
Contributors: impactingdigital, tixastronauta, mariobalca
Donate link: https://www.pushnews.eu/
Tags: push, push notifications, web push, desktop notification, notifications, pushnews, onesignal, sendpulse, pushcrew, vwo, pushengage, pushwoosh, aimtell
Requires at least: 3.8
Tested up to: 5.4
Requires PHP: 5.3
Stable tag: 3.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Increase your website traffic with Pushnews Web Push Notifications.

== Description ==

Pushnews is a web push notifications provider. With push notifications you can increase your website traffic by bringing your users back. Pushnews has a simple and intuitive interface - no development experience is required!

Features:

- Sending web push notifications
- Customizable opt-in widget
- Schedule push notifications
- Weekly recurring notifications planner
- Push OnSite - Push notifications directly on your website without the need for user opt-in
- Push Mail - Capture your visitors email addresses and send them beautiful emails
- Statistics
- Regional segmentation
- Advanced segmentation
- Device Target
- Custom Templates

== Installation ==

1. Create an account at Pushnews
2. Install the Pushnews plugin from the WordPress.org plugin directory or by uploading the Pushnews plugin folder to your wp-content/plugins directory.
3. Activate the plugin and set the desired configurations.

== Frequently Asked Questions ==

= Where do I create a Pushnews account? =

You can create a Pushnews account at [https://app.pushnews.eu/register](https://app.pushnews.eu/register?utm_source=WpPluginSite&utm_medium=wordpress)

= How do I send a push notification? =

In your wordpress dashboard, go to the Pushnews menu and click "Send Push". Then, login with your Pushnews credentials.

== Upgrade Notice ==

= 1.0.0 =
First plugin release

== Screenshots ==

1. Web Push Notifications
2. Easy to setup
3. Customizable opt-in experience
4. No HTTPS website is required

== Changelog ==

= 3.0.0 =
* Changed Service Worker importScripts URL

= 2.1.0 =
* Updated technical domain from "pushnews.eu" to "pn.vg"

= 2.0.1 =
* Bugfix: Was sending recovery push always after a minute

= 2.0.0 =
* Added WooCommerce integration allowing for abandoned cart recovery Push Notification
* Redesigned admin interface

= 1.10.2 =
* Removing &htmlentities; before calling push send api

= 1.10.1 =
* Translations were not working

= 1.10.0 =
* Added "max push title characters" and "max push body characters" to advanced configuration

= 1.9.0 =
* Now we are trimming Push title (max 50 chars), and body (max 145 chars)

= 1.8.1 =
* Saving "send push"/"send email" checks on custom post drafts

= 1.8.0 =
* Preventing Push/Email send while saving a draft post
* Added support for custom post types

= 1.7.3 =
* Fixed date comparison (now using GMT)

= 1.7.2 =
* Fixed bad production API endpoint

= 1.7.1 =
* Fixed sending Push after editing a Post

= 1.7.0 =
* Added support for Wordpress 5
* Updated API url
* Allowing Push/Email sending after editing a Post
* Enhanced Push/Email sending for scheduled Posts
* Editing a scheduled Post will now show if it has scheduled Push/Email
* Added Service Worker under "/wp-content/plugins/pushnews/sdk/pushnews-sw.js.php" (preparing for future native Widget Support)
* Added plugin version to tag

= 1.6.0 =
* No longer pre-checking "Send Push" and "Send Pushmail" checkboxes

= 1.5.4 =
* Updated Push Notifications plugin behaviour, now sending featured image as SuperPush

= 1.5.3 =
* Fixed some issues regarding updated posts

= 1.5.2 =
* Fixed some issues regarding scheduled posts

= 1.5.1 =
* Fixed some issues regarding the automatic push notifications

= 1.5.0 =
* Added send automatic push mail feature on new post publish

= 1.4.0 =
* Added send automatic push notification feature on new post publish

= 1.3.0 =
* Simplified tag installation so it only requires the App ID

= 1.2.0 =
* Making sure all tag configuration elements are trimmed before printed

= 1.1.0 =
* Removed short array syntax to allow compatibility with PHP 5.3
* Updated API domain

= 1.0.0 =
* First plugin release
