=== CF7 Auto Cleaner - Auto Erase Profanity & Promotional Content ===
Contributors: akshar789
Tags: contact form 7, spam filter, content filter, profanity filter, form sanitizer
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically filters profanity and promotional content from Contact Form 7 submissions with dual-layer sanitization.

== Description ==

CF7 Auto Cleaner is a lightweight WordPress plugin that automatically removes unwanted words and phrases from Contact Form 7 submissions. It works on both client-side (live filtering as users type) and server-side (backup sanitization) to ensure clean form submissions.

= Key Features =

* **Dual-Layer Filtering** - Client-side live filtering + server-side sanitization
* **Smart Detection** - Regex pattern matching with text normalization
* **Automatic Erase** - Removes banned words/phrases automatically
* **50+ Default Blacklist Words** - Pre-configured spam and promotional terms
* **Whitelist Support** - Prevent false positives
* **User Notifications** - Optional notification when content is modified
* **Performance Optimized** - Lightweight and fast
* **Fully Translatable** - i18n ready

= How It Works =

**Client-Side Filtering (Live)**
* JavaScript monitors form fields in real-time
* Banned words are removed as users type
* Provides instant feedback
* Caret position is preserved for smooth typing

**Server-Side Filtering (Backup)**
* PHP sanitization runs before email is sent
* Catches anything missed by client-side
* Works even if JavaScript is disabled
* Ensures clean data in all cases

= Default Blacklist Categories =

* Spam & Scam (spam, scam, fraud, phishing)
* Gambling (casino, lottery, poker, betting)
* Promotional (click here, buy now, limited time, act now)
* Money Schemes (free money, make money fast, get rich quick)
* Urgency Tactics (urgent, hurry up, last chance)
* Suspicious (verify your account, suspended account)
* Cryptocurrency (bitcoin, crypto, forex)
* Pharmaceutical (pills, medication, prescription)

= Perfect For =

* Blocking spam submissions
* Filtering promotional content
* Removing profanity
* Preventing scam attempts
* Cleaning up form data

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/cf7-auto-cleaner/` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Contact → Auto Cleaner to configure settings
4. Add your blacklist and whitelist words
5. Test with a Contact Form 7 form

== Frequently Asked Questions ==

= Does this work with Contact Form 7? =

Yes! This plugin requires Contact Form 7 to be installed and activated.

= Will it work if JavaScript is disabled? =

Yes! The plugin has server-side sanitization as a backup, so it works even without JavaScript.

= Can I customize the blacklist? =

Absolutely! You can add, remove, or modify any words in the blacklist. The plugin comes with 50+ default terms, but you have full control.

= What happens to the banned words? =

By default, they are automatically erased from the submission. Users see an optional notification that content was modified.

= Can I whitelist certain words? =

Yes! Use the whitelist to prevent false positives. For example, if "ass" is blacklisted, you can whitelist "assess" and "classic".

= Does it work on all form fields? =

Yes, by default. However, you can exclude specific fields in the per-form settings if needed.

= Will this slow down my site? =

No! The plugin is lightweight and optimized for performance. It uses efficient pattern matching and caching.

= Can I use it on multiple forms? =

Yes! You can configure global settings and override them per-form if needed.

== Screenshots ==

1. Settings page - Configure blacklist, whitelist, and notifications
2. Per-form settings - Override global settings for specific forms
3. Client-side filtering in action - Words disappear as users type
4. User notification when content is modified

== Changelog ==

= 1.0.1 - 2025-12-08 =
* Removed logging feature for simplicity
* Moved settings to Contact Form 7 menu
* Added 50+ default blacklist words
* Improved performance
* Enhanced user interface

= 1.0.0 - 2025-12-08 =
* Initial release
* Dual-layer filtering (client + server)
* Per-form settings
* Blacklist and whitelist support
* Text normalization
* User notifications

== Upgrade Notice ==

= 1.0.1 =
Simplified version with improved performance. Settings moved to Contact → Auto Cleaner menu.

== Additional Info ==

For support, feature requests, or bug reports, please visit:
https://github.com/Akshar789/cf7-auto-cleaner

== Privacy Policy ==

This plugin does not collect, store, or transmit any personal data. All filtering happens locally on your WordPress installation.
