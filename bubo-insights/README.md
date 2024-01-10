=== Bubo Insights ===
Contributors: pizza2mozzarella
Donate link: https://github.com/pizza2mozzarella/bubo_insights
Tags: analytics, statistics, stats, visits, tracking
Requires at least: 6.0.0
Tested up to: 6.4.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bubo Insights tracks and display the most useful user navigation data without using cookies or violating privacy. Simple, useful, effective.

== Description ==

Bubo Insights tracks and display the most useful user navigation data without using cookies or violating privacy. Simple, useful, effective.

It uses AJAX (with jQuery script) to track user information and anonimously fingerprint it. The jQuery script scrapes the otherwise impossibile to get information as the element clicked, the touch capability, screen size etc.. while everything else is collected via the AJAX call headers for the maximum respect of the privacy of the user. IP address is used only to hash the user and then discarded. Does not set or use cookies or session stored data.

Data is collected in an efficient way to keep the database small yet rich of information. The simple yet flexible interface of the plugin permits the execution of deep analytics of the traffic with customizable filters, userbase and timespans.

The plugin is still in it's early stage of realease and will soon add many new innovative ways to analyze the website traffic.

== Installation ==

1. Upload `bubo-insights.zip` to the `/wp-content/plugins/` directory and unzip it there.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Purge the cache to start collecting data.
4. Enjoy the insights.

== Frequently Asked Questions ==

= Why Bubo? =

Bubo is the latin name for the owl, an animal that examines the enviroment and its prey while remaining in the dark. It uses its intelligence to detect its prey and strike it in one successful strike. It's the totem animal of the analytics software. Yes... and all the other animal names were already taken! ...and Bubo sounds really cool so why not? ;)

= Why did you make a plugin for website analytics, don't you see there are dozens of them? =

Yes there are dozens of them but no one had the characteristics I needed such as a zero cookie approach to be GDRP compliant, an easy to use interface and strategic data collection such as external click tracking... In the end I just coded it by myself and I was so satisfied that I decided to release it to the public. Enjoy my work.

== Changelog ==

= 1.0 =
* Plugin released to the public.
* Added jQuery script to collect user data via AJAX call.
* Added the interface to see the statistics.

== Upgrade Notice ==

= 1.0 =
* Plugin released to the public. Discard all the alphas.