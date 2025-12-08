<?php
/**
 * Content sanitization coordinator.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Sanitizer class for content cleaning.
 */
class CF7AC_Sanitizer
{

    /**
     * Detector instance.
     *
     * @var CF7AC_Detector
     */
    private $detector;

    /**
     * Settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param array $settings Sanitization settings.
     */
    public function __construct($settings = array())
    {
        $this->settings = wp_parse_args(
            $settings,
            array(
                'action' => 'erase',
                'blacklist' => array(),
                'whitelist' => array(),
            )
        );

        // Initialize detector.
        $this->detector = new CF7AC_Detector(
            array(
                'blacklist' => $this->settings['blacklist'],
                'whitelist' => $this->settings['whitelist'],
                'fuzzy_matching' => $this->settings['fuzzy_matching'] ?? false,
                'fuzzy_threshold' => $this->settings['fuzzy_threshold'] ?? 2,
                'use_fast_matcher' => $this->settings['use_fast_matcher'] ?? false,
            )
        );
    }

    /**
     * Sanitize text based on settings.
     *
     * @param string $text Input text.
     * @return array Sanitization result with cleaned text and matches.
     */
    public function sanitize($text)
    {
        // Detect banned content.
        $detection = $this->detector->detect($text);

        if (!$detection['found']) {
            return array(
                'text' => $text,
                'modified' => false,
                'matches' => array(),
            );
        }

        // Erase banned words.
        $sanitized_text = $this->erase_content($text, $detection['matches']);

        return array(
            'text' => $sanitized_text,
            'modified' => $sanitized_text !== $text,
            'matches' => $detection['matches'],
            'action' => 'erase',
        );
    }

    /**
     * Erase banned words from text.
     *
     * @param string $text Input text.
     * @param array  $matches Detected matches.
     * @return string Cleaned text.
     */
    private function erase_content($text, $matches)
    {
        // Erase banned words only.
        foreach ($matches as $match) {
            $word = $match['word'];
            $text = preg_replace('/\b' . preg_quote($word, '/') . '\b/iu', '', $text);
        }

        // Clean up extra whitespace.
        $text = CF7AC_Normalizer::normalize_whitespace($text);

        return trim($text);
    }

    /**
     * Get detection result without sanitizing.
     *
     * @param string $text Input text.
     * @return array Detection result.
     */
    public function detect_only($text)
    {
        return $this->detector->detect($text);
    }

    /**
     * Sanitize multiple fields.
     *
     * @param array $fields Array of field_name => value pairs.
     * @param array $excluded_fields Fields to skip.
     * @return array Sanitization results.
     */
    public function sanitize_fields($fields, $excluded_fields = array())
    {
        $results = array();

        foreach ($fields as $field_name => $value) {
            // Skip excluded fields.
            if (in_array($field_name, $excluded_fields, true)) {
                $results[$field_name] = array(
                    'text' => $value,
                    'modified' => false,
                    'matches' => array(),
                );
                continue;
            }

            // Only sanitize strings.
            if (!is_string($value)) {
                $results[$field_name] = array(
                    'text' => $value,
                    'modified' => false,
                    'matches' => array(),
                );
                continue;
            }

            // Sanitize field.
            $results[$field_name] = $this->sanitize($value);
        }

        return $results;
    }
}
