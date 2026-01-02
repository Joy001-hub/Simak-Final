<?php

// Provide a minimal fallback when the mbstring extension is missing.
// Laravel string helpers call mb_split(); without mbstring the app fatals.
if (! function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array
    {
        if ($pattern === '') {
            return [$string];
        }

        // If delimiters are absent, wrap the pattern and force UTF-8 mode.
        $regex = ($pattern[0] === '/' ? $pattern.'u' : '/'.$pattern.'/u');

        // mb_split treats limit <= 0 as "no limit".
        return preg_split($regex, $string, $limit > 0 ? $limit : -1);
    }
}
