=== PlugIntelligence ===
Plugin Name:  PlugIntel
Contributors: charlestonsw
Donate link: http://www.charlestonsw.com/product/plugintel/
Tags: wordpress plugins, plugin finder, plugin helper, plugin installer, plugin filter, plugin search, filter, search, ratings, helper, plugin installer
Requires at least: 3.3
Tested up to: 3.9
Stable tag: 0.5

A plugin to help navigate the WordPress plugin directory by filtering the results based on ratings and other criteria.

== Description ==

After years of searching through thousands of WordPress plugins, trying to sort out the 5-star from the 1-star listings, those that were not tested on the most recent version of WordPress and other filters,  I decided it was time to make my plugin search a bit easier.    Since the WordPress plugin manager already has a way to retrieve both basic and detailed plugin information directly to my WordPress site admin panel, I figured there must be a way to do some more intelligent filtering of plugin data when doing my searches.

Turns out there is a way.    And this is my first stab at it.   If enough people are interested in the plugin I'll even add more advanced features like listing and filtering based on number of downloads, reviews, and more.

For now, here is the basic feature set I find useful in filtering out my lists.

Current plugin filtering options include:

* Minimum Rating : Do not show plugins with ratings below this value. 60 = 3 stars, 100 = 5 stars.
* Maximum Rating : Do not show plugins with ratings above this value. 60 = 3 stars, 100 = 5 stars.
* Minimum Number of Ratings :  Do not show plugins with fewer than this number of ratings. Default: 2.
* Minimum Tested Version : Do not show plugins that were not tested on this version of WordPress or higher. Default: 3.3.

= Features =

* Filter WordPress plugin listings.


= Related Links =

* [Other CSA Plugins](http://profiles.wordpress.org/charlestonsw/)
* [PlugIntelligence Issues List](https://bitbucket.org/lance_cleveland/plugintelligence/issues)

== Installation ==

Use the standard WordPress install process.

= Requirements =

* Wordpress: 3.3.2+
* PHP: 5.1+


== Frequently Asked Questions ==

= What are the terms of the license? =

The license is GPL.  You get the code, feel free to modify it as you
wish.  We prefer that our customers pay us because they like what we do and
want to support our efforts to bring useful software to market.  Learn more
on our [CSA License Terms](http://www.charlestonsw.com/products/general-eula/).

== Screenshots ==

The website offers [more screen shots](http://www.charlestonsw.com/product/plugintel/).

1. The Settings Page
2. Results With Default Settings
3. Results With Plugin Deactivated


== Changelog ==

Visit the [CSA Website for details](http://www.charlestonsw.com/).

= v0.5 =

* Added Spanish (es_ES) language files.

= v0.4 =

* Properly init options array from DB on fresh install.

= v0.3 =

* Turn on/off plugintel with a simple on/off switch.  You no longer need to activate/de-activate the plugin.

= v0.2 =

* Put header on search page so you know PlugIntel is running.  Needs to be interactive, yeah I know... its on the list.

= v0.1 =

* First stab.
