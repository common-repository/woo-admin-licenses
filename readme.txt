=== Woo Admin Licenses ===
Contributors: pwallner
Author URI: https://www.mcpat.com
Plugin URL: https://wordpress.org/plugins/woo-admin-licenses/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=p%2er%2ewallner%40gmail%2ecom&lc=AT&item_name=mcpat&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Requires at Least: 4.7
Tested Up To: 4.9
Tags: woocommerce, license, licence, lizenz, software, api, wpml, multilanguage, language, add-on
Stable tag: 1.2.2
WC requires at least: 3.0.0
WC tested up to: 3.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Woo Admin Licenses is a simple shortcode plugin that allows you to display the end-users licence keys in a table.


== Description ==

> <strong>Required to Work</strong><br>
> This plugin requires the use of [WooCommerce Software Add-on](https://woocommerce.com/products/software-add-on/)

Displays the end-users license keys in the user account and also if required in a table. For showing in a table, simply place a shortcode on a new page. 

Logged in users can access the license keys and deactivate any software they purchased.

With WooCommerce Software Add-on, each software product the customer has purchased, a licence key is given but only via email when the order is completed. You also don't have the option to deactivate your licence key. With this plugin, the customer see all licence keys purchased and also on which platform the licence has been activated.

The plugin is very fast, works together with WPML and can show the output in any language!

> **Software Add-On Hacks**  
> This plugin can override the software add-on (please report any error in the forum):
> 1.  Define variable products as software
> 2.  Define which software can be deactivated
> 3.  Define expiry days for software (can be checked with the API or at the customer account page)  
> 4.  Override of API:  
>     * Disallow software reset
>     * Disallow deactivation 
>     * Generate keys with expiry days  
>     * Software which cannot be deactivated, can't be deactivated via API anymore  

== Installation ==

Just install the plugin as normal and activate it. Nothing else required.


== Upgrade Notice ==

---


== Frequently Asked Questions ==

= Q: How do I display the license table? =

A.1: Simply place this shortcode anywhere you want.

`[woo_admin_licenses]`

A.2: Customer can view his licenses directly in his account

= Q: How can I display a products variation column? =

A: Simply display the shortcode like this:

`[woo_admin_licenses variables="yes"]`


== Screenshots ==

1. Account page with added submenu
2. Table with “variables”
3. Settings

== Changelog ==

= 1.2.2 = 
- Minor changes

= 1.2.1 = 
- Disabling of API deactivation added

= 1.2.0 = 
- Deactivation uses ajax call
- License table only visible for logged in users
- Minor changes

= 1.1.9 = 
- Show info if user activation email is different to account email

= 1.1.8 = 
- Bugfix switching between simple and variable products
- Minor changes

= 1.1.7 = 
- Bugfix simple products saving

= 1.1.6 = 
- Bugfix variable products saving

= 1.1.5 = 
- WooCommerce: Upwards compatible with the upcoming WooCommerce 3.2

= 1.1.4 = 
- Update for Software Add-on 1.7.4

= 1.1.3 = 
- Bugs fixed
- Added software expiry

= 1.1.2 = 
- Bug fixed at license table

= 1.1.1 = 
- Bugs fixed

= 1.1.0 = 
- Software add-on hack for variable products
- Minor changes