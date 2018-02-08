=== tinyRelated ===
Contributors: ideag
Tags: related posts, widget, shortcode, related
Requires at least: 3.9.0
Tested up to: 4.9
Stable tag: 1.0.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: http://kava.tribuna.lt/en/

A tiny and simple plugin to manually assign and display related posts.

== Description ==

A tiny and simple hackable plugin to **manually** assign and display related posts. Incudes a widget, a shortcode (`[tinyrelated]`), a couple of template tags (`the_related_posts()/get_related_posts()`) for easy integration.

You can pick up to 10 related posts via a meta box in `Edit Post` window. If no posts were selected, there is an option to display random posts instead. *Currently, there is no automatic generation of related posts*.

The plugin is translation ready and has Lithuanian translation.

Also try out my other plugins:

* [Gust](http://tiny.lt/gust) - a Ghost-like admin panel for WordPress, featuring Markdown based split-view editor.
* [tinyCoffee](http://tiny.lt/tinycoffee) - a PayPal donations button with a twist. Ask people to treat you to a coffee/beer/etc. 
* [tinyTOC](http://tiny.lt/tinytoc) - automatic Table of Contents, based on H1-H6 headings in post content.
* [tinyIP](http://tiny.lt/tinyip) - *Premium* - stop WordPress users from sharing login information, force users to be logged in only from one device at a time.

An enormous amount of coffee was consumed while developing these plugins, so if you like what you get, please consider treating me to a [cup](http://kava.tribuna.lt/en/). Or two. Or ten.

== Installation ==

1. Install via `WP Admin > Plugins > Add New` or download a .zip file and upload via FTP to `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. (optional) Modify options via `WP Admin > Settings > tinyRelated`, if needed. Plugin automatically embeds related posts list to the end of post content, but you can use a widget, a shortcode (`[tinyrelated]`), a couple of template tags (`the_related_posts()/get_related_posts()`) for other integration options.

== Frequently Asked Questions ==

= Does it pick related posts automatically =

Not at the moment. If no posts are picked manually, it will show **random** posts by default (can be turned off in Settings).

== Screenshots ==

1. Related Posts picker metabox in Post editor.
2. Settings screen.

== Changelog ==

= 1.0.0 =
* Initial submission to wordpress.org
