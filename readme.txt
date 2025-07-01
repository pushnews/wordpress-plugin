=== Pushnews ===
Contributors: impactingdigital, tixastronauta, mariobalca, pnkaki, pushnewslda
Donate link: https://pushnews.eu/
Tags: pushnews, push notifications, web push, notifications, push
Requires at least: 3.8
Tested up to: 6.8.1
Requires PHP: 5.3
Stable tag: 3.13.0
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

In your WordPress dashboard, go to the Pushnews menu and click "Send Push". Then, login with your Pushnews credentials.

== Upgrade Notice ==

= 3.13.0 =
This is a routine maintenance release to improve stability and performance.

== Screenshots ==

1. Web Push Notifications
2. Easy to setup
3. Customizable opt-in experience
4. No HTTPS website is required

== Changelog ==

= 3.13.0 =
* Avoiding PHP warnings when checking for Pushnews related POST parameters
* Tested with the latest WordPress version
* Code cleanup

= 3.12.0 =
* Changed Pushnews tag URI
* Sending headers to the API

= 3.11.0 =
* New logo
* Tested with latest Wordpress version

= 3.10.1 =
* Config checkboxes should maintain their state when saving post as a draft

= 3.10.0 =
* Added "Don't replace previous push" checkbox on create/update post so push won't replace previous one

= 3.9.0 =
* Added "allow duplicate Push" checkbox on create/update post so user can control whether to have a duplicate push or not

= 3.8.0 =
* Added "ignoreWarningSameHashPush" option to the payload to allow for duplicate pushes.

= 3.7.0 =
* Added a log file to help with debug

= 3.6.3 =
* Fixed sending push on scheduled posts

= 3.6.2 =
* Changed API domain to a temporary one (previous one was still not working)

= 3.6.1 =
* Changed API domain to a temporary one

= 3.6.0 =
* Always using https protocol on the javascript tag

= 3.5.0 =
* Tested OK up to Wordpress 6.0

= 3.4.0 =
* Added two activation toggles: tag inject (frontend) and metabox inject (backend)
* Improved translations
* Minor code improvements

= 3.3.1 =
* Fixed PHP Notice on undefined index

= 3.3.0 =
* Updated Wordpress plugin directory image assets

= 3.2.1 =
* Fixed support links

= 3.2.0 =
* Updated ServiceWorker URL
* Tested OK up to Wordpress 5.8.1

= 3.1.5 =
* Tested OK up to Wordpress 5.8
* Added alternative string trimming with "substr" instead of "mb_strimwidth" for installations without "mbstring" PHP module

= 3.1.4 =
* Tested OK up to Wordpress 5.7.1

= 3.1.3 =
* Stripping shortcodes from post body while converting it to Push

= 3.1.2 =
* Added a fallback function for determine_locale since it is only available on wordpress 5.0.0

= 3.1.1 =
* Fixed a deprecation warning

= 3.1.0 =
* Ensured 100% compatibility with Wordpress 5.4

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
