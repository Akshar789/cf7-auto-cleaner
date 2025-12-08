<?php
/**
 * Aho-Corasick algorithm implementation for fast multi-pattern matching.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Aho-Corasick automaton for efficient pattern matching.
 */
class CF7AC_Aho_Corasick
{

    /**
     * Trie structure.
     *
     * @var array
     */
    private $trie = array();

    /**
     * Failure links.
     *
     * @var array
     */
    private $failure = array();

    /**
     * Output links (matched patterns at each node).
     *
     * @var array
     */
    private $output = array();

    /**
     * Patterns to search for.
     *
     * @var array
     */
    private $patterns = array();

    /**
     * Constructor.
     *
     * @param array $patterns Array of patterns to search for.
     */
    public function __construct($patterns)
    {
        $this->patterns = array_filter($patterns);

        // Try to load from cache.
        $cache_key = 'cf7ac_aho_corasick_' . md5(wp_json_encode($this->patterns));
        $cached = get_transient($cache_key);

        if (false !== $cached && is_array($cached)) {
            $this->trie = $cached['trie'] ?? array();
            $this->failure = $cached['failure'] ?? array();
            $this->output = $cached['output'] ?? array();
            return;
        }

        // Build automaton.
        $this->build_trie();
        $this->build_failure_links();

        // Cache for 1 hour.
        set_transient(
            $cache_key,
            array(
                'trie' => $this->trie,
                'failure' => $this->failure,
                'output' => $this->output,
            ),
            HOUR_IN_SECONDS
        );
    }

    /**
     * Build trie from patterns.
     */
    private function build_trie()
    {
        $this->trie = array(0 => array());
        $this->output = array(0 => array());
        $node_count = 1;

        foreach ($this->patterns as $pattern) {
            $current_node = 0;

            // Convert pattern to array of characters (Unicode-aware).
            $chars = preg_split('//u', $pattern, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($chars as $char) {
                if (!isset($this->trie[$current_node][$char])) {
                    $this->trie[$current_node][$char] = $node_count;
                    $this->trie[$node_count] = array();
                    $this->output[$node_count] = array();
                    $node_count++;
                }

                $current_node = $this->trie[$current_node][$char];
            }

            // Mark end of pattern.
            $this->output[$current_node][] = $pattern;
        }
    }

    /**
     * Build failure links using BFS.
     */
    private function build_failure_links()
    {
        $this->failure = array();
        $queue = array();

        // Initialize failure links for depth 1.
        foreach ($this->trie[0] as $char => $node) {
            $this->failure[$node] = 0;
            $queue[] = $node;
        }

        // BFS to build failure links.
        while (!empty($queue)) {
            $current_node = array_shift($queue);

            foreach ($this->trie[$current_node] as $char => $child_node) {
                $queue[] = $child_node;

                // Find failure link.
                $failure_node = $this->failure[$current_node] ?? 0;

                while ($failure_node !== 0 && !isset($this->trie[$failure_node][$char])) {
                    $failure_node = $this->failure[$failure_node] ?? 0;
                }

                if (isset($this->trie[$failure_node][$char]) && $this->trie[$failure_node][$char] !== $child_node) {
                    $this->failure[$child_node] = $this->trie[$failure_node][$char];
                } else {
                    $this->failure[$child_node] = 0;
                }

                // Merge outputs from failure link.
                if (isset($this->output[$this->failure[$child_node]])) {
                    $this->output[$child_node] = array_merge(
                        $this->output[$child_node],
                        $this->output[$this->failure[$child_node]]
                    );
                }
            }
        }
    }

    /**
     * Search for patterns in text.
     *
     * @param string $text Text to search.
     * @return array Found patterns.
     */
    public function search($text)
    {
        $found = array();
        $current_node = 0;

        // Convert text to array of characters (Unicode-aware).
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $char) {
            // Follow failure links until we find a match or reach root.
            while ($current_node !== 0 && !isset($this->trie[$current_node][$char])) {
                $current_node = $this->failure[$current_node] ?? 0;
            }

            // Move to next node if possible.
            if (isset($this->trie[$current_node][$char])) {
                $current_node = $this->trie[$current_node][$char];
            }

            // Check for matches at current node.
            if (!empty($this->output[$current_node])) {
                foreach ($this->output[$current_node] as $pattern) {
                    $found[] = $pattern;
                }
            }
        }

        // Return unique matches.
        return array_unique($found);
    }

    /**
     * Get memory usage of automaton.
     *
     * @return int Approximate memory usage in bytes.
     */
    public function get_memory_usage()
    {
        return strlen(serialize($this->trie)) + strlen(serialize($this->failure)) + strlen(serialize($this->output));
    }
}
