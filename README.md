# CF7 Auto Cleaner — Auto Erase Profanity & Promotional Content

A lightweight WordPress plugin that automatically filters profanity and promotional content from Contact Form 7 submissions with dual-layer (client-side + server-side) sanitization.

## Features

- **Dual-Layer Filtering**: Client-side live filtering + server-side sanitization
- **Smart Detection**: Regex pattern matching with normalization
- **Automatic Erase**: Removes banned words automatically
- **Per-Form Settings**: Override global settings for individual forms
- **Whitelist Support**: Prevent false positives with whitelist
- **User Notifications**: Optional notification when content is modified
- **Performance Optimized**: Lightweight and fast
- **50+ Default Blacklist Words**: Pre-configured spam and promotional terms
- **Fully Translatable**: i18n ready

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Contact Form 7 plugin installed and activated

## Installation

1. **Upload the plugin**:
   - Download the plugin files
   - Upload to `/wp-content/plugins/cf7-auto-cleaner/` directory
   - Or install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate the plugin**:
   - Go to Plugins → Installed Plugins
   - Find "CF7 Auto Cleaner" and click "Activate"

3. **Configure settings**:
   - Navigate to **Contact → Auto Cleaner**
   - Configure your blacklist and whitelist
   - Save settings

4. **Test the plugin**:
   - Create or edit a Contact Form 7 form
   - Add test content with banned words
   - Verify filtering works both client-side (live) and server-side

## Configuration

### Global Settings

Navigate to **Contact → Auto Cleaner** to configure:

#### Basic Settings
- **Enable Plugin**: Turn filtering on/off globally
- **User Notification**: Show message when content is modified
- **Notification Message**: Customize the message shown to users

#### Blacklist & Whitelist
- **Blacklist**: Words/phrases to filter (one per line)
- **Whitelist**: Words to never block (e.g., "assess", "classic")
- Supports multi-word phrases
- 50+ default spam/promotional terms included

### Per-Form Settings

Edit any Contact Form 7 form to find the **CF7 Auto Cleaner Settings** meta box:

- **Enable for this form**: Override global enable/disable
- **Excluded Fields**: Skip filtering for specific fields (comma-separated)
- **Additional Blacklist/Whitelist**: Merge with global lists

## Default Blacklist Words

The plugin comes pre-configured with 50+ common spam and promotional terms:

**Spam & Scam**: spam, scam, fraud, phishing

**Gambling**: casino, lottery, poker, betting, jackpot, gamble

**Promotional**: click here, buy now, limited time, act now, order now, sign up now, register now, subscribe now, download now

**Money Schemes**: free money, make money fast, get rich quick, work from home, guaranteed income, no risk, risk free, 100% free

**Urgency Tactics**: urgent, hurry up, last chance, don't miss, limited offer, special promotion, exclusive offer, once in a lifetime

**Suspicious**: verify your account, confirm your identity, update your information, suspended account, unusual activity, security alert

**Cryptocurrency**: bitcoin, crypto, forex, trading signals

**Pharmaceutical**: pills, medication, prescription, pharmacy

## Usage Examples

### Example 1: Basic Spam Filter

```
Blacklist:
spam
casino
lottery
```

When a user types "Buy casino chips now", it will be automatically erased to "Buy chips now".

### Example 2: Promotional Content Filter

```
Blacklist:
click here
buy now
limited time
act now
free money
```

### Example 3: Multi-Word Phrases

```
Blacklist:
click here now
buy this product
visit our website
```

### Example 4: Whitelist to Prevent False Positives

```
Blacklist:
ass

Whitelist:
assess
classic
glass
assignment
```

This prevents "assess" and "classic" from being flagged.

## How It Works

### Client-Side Filtering (Live)
- JavaScript monitors form fields in real-time
- Banned words are removed as users type
- Provides instant feedback
- Caret position is preserved for smooth typing

### Server-Side Filtering (Backup)
- PHP sanitization runs before email is sent
- Catches anything missed by client-side
- Works even if JavaScript is disabled
- Ensures clean data in all cases

### Normalization
The plugin normalizes text before detection:
- Converts to lowercase
- Removes special characters
- Handles leetspeak (e.g., "v1agra" → "viagra")
- Collapses repeated characters (e.g., "spaaam" → "spam")

## Testing

### Manual Testing

1. **Client-Side Testing**:
   - Create a CF7 form with text fields
   - Type banned words and watch them disappear instantly
   - Check that caret position is preserved

2. **Server-Side Testing**:
   - Disable JavaScript in browser
   - Submit form with banned content
   - Verify server sanitization works
   - Check email contains sanitized content

## Troubleshooting

### Plugin Not Filtering

1. Check if plugin is enabled: Contact → Auto Cleaner
2. Verify Contact Form 7 is installed and activated
3. Check per-form settings (may be disabled for specific form)
4. Clear browser cache

### False Positives

1. Add words to **Whitelist**
2. Review blacklist for overly broad terms
3. Test with different word combinations

### Client-Side Not Working

1. Check browser console for JavaScript errors
2. Verify CF7 is using latest version
3. Test with default WordPress theme
4. Disable other plugins to check for conflicts

## Extending the Plugin

### Adding Custom Blacklist Programmatically

```php
add_filter('cf7ac_blacklist', function($blacklist) {
    $blacklist[] = 'custom-word';
    return $blacklist;
});
```

### Custom Detection Logic

```php
add_filter('cf7ac_detect_banned', function($is_banned, $text) {
    // Your custom logic
    if (strpos($text, 'custom-pattern') !== false) {
        return true;
    }
    return $is_banned;
}, 10, 2);
```

## Changelog

### Version 1.0.1 (2025-12-08)
- Removed logging feature for simplicity
- Moved settings to Contact Form 7 menu
- Added 50+ default blacklist words
- Improved modal design
- Performance optimizations

### Version 1.0.0 (2025-12-08)
- Initial release
- Dual-layer filtering (client + server)
- Per-form settings
- Blacklist and whitelist support

## Support

For issues, feature requests, or questions:
- GitHub Issues: [github.com/Akshar789/cf7-auto-cleaner](https://github.com/Akshar789/cf7-auto-cleaner)

## License

This plugin is licensed under GPL-2.0+. See LICENSE file for details.

## Credits

Developed by Akshar
Contact Form 7 by Takayuki Miyoshi

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request

---

**Note**: This plugin focuses on simplicity and performance. It automatically erases banned content without complex logging or analytics features.
