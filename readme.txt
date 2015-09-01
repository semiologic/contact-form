=== Contact Form ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: https://www.semiologic.com/donate/
Tags: semiologic
Requires at least: 3.1
Tested up to: 4.3
Stable tag: trunk

A widget-driven contact form.


== Description ==

The Contact Form plugin for WordPress allows you to manage contact forms on your site.

It is widget-driven, and plays best with widget-driven themes such as the Semiologic theme, especially when combined with the Inline Widgets plugin.

= Placing a Contact Form in a panel/in a sidebar =

It's short and simple:

1. Browse Appearance / Widgets
2. Open the panel of your choice (or sidebar, if not using the Semiologic theme)
3. Place a "Contact Form" widget in that panel/sidebar
4. Configure that contact form widget as needed

Usually, no configuration will be required unless you wish to change the email that receives your correspondence.

Common places to insert a form automatically include:

- To the top/middle right of your site in a wide sidebar. Users commonly swipe their mouse to the top right corner of their screen, and eyeballs generally look for it in that area once they're done reading.
- After all posts ("After The Entries" panel.)

= Embedding a contact form in a static page (with Semiologic Pro) =

As much as a form is nice in a large sidebar, you'll usually want it in a static page:

1. Open the Inline Widgets panel, under Appearance / Widgets
2. Place and configure a Contact Form widget
3. Create or edit your "Contact Us" page; note the "Widgets" drop down menu
4. Select your newly configured contact form in the "Widgets" drop down menu to insert it where your mouse cursor is at

= Autoresponder =

If text is entered in the Auto Responder field of the widget, the form will send an email to submitter with response message.

= Google Analytics integration =

Combining this plugin with the Semiologic Google Analytics (GA) plugin adds an interesting bonus. Specifically, contact form usage gets tracked as page events.

= WP-Hashcash and Akismet integration =

To fight increasingly common contact form spam, the plugin integrates with WP-Hashcash and Akismet. Both of these do an excellent job at fighting spam. (The first is slightly more efficient, and free.)

= DMARC Support =

Yahoo, AOL, Google and others have begun to implement [DMARC](http://www.dmarc.org/) to further combat spam.   Basically is the sender's email domain does not match the sender's email server, the receiver email server wil query the email domain and ask what to do with this unauthenticated message.  Stricter enforcement has it being discarded as spam.

The contact form now has the user's entered email address being set in the Reply-To email header.  The From address will now be default sender address for the domain wordpress@yourdomain.com).

= Help Me! =

The [Semiologic Support Page](https://www.semiologic.com/support/) is the best place to report issues.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 3.0 =

- New configuration option to show the Captions (Your Name, Your Email, ...) as the input field defaults as opposed to over fields
- The Name shown as the sender will now be the user's entered name.   wordpress@yourdomain.com will continue to be actual email address per dmarc needs.
- Removed empty action="" from <form....   that was getting flagged in W3C validator
- Use akismet php class to check comment for spam depending on akismet version used
- Cosmetic tweaks (spacing, text centering) to email sent or failure message
- Updated to use PHP5 constructors as WP deprecated PHP4 constructor type in 4.3.
- WP 4.3 compat
- Tested against PHP 5.6

= 2.7 =

- WP 4.0 compat

= 2.6 =

- Support [DMARC](http://www.dmarc.org/) email messages.
- HTML special characters, like &amp;, used in the site's name  and will be converted to normal text version (&) in the message body.

= 2.5.2 =

- Fix unknown index warning if autorespond is not set.

= 2.5.1 =

- Minor code tweak queue css

= 2.5 =

- Code refactoring
- WP 3.9 compat
- CSS tweak for responsive

= 2.4 =

- Fix duplicate emails being sent that got introduced in 2.3
- Add identifier for sem_cache detection.  Pages with contact form will not be cached due to false spam detection.
- Fix typo in email setup message.
- Refactored some of the code around spam detection.

= 2.3.1 =

- WP 3.8 compat

= 2.3 =

- WP 3.6 compat
- PHP 5.4 compat

= 2.2.2 =

- Clean up some phpdoc errors

= 2.2.1 =

- Don't display cc checkbox if the caption is empty

= 2.2.0 =

- Added autoresponder capability.
- Fixed unknown index warnings when form initially is displayed.

= 2.1.0 =

- Cookies not being set correctly using JavaScript approach.  Using PHP setcookie now.

= 2.0.4 =

- Fix HTML Validation Errors - Missing rows and cols attr on text area field

= 2.0.3 =

- Fix PHP notice

= 2.0.2 =

- Improve cache support

= 2.0.1 =

- Fix typo / HTML validation

= 2.0 =

- Complete rewrite
- WP_Widget class
- Localization
- Code enhancements and optimizations
