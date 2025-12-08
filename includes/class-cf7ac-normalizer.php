<?php
/**
 * Text normalization engine.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Normalizer class for text processing.
 */
class CF7AC_Normalizer
{

    /**
     * Leetspeak character mappings.
     *
     * @var array
     */
    private static $leetspeak_map = array(
        '@' => 'a',
        '4' => 'a',
        '3' => 'e',
        '1' => 'i',
        '!' => 'i',
        '0' => 'o',
        '5' => 's',
        '$' => 's',
        '7' => 't',
        '+' => 't',
        '|_|' => 'u',
        '\/' => 'v',
        '\/\/' => 'w',
    );

    /**
     * Normalize text for detection.
     *
     * @param string $text Input text.
     * @param array  $options Normalization options.
     * @return string Normalized text.
     */
    public static function normalize($text, $options = array())
    {
        $defaults = array(
            'lowercase' => true,
            'leetspeak' => true,
            'strip_punctuation' => true,
            'collapse_repeated' => true,
            'normalize_whitespace' => true,
        );

        $options = wp_parse_args($options, $defaults);

        // Convert to lowercase (Unicode-aware).
        if ($options['lowercase']) {
            $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        }

        // Convert leetspeak.
        if ($options['leetspeak']) {
            $text = self::convert_leetspeak($text);
        }

        // Strip punctuation (preserve Unicode letters and numbers).
        if ($options['strip_punctuation']) {
            $text = self::strip_punctuation($text);
        }

        // Collapse repeated characters.
        if ($options['collapse_repeated']) {
            $text = self::collapse_repeated_chars($text);
        }

        // Normalize whitespace.
        if ($options['normalize_whitespace']) {
            $text = self::normalize_whitespace($text);
        }

        return trim($text);
    }

    /**
     * Convert leetspeak characters to normal characters.
     *
     * @param string $text Input text.
     * @return string Converted text.
     */
    private static function convert_leetspeak($text)
    {
        // Sort by length (longest first) to handle multi-character replacements.
        $map = self::$leetspeak_map;
        uksort($map, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($map as $leet => $normal) {
            $text = str_replace($leet, $normal, $text);
        }

        return $text;
    }

    /**
     * Strip punctuation while preserving Unicode letters and numbers.
     *
     * @param string $text Input text.
     * @return string Text without punctuation.
     */
    private static function strip_punctuation($text)
    {
        // Remove all characters except Unicode letters, numbers, and spaces.
        // \p{L} = Unicode letters, \p{N} = Unicode numbers.
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        return $text;
    }

    /**
     * Collapse repeated characters (more than 2 occurrences).
     *
     * @param string $text Input text.
     * @return string Text with collapsed characters.
     */
    private static function collapse_repeated_chars($text)
    {
        // Replace 3+ repeated characters with 2 occurrences.
        // Example: "baaaad" -> "baad", "heeello" -> "heello".
        $text = preg_replace('/(\p{L})\1{2,}/u', '$1$1', $text);

        return $text;
    }

    /**
     * Normalize whitespace (trim and collapse multiple spaces).
     *
     * @param string $text Input text.
     * @return string Text with normalized whitespace.
     */
    private static function normalize_whitespace($text)
    {
        // Replace multiple whitespace characters with a single space.
        $text = preg_replace('/\s+/', ' ', $text);

        return $text;
    }

    /**
     * Normalize a list of words/phrases.
     *
     * @param array $words Array of words/phrases.
     * @param array $options Normalization options.
     * @return array Normalized words.
     */
    public static function normalize_list($words, $options = array())
    {
        return array_map(
            function ($word) use ($options) {
                return self::normalize($word, $options);
            },
            $words
        );
    }

    /**
     * Extract words from text.
     *
     * @param string $text Input text.
     * @return array Array of words.
     */
    public static function extract_words($text)
    {
        // Split by whitespace.
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $words;
    }

    /**
     * Get character count (Unicode-aware).
     *
     * @param string $text Input text.
     * @return int Character count.
     */
    public static function char_count($text)
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    /**
     * Check if text contains URLs.
     *
     * @param string $text Input text.
     * @return bool True if contains URLs.
     */
    public static function contains_urls($text)
    {
        // Simple URL detection pattern.
        $pattern = '/(https?:\/\/|www\.)[^\s]+/i';
        return (bool) preg_match($pattern, $text);
    }

    /**
     * Count URLs in text.
     *
     * @param string $text Input text.
     * @return int Number of URLs.
     */
    public static function count_urls($text)
    {
        $pattern = '/(https?:\/\/|www\.)[^\s]+/i';
        preg_match_all($pattern, $text, $matches);
        return count($matches[0]);
    }

    /**
     * Strip URLs from text.
     *
     * @param string $text Input text.
     * @return string Text without URLs.
     */
    public static function strip_urls($text)
    {
        $pattern = '/(https?:\/\/|www\.)[^\s]+/i';
        return preg_replace($pattern, '', $text);
    }
}
