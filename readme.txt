=== WP Twitter Wall ===
Contributors: thierrypigot, willybahuaud
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5XZ4CPDQQU7ZC
Tags: twitter, event, wall, twitterwall, conference, hastag, social, photo, tweet
Requires at least: 3.5.0
Tested up to: 4.6.1
Stable tag: 1.3.1
Version: 1.3.1
Text Domain: wp-twitter-wall
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Display a live Twitter wall at your event, using your WordPress website!

== Description ==

With WP Twitter wall, show a live Twitter wall at your event! Install the plugin on your website, get a video projector, then display a wall of people's tweets reacting about your show.

This plugin was originally designed to display attendees's tweets during the WP Tech Nantes, and WordCamp Paris events.

The twitterwall will be updated twice a minute, adding new tweets and updating the publication times of the old ones.

Rules are defined to protect you against twitter automated spam, but if someone pass  these protection, you can manually exclude them.

Wall's colors can be customized using the plugin settings panel, adapting them to your brand.

Interactions between the plugin and Twitter API use the PHP Class [TokenToMe](https://github.com/TweetPressFr/TokenToMe), created and maintained by Julien Maury.

== Installation ==

1. Upload the plugin files to the plugin directory, or install the plugin through the WordPress plugins screen directly.
1. Activate WP Twitter Wall through the 'Plugins' screen in WordPress.
1. Use the WP Twitter Wall screen to define your [twitter API credentials](https://apps.twitter.com/).
1. On the same screen define your twitter wall relative URI, your search query and optionals parameters then save settings.

You can see your WP Twitter Wall on the defined URI, or use the shortcode `[twitter-wall/]` to display the feed inside your website.

If you encounter a 404 page when visitng twitterwall URI, please flush your permalinks through the 'Settings > Permalinks' screen.

== Frequently Asked Questions ==

= Which operators can be used in the search query? =

All available query operators are detailled [here](https://dev.twitter.com/rest/public/search).

More commons are:

* use simple words `exemple`.
* use hastags words `#exemple`.
* use negative asserts: `-something`.
* use `OR`. If you define multiple words, the search query will retrieve tweets containing all of them. `exemple OR another` will get tweets with one of theses words (or more).
* query accounts using `@`: `@someone` will get all tweets were the user named 'someone' is mentioned. `to:someone` will obtain all tweets sended to this user, and `from:someone` will get all tweets writed by him.

You're allowed to combine as many operators as you want in one string :)

= How to define a custom stylesheet? =

Paste these lines into your 'functions.php' theme file:
`add_action( 'wp_enqueue_scripts', 'custom_enqueue_script', 11 );
function custom_enqueue_script() {
    $style = get_stylesheet_directory_uri() . '/my-custom-twitterwall-style.css'; // this is an example
    wp_deregister_style( 'twitter-wall-css' );
    wp_register_style( 'twitter-wall-css', $style, false, '1', 'all' );
}`

= How to protect the wall against spams? =

If you use this plugin on an event, it is likely than some twitter accounts will try to spam your wall (especially if you appear in trending topics). WP Twitter Wall offer two ways to prevent that this happen:

1. WP Twitter Wall will not show tweets containing more than two hastags
2. Admin users can mark some accounts as 'spam' by clicking on the concerned username. His tweets will be removed and no other will appear.

== Screenshots ==

1. A twitter wall near the main screen, at WP Tech 2015 event.
2. The plugin on the computer used to project the twitter wall ; on this interface the admin can report some tweets as spam.
3. Reporting twitter user as spam (on live at WordCamp Paris 2016)
4. A twitter wall is a way to joke at the end of conference day ;-)

== Changelog ==

= 1.3.1 =
* Wording fix
* Add GlotPress compatibility

= 1.3 =
* Bug Fix : duplicate tweet on refresh

= 1.2 =
* Bug Fix : header fail on TokenToMe.class

= 1.1 =
* Security Fix

= 1.0 =

* 10 september 2016
* Initial release
