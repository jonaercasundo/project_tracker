<?php

if (!function_exists('fallback_avatar_initials')) {
    function fallback_avatar_initials($name)
    {
        return collect(explode(' ', $name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->join('');
    }
}