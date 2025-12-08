<?php
/**
 * Content detection and matching engine.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Detector class for content matching.
 */
class CF7AC_Detector
{

    /**
     * Blacklist patterns (cached).
     *
     * @var array
     */
    private $blacklist_patterns = array();

    /**
     * Whitelist patterns (cached).
     *
     * @var array
     */
    private $whitelist_patterns = array();

    /**
     * Settings.
     *
     * @var array
     */
    private $settings = array();

    /**
     * Constructor.
     *
     * @param array $settings Detection settings.
     */
    public function __construct($settings = array())
    {
        $this->settings = wp_parse_args(
            $settings,
            array(
                'blacklist' => array(),
                'whitelist' => array(),
                'fuzzy_matching' => false,
                'fuzzy_threshold' => 2,
                'use_fast_matcher' => false,
            )
        );

        $this->compile_patterns();
    }

    /**
     * Compile regex patterns from blacklist/whitelist.
     */
    private function compile_patterns()
    {
        // Try to get from cache.
        $cache_key = 'cf7ac_patterns_' . md5(wp_json_encode($this->settings));
        $cached = get_transient($cache_key);

        if (false !== $cached && is_array($cached)) {
            $this->blacklist_patterns = $cached['blacklist'] ?? array();
            $this->whitelist_patterns = $cached['whitelist'] ?? array();
            return;
        }

        // Compile blacklist patterns.
        $this->blacklist_patterns = $this->compile_pattern_list($this->settings['blacklist']);

        // Compile whitelist patterns.
        $this->whitelist_patterns = $this->compile_pattern_list($this->settings['whitelist']);

        // Cache for 1 hour.
        set_transient(
            $cache_key,
            array(
                'blacklist' => $this->blacklist_patterns,
                'whitelist' => $this->whitelist_patterns,
            ),
            HOUR_IN_SECONDS
        );
    }

    /**
     * Compile pattern list into regex patterns.
     *
     * @param array $words List of words/phrases.
     * @return array Compiled patterns.
     */
    private function compile_pattern_list($words)
    {
        $patterns = array();

        foreach ($words as $word) {
            if (empty($word)) {
                continue;
            }

            // Normalize the word.
            $normalized = CF7AC_Normalizer::normalize($word);

            if (empty($normalized)) {
                continue;
            }

            // Build regex pattern with word boundaries.
            // For multi-word phrases, allow optional whitespace between words.
            $pattern_parts = preg_split('/\s+/', $normalized);
            $pattern = implode('\s*', array_map('preg_quote', $pattern_parts));

            // Add word boundaries for single words.
            if (count($pattern_parts) === 1) {
                $pattern = '\b' . $pattern . '\b';
            }

            $patterns[] = array(
                'original' => $word,
                'normalized' => $normalized,
                'pattern' => '/' . $pattern . '/iu',
            );
        }

        return $patterns;
    }

    /**
     * Detect banned content in text.
     *
     * @param string $text Input text.
     * @return array Detection results with matches.
     */
    public function detect($text)
    {
        // Normalize text.
        $normalized_text = CF7AC_Normalizer::normalize($text);

        $matches = array();

        // Check whitelist first.
        if ($this->is_whitelisted($normalized_text)) {
            return array(
                'found' => false,
                'matches' => array(),
            );
        }

        // Use fast matcher if enabled.
        if ($this->settings['use_fast_matcher'] && class_exists('CF7AC_Aho_Corasick')) {
            $matches = $this->detect_with_aho_corasick($normalized_text);
        } else {
            $matches = $this->detect_with_regex($normalized_text);
        }

        // Apply fuzzy matching if enabled and no exact matches found.
        if ($this->settings['fuzzy_matching'] && empty($matches)) {
            $matches = $this->detect_with_fuzzy($normalized_text);
        }

        return array(
            'found' => !empty($matches),
            'matches' => $matches,
        );
    }

    /**
     * Detect using regex patterns.
     *
     * @param string $normalized_text Normalized text.
     * @return array Matches.
     */
    private function detect_with_regex($normalized_text)
    {
        $matches = array();

        foreach ($this->blacklist_patterns as $pattern_data) {
            if (preg_match($pattern_data['pattern'], $normalized_text, $match)) {
                $matches[] = array(
                    'word' => $pattern_data['original'],
                    'matched' => $match[0],
                    'method' => 'regex',
                );
            }
        }

        return $matches;
    }

    /**
     * Detect using Aho-Corasick algorithm.
     *
     * @param string $normalized_text Normalized text.
     * @return array Matches.
     */
    private function detect_with_aho_corasick($normalized_text)
    {
        $ac = new CF7AC_Aho_Corasick(array_column($this->blacklist_patterns, 'normalized'));
        $found = $ac->search($normalized_text);

        $matches = array();
        foreach ($found as $match) {
            $matches[] = array(
                'word' => $match,
                'matched' => $match,
                'method' => 'aho-corasick',
            );
        }

        return $matches;
    }

    /**
     * Detect using fuzzy matching (Levenshtein distance).
     *
     * @param string $normalized_text Normalized text.
     * @return array Matches.
     */
    private function detect_with_fuzzy($normalized_text)
    {
        $matches = array();
        $words = CF7AC_Normalizer::extract_words($normalized_text);
        $threshold = absint($this->settings['fuzzy_threshold']);

        foreach ($words as $word) {
            // Skip very short words for fuzzy matching.
            if (CF7AC_Normalizer::char_count($word) < 3) {
                continue;
            }

            foreach ($this->blacklist_patterns as $pattern_data) {
                $blacklist_word = $pattern_data['normalized'];

                // Only compare words of similar length.
                $length_diff = abs(CF7AC_Normalizer::char_count($word) - CF7AC_Normalizer::char_count($blacklist_word));
                if ($length_diff > $threshold) {
                    continue;
                }

                // Calculate Levenshtein distance.
                $distance = levenshtein($word, $blacklist_word);

                // Adjust threshold based on word length.
                $max_distance = $threshold;
                if (CF7AC_Normalizer::char_count($word) > 8) {
                    $max_distance = $threshold + 1;
                }

                if ($distance <= $max_distance) {
                    $matches[] = array(
                        'word' => $pattern_data['original'],
                        'matched' => $word,
                        'method' => 'fuzzy',
                        'distance' => $distance,
                    );
                }
            }
        }

        return $matches;
    }

    /**
     * Check if text is whitelisted.
     *
     * @param string $normalized_text Normalized text.
     * @return bool True if whitelisted.
     */
    private function is_whitelisted($normalized_text)
    {
        foreach ($this->whitelist_patterns as $pattern_data) {
            if (preg_match($pattern_data['pattern'], $normalized_text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a specific word is whitelisted.
     *
     * @param string $word Word to check.
     * @return bool True if whitelisted.
     */
    public function is_word_whitelisted($word)
    {
        $normalized = CF7AC_Normalizer::normalize($word);

        foreach ($this->whitelist_patterns as $pattern_data) {
            if ($pattern_data['normalized'] === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all matches in text with positions.
     *
     * @param string $text Input text.
     * @return array Matches with positions.
     */
    public function get_matches_with_positions($text)
    {
        $normalized_text = CF7AC_Normalizer::normalize($text);
        $matches = array();

        foreach ($this->blacklist_patterns as $pattern_data) {
            if (preg_match_all($pattern_data['pattern'], $normalized_text, $found, PREG_OFFSET_CAPTURE)) {
                foreach ($found[0] as $match) {
                    $matches[] = array(
                        'word' => $pattern_data['original'],
                        'matched' => $match[0],
                        'position' => $match[1],
                    );
                }
            }
        }

        return $matches;
    }
}
