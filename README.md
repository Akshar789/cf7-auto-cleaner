# CF7 Auto Cleaner — Auto Erase Profanity & Promotional Content

A production-ready WordPress plugin that automatically filters profanity and promotional content from Contact Form 7 submissions with dual-layer (client-side + server-side) sanitization.

## Features

- **Dual-Layer Filtering**: Client-side live filtering + server-side sanitization
- **Advanced Detection**: Regex, fuzzy matching (Levenshtein), and Aho-Corasick algorithm
- **Flexible Actions**: Erase, replace with mask, block submission, or flag only
- **Per-Form Settings**: Override global settings for individual forms
- **Comprehensive Logging**: Track all filtered submissions with privacy controls
- **Import/Export**: CSV import/export for blacklists and whitelists
- **Performance Optimized**: Pattern caching and fast matching for large lists
- **Privacy-Aware**: GDPR-compliant with configurable data retention
- **Fully Translatable**: i18n ready with POT file included

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Contact Form 7 plugin installed and activated

## Installation

1. **Upload the plugin**:
   - Download the `cf7-auto-cleaner` folder
   - Upload to `/wp-content/plugins/` directory
   - Or install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate the plugin**:
   - Go to Plugins → Installed Plugins
   - Find "CF7 Auto Cleaner" and click "Activate"

3. **Configure settings**:
   - Navigate to Settings → CF7 Auto Cleaner
   - Configure your blacklist, whitelist, and action preferences
   - Save settings

4. **Test the plugin**:
   - Create or edit a Contact Form 7 form
   - Add test content with banned words
   - Verify filtering works both client-side (live) and server-side

## Configuration

### Global Settings

Navigate to **Settings → CF7 Auto Cleaner** to configure:

#### Basic Settings
- **Enable Plugin**: Turn filtering on/off globally
- **Default Action**: Choose what happens when banned content is detected
  - `Erase`: Remove banned words
  - `Replace`: Replace with mask (e.g., `*****`)
  - `Block`: Show validation error
  - `Flag Only`: Log but don't modify
- **Erase Behavior**: Erase word only or entire phrase
- **User Notification**: Show message when content is modified

#### Blacklist & Whitelist
- **Blacklist**: Words/phrases to filter (one per line)
- **Whitelist**: Words to never block (e.g., "assess", "classic")
- Supports multi-word phrases
- Import from CSV/TXT files

#### Advanced Detection
- **Fuzzy Matching**: Enable Levenshtein distance matching
- **Fuzzy Threshold**: Character distance tolerance (1-5)
- **Fast Matcher**: Use Aho-Corasick for large blacklists (>500 entries)

#### Logging
- **Log Submissions**: Record all filtered submissions
- **Retention Days**: Auto-delete logs after X days
- **Max Logs**: Maximum number of logs to keep
- **Admin Email**: Receive notifications for blocked/flagged submissions
- **Store Full Content**: Privacy warning - stores complete submission data

### Per-Form Settings

Edit any Contact Form 7 form to find the **CF7 Auto Cleaner Settings** meta box:

- **Enable for this form**: Override global enable/disable
- **Action Override**: Use different action for this form
- **Excluded Fields**: Skip filtering for specific fields (comma-separated)
- **Additional Blacklist/Whitelist**: Merge with global lists

### Viewing Logs

Navigate to **Settings → CF7 Auto Cleaner → Logs** to:

- View all filtered submissions
- Filter by form, action, date range
- Search log content
- Export logs to CSV
- Mark logs as resolved
- Add admin notes

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

## Testing

### Running Unit Tests

```bash
# Install dependencies
composer install

# Run PHPUnit tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/test-normalizer.php
```

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

3. **Block Mode Testing**:
   - Set action to "Block"
   - Submit form with banned content
   - Verify validation error appears

## Performance Tips

### For Large Blacklists (>500 entries)

1. Enable **Use Fast Matcher** (Aho-Corasick algorithm)
2. Enable **Caching** (default: ON)
3. Consider increasing PHP memory limit if needed:
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

### For High-Traffic Sites

1. Set **Performance Mode** to "High"
2. Reduce **Log Retention Days** to minimize database size
3. Use **Flag Only** mode instead of real-time filtering
4. Consider server-side only (disable client-side JS)

## Troubleshooting

### Plugin Not Filtering

1. Check if plugin is enabled: Settings → CF7 Auto Cleaner
2. Verify Contact Form 7 is installed and activated
3. Check per-form settings (may be disabled for specific form)
4. Clear browser cache and WordPress transients

### False Positives

1. Add words to **Whitelist**
2. Disable **Fuzzy Matching** if too aggressive
3. Review blacklist for overly broad terms
4. Use **Flag Only** mode to test before enforcing

### Performance Issues

1. Check blacklist size (>2000 entries may slow down)
2. Enable **Fast Matcher** for large lists
3. Disable **Fuzzy Matching** (CPU-intensive)
4. Reduce **Log Retention Days**
5. Check PHP error logs for memory issues

### Client-Side Not Working

1. Check browser console for JavaScript errors
2. Verify CF7 is using latest version
3. Test with default WordPress theme
4. Disable other plugins to check for conflicts

## Privacy & GDPR Compliance

### Data Stored

By default, the plugin stores:
- Form ID
- Timestamp
- IP address (can be redacted)
- User agent (sanitized)
- Blocked field names
- Truncated content excerpt (not full submission)

### Privacy Controls

1. **Disable Full Content Storage**: Keep "Store Full Content" OFF
2. **Set Retention Period**: Auto-delete logs after X days
3. **Export with Redaction**: IP addresses are partially redacted in exports
4. **Disable Logging**: Turn off logging entirely if not needed

### GDPR Recommendations

- Set log retention to 30 days or less
- Do NOT enable "Store Full Content"
- Inform users in privacy policy
- Provide data export/deletion on request

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

### Version 1.0.0 (2025-12-08)
- Initial release
- Dual-layer filtering (client + server)
- Advanced detection algorithms
- Per-form settings
- Comprehensive logging
- Import/export functionality

## Future Features

- Machine learning classifier integration
- External API support (CleanSpeak, WebPurify)
- Bulk log actions
- Advanced analytics dashboard
- Multi-language blacklist packs
- Regex pattern support in UI

## Support

For issues, feature requests, or questions:
- GitHub Issues: [github.com/yourusername/cf7-auto-cleaner](https://github.com/yourusername/cf7-auto-cleaner)
- WordPress Support Forum: [wordpress.org/support/plugin/cf7-auto-cleaner](https://wordpress.org/support/plugin/cf7-auto-cleaner)

## License

This plugin is licensed under GPL-2.0+. See LICENSE file for details.

## Credits

Developed by [Your Name]
Contact Form 7 by Takayuki Miyoshi

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Submit a pull request

## Acknowledgments

- Contact Form 7 team for the excellent form plugin
- WordPress community for coding standards and best practices
- Contributors and testers
