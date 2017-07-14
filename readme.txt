=== Woo Pacsoft Unifaun ===
Contributors: pierrewiberg, itsmikita
Donate link: 
Tags: ecommerce, woocommerce, shipping
Requires at least: 3.3
Tested up to: 4.7.3
Stable tag: 2.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Choose from over 200 transport services to ship your WooCommerce orders with, powered by Pacsoft/Unifaun.

== Description ==

Manage advanced logistics of your WooCommerce orders. You can set to book transport automatically upon an approved order, print freight labels and map your shipping methods to different transport services.

Powered by Pacsoft/Unifaun. You are required to have an account registered at Pacsoft or Unifaun.

Over 200 transport services from both local and global freight companies like DHL, Posten/Postnord, Bring, DB Schenker, Posti Oy, TNT and more.

More freight companies are being added constantly. Let us know if you are missing one.

== Installation ==

1. Upload `woo-pacsoft-unifaun` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Provide your Pacsoft/Unifaun account details in 'Settings' - 'Pacsoft/Unifaun' and set your shipping preferences.

== Frequently asked questions ==

= What is Sender Quick Value? =

In some cases you need to have multiple sender addresses, e.g. when your company is based in multiple countries. You can add multiple sender addresses in Pacsoft/Unifaun System and map its Sender Quick Value in WordPress.

== Screenshots ==



== Changelog ==

2.1.3
- This plugin now works also if running with Woocommerce 2.6.X.

2.1.0
- Sync shippings classes with shipping services.

- If you have more then one shipping class in an order it will automatically choose the shipping service that corresponds to the most expensive shipping class.

2.0.13
- 	Added address2 (C/O field) to all Unifaun/Pacsoft labels if this was present in the WC order

- 	When you hold the shift key down and press the "Sync order to Pacsoft / Unifaun" button, you
	now force the sync, which means that the plugin will ignore the fact that the order has been
	synced before already.

-	When syncing orders, previously the web browser window would scroll up to the top in every case.
	Now, it only does so in the event of a failed sync. 

2.0.12
- Added option to print return labels

2.0.11
- Added all UPS services to plugin service list

2.0.10
- Bugfixes

2.0.1
- Added NOT (e-mail notification) addon to IT16 service.

2.0.0
- Fixed bug where mapped Sender Quick Value was ignored. 
- Minor improvements. 
- Changed versioning format to Semantic Versioning 2.0.0.

== Upgrade notice ==

