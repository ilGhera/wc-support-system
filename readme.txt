=== ilGhera Support System for WooCommerce ===
Contributors: ghera74
Tags: WooCommerce, support, ticket, thread, orders
Version: 1.2.9
Stable tag: 1.2.9
Requires at least: 5.0
Tested up to: 6.8
WC tested up to: 10
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Give support to your WooComerce customers with this fast and easy to use ticket system.


== Description ==
Customer care is a very important part of our online store and a support service is essential to bring our e-commerce to the next level.
ilGhera Support System for WooCommerce integrates into your WordPress site a simple and fast support system, which will allow your users to open ticket assistance for WooCommerce products purchased, then receiving a notification when a reply is published.
The ability to update the ticket with new messages, allows you to keep the logical thread of the conversation, making more productive your work and facilitating the user.


**Functionlities**

* Select a page or create a new one for adding the support service in front-end
* Chose if insert the tickets table before or after the page content
* Customize the colors for admin and users threads.
* Send notifications to admin and users
* Support service for not logged in users (Premium)
* Auto-close tickets after a specified period of time (Premium)

https://youtu.be/XUtmBvoPwkI

== Installation ==
**From your WordPress dashboard**

* Visit Plugins > Add New.
* Search for ilGhera Support System for WooCommerce and download it.
* Activate ilGhera Support System for WooCommerce from your Plugins page.
* Once Activated, go to <strong>Support System for WC</strong> menu and set you preferences.

**From WordPress.org**

* Download ilGhera Support System for WooCommerce
* Upload the wc-support-system folder to your /wp-content/plugins/ directory, using your favorite method (ftp, sftp, scp, etc...)
* Activate ilGhera Support System for WooCommerce from your Plugins page.
* Once Activated, go to **WC Support/ Settings** menu and set you preferences.


== Screenshots ==
1. Single ticket view in fornt-end
2. Single ticket view in back-end
3. Changing the ticket status
4. E-mail notifications
5. Colors customizations
6. Not only for logged in users (Premium)
7. Auto-close tickets (Premium)


== Changelog ==

= 1.2.9 =
Release Date: 27 December 2025

    * Bug Fix: Corrected billing email retrieval method in get_user_products()
    * Bug Fix: Added null check for order existence

= 1.2.8 =
Release Date: 27 December 2025

    * Security: Fixed unauthorized access to ticket content (CVE-2025-14033)
    * Security: Add ownership verification for ticket viewing
    * Security: Prevent unauthenticated users from viewing arbitrary tickets


= 1.2.7 =
Release Date: 23 December 2025

    * Security: Fixed missing capability checks on AJAX callbacks
    * Security: Prevent unauthorized users from deleting or modifying tickets


= 1.2.6 =
Release Date: 6 October 2025

    * Enhancement: Coding standard 


= 1.2.5 =
Release Date: 5 October 2025

    * Enhancement: Plugin renamed
    * Enhancement: New plugin images
    * Enhancement: WooCommerce 10 support 
    * Enhancement: Coding standard 


= 1.2.4 =
Release Date: 16 June 2025

    * Enhancement: WordPress 6.8 support 
    * Enhancement: WooCommerce 9 support 
    * Update: (Premium) Plugin Update Checker 
    * Update: (Premium) ilGhera Notice 


= 1.2.3 =
Release date: 7 March 2024

    * Enhancement: Coding standard 
    * Bug: Nonce missed on saving options 


= 1.2.2 =
Release date: 31 December 2023

    * Enhancement: HPOS compatibility 
    * Enhancement: WP coding standards 
    * Update: (Premium) Plugin Update Checker 


= 1.2.1 =
Release Date: 20 September 2023

    * Bug: CSS fix in new thread box
    * Bug: Chosen script missed in front-end


= 1.2.0 =
Release Date: 29 August 2023

    * Enhancement: Send the last thread and close the ticket in one shot 
    * Enhancement: (Premium) New option to give the ability to close tickets to the user 
    * Enhancement: (Premium) Admin notice for premium key
    * Enhancement: Better user interface 
    * Enhancement: WP coding standards 
    * Update: (Premium) Plugin Update Checker 
    * Update: Tagify 
    * Update: Translations 
    * Bug Fix: PHP Notice on declaring a function and its properties 
    * Bug Fix: Function get_currentuserinfo() replaced because deprecated 


= 1.1.2 =
Release Date: 10 November 2022

    * Enhancement: WordPress 6.1 support 


= 1.1.1 =
Release Date: 11 May, 2022

    * Bug Fix: Empty email notification sent after performing a ticket search in back-end
    * Bug Fix: Trim missed in ticket search field value
    * Bug Fix: Missing translations


= 1.1.0 =
Release Date: 10 May, 2022

    * Enhancement (Premium): Send notifications to additional recipients about the ticket updates
    * Enhancement: Search ticket by id using an hash followed the number
    * Enhancement: Display the product name in mouse hover on the ticket product image
    * Enhancement: Better user interface
    * Bug Fix: Media not added in the first message of the ticket


= 1.0.5 =
Release Date: 26 March, 2021

    * Bug Fix: wp_unslash missed in ticket text


= 1.0.4 =
Release Date: 21 March, 2021

    * Bug Fix: Bad Front-end tickets table style


= 1.0.3 =
Release Date: 17 July, 2019

    * Enhancement: Search tickets by title, user name and user email.
    * Enhancement: General improvements.
    * Bug Fix: Front-end tickets table not responsive.


= 1.0.2 =
Release Date: 19 March, 2019

    * Enhancement: Allowed HTML in Footer email text and in User notice fields.
    * Bug Fix: Front-end user can now see only his files with "Add Media" button (Premium).


= 1.0.1 =
Release Date: 18 March, 2019

    * Bug Fix: Some tickets not shown if created by the same user sometimes logged in and sometimes not (Premium).


= 1.0.0 =
Release Date: 14 March, 2019

    * Enhancement: Upload files in tickets available for logged in customers. (Premium)
    * Enhancement: General improvements.
    * Bug Fix: Frontend editor style missed.


= 0.9.6 =
Release Date: 12 September, 2018

    * Bug Fix: User message repeated many times.


= 0.9.5 =
Release Date: 25 June, 2018

    * Bug Fix: JS scripts missed in setting page.


= 0.9.4 =
Release Date: 15 June, 2018

    * Bug Fix: Setting page slug changed.


= 0.9.3 =
Release Date: 15 June, 2018

    * Bug Fix: Plugin deactivation on WooCommerce missed.


= 0.9.2 =
Release Date: 24 May, 2018

    * Bug Fix: Products purchased by the user not found.


= 0.9.1 =
Release Date: 5 April, 2018

    * Bug Fix: Plugin not deactivated if WooCommerce is not installed.


= 0.9.0 =
Release Date: 4 April, 2018

    * First release.
