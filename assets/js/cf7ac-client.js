/**
 * CF7 Auto Cleaner - Client-side content filtering
 * 
 * @package CF7_Auto_Cleaner
 * @version 1.0.0
 */

(function () {
	'use strict';

	// Configuration (passed from PHP via wp_localize_script)
	const config = window.cf7acConfig || {};

	// Exit if not enabled
	if (!config.enabled) {
		return;
	}

	// Compiled patterns cache
	let blacklistPatterns = [];
	let whitelistPatterns = [];

	/**
	 * Initialize the plugin
	 */
	function init() {
		// Compile patterns
		compilePatterns();

		// Wait for DOM ready
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', attachToForms);
		} else {
			attachToForms();
		}
	}

	/**
	 * Compile blacklist and whitelist patterns
	 */
	function compilePatterns() {
		// Compile blacklist
		if (Array.isArray(config.blacklist)) {
			blacklistPatterns = config.blacklist.map(word => {
				const normalized = normalize(word);
				const parts = normalized.split(/\s+/);
				const pattern = parts.map(p => escapeRegex(p)).join('\\s*');

				// Add word boundaries for single words
				const regex = parts.length === 1
					? new RegExp('\\b' + pattern + '\\b', 'giu')
					: new RegExp(pattern, 'giu');

				return {
					original: word,
					normalized: normalized,
					regex: regex
				};
			});
		}

		// Compile whitelist
		if (Array.isArray(config.whitelist)) {
			whitelistPatterns = config.whitelist.map(word => {
				const normalized = normalize(word);
				return {
					original: word,
					normalized: normalized,
					regex: new RegExp('\\b' + escapeRegex(normalized) + '\\b', 'giu')
				};
			});
		}
	}

	/**
	 * Attach to all CF7 forms
	 */
	function attachToForms() {
		const forms = document.querySelectorAll('.wpcf7-form');

		forms.forEach(form => {
			// Monitor text inputs and textareas
			const fields = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="url"], textarea');

			fields.forEach(field => {
				// Debounced input handler
				let debounceTimer;
				field.addEventListener('input', function () {
					clearTimeout(debounceTimer);
					debounceTimer = setTimeout(() => {
						sanitizeField(field);
					}, 200);
				});
			});

			// Hook into CF7 before submit event
			form.addEventListener('wpcf7beforesubmit', function (event) {
				sanitizeAllFields(form);
			});
		});
	}

	/**
	 * Sanitize a single field
	 */
	function sanitizeField(field) {
		const originalValue = field.value;
		const result = sanitizeText(originalValue);

		if (result.modified) {
			// Save caret position
			const caretPos = field.selectionStart;
			const lengthDiff = originalValue.length - result.text.length;

			// Update value
			field.value = result.text;

			// Restore caret position (adjusted for length change)
			const newCaretPos = Math.max(0, caretPos - lengthDiff);
			field.setSelectionRange(newCaretPos, newCaretPos);

			// Show notification if enabled
			if (config.showNotification) {
				showNotification(field, result.matches);
			}
		}
	}

	/**
	 * Sanitize all fields in form
	 */
	function sanitizeAllFields(form) {
		const fields = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="url"], textarea');

		fields.forEach(field => {
			const result = sanitizeText(field.value);
			if (result.modified) {
				field.value = result.text;

				// Show notification if enabled
				if (config.showNotification) {
					showNotification(field, result.matches);
				}
			}
		});
	}

	/**
	 * Sanitize text based on configuration
	 */
	function sanitizeText(text) {
		if (!text || typeof text !== 'string') {
			return { text: text, modified: false, matches: [] };
		}

		// Detect banned content
		const detection = detectBannedContent(text);

		if (!detection.found) {
			return { text: text, modified: false, matches: [] };
		}

		let sanitizedText = text;
		const action = config.action || 'erase';

		// Apply action
		switch (action) {
			case 'erase':
				sanitizedText = eraseContent(text, detection.matches);
				break;

			case 'replace':
				sanitizedText = replaceContent(text, detection.matches);
				break;

			case 'block':
				// Block is handled server-side via validation
				break;

			case 'flag_only':
				// Flag only - don't modify
				break;
		}

		return {
			text: sanitizedText,
			modified: sanitizedText !== text,
			matches: detection.matches
		};
	}

	/**
	 * Detect banned content in text
	 */
	function detectBannedContent(text) {
		const normalized = normalize(text);
		const matches = [];

		// Check whitelist first
		if (isWhitelisted(normalized)) {
			return { found: false, matches: [] };
		}

		// Check blacklist
		blacklistPatterns.forEach(pattern => {
			const match = normalized.match(pattern.regex);
			if (match) {
				matches.push({
					word: pattern.original,
					matched: match[0]
				});
			}
		});

		return {
			found: matches.length > 0,
			matches: matches
		};
	}

	/**
	 * Check if text is whitelisted
	 */
	function isWhitelisted(normalizedText) {
		return whitelistPatterns.some(pattern => {
			return pattern.regex.test(normalizedText);
		});
	}

	/**
	 * Erase banned content from text
	 */
	function eraseContent(text, matches) {
		let result = text;

		matches.forEach(match => {
			const word = match.word;
			const regex = new RegExp('\\b' + escapeRegex(word) + '\\b', 'giu');
			result = result.replace(regex, '');
		});

		// Clean up extra whitespace
		result = result.replace(/\s+/g, ' ').trim();

		return result;
	}

	/**
	 * Replace banned content with mask
	 */
	function replaceContent(text, matches) {
		let result = text;
		const mask = config.replaceMask || '*****';

		matches.forEach(match => {
			const word = match.word;
			const regex = new RegExp('\\b' + escapeRegex(word) + '\\b', 'giu');
			result = result.replace(regex, mask);
		});

		return result;
	}

	/**
	 * Normalize text (simplified client-side version)
	 */
	function normalize(text) {
		if (!text) return '';

		// Lowercase
		text = text.toLowerCase();

		// Leetspeak conversion
		const leetspeakMap = {
			'@': 'a', '4': 'a', '3': 'e', '1': 'i', '!': 'i',
			'0': 'o', '5': 's', '$': 's', '7': 't', '+': 't'
		};

		Object.keys(leetspeakMap).forEach(leet => {
			text = text.split(leet).join(leetspeakMap[leet]);
		});

		// Strip punctuation (keep letters, numbers, spaces)
		text = text.replace(/[^\p{L}\p{N}\s]/gu, ' ');

		// Collapse repeated characters (3+ to 2)
		text = text.replace(/(\p{L})\1{2,}/gu, '$1$1');

		// Normalize whitespace
		text = text.replace(/\s+/g, ' ').trim();

		return text;
	}

	/**
	 * Escape special regex characters
	 */
	function escapeRegex(str) {
		return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	/**
	 * Show notification to user
	 */
	function showNotification(field, matches) {
		// Remove existing notification
		const existingNotice = field.parentElement.querySelector('.cf7ac-notice');
		if (existingNotice) {
			existingNotice.remove();
		}

		// Create notification element
		const notice = document.createElement('div');
		notice.className = 'cf7ac-notice';
		notice.textContent = config.notificationMessage || 'We removed disallowed words from your message.';
		notice.style.cssText = 'color: #d63638; font-size: 12px; margin-top: 4px;';

		// Insert after field
		field.parentElement.insertBefore(notice, field.nextSibling);

		// Auto-remove after 5 seconds
		setTimeout(() => {
			notice.remove();
		}, 5000);
	}

	// Initialize when script loads
	init();

})();
