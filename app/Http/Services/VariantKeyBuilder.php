<?php

namespace App\Services;

class VariantKeyBuilder
{
    /**
     * @param array<string,string> $pairs [option_slug => value_slug]
     */
    public static function makeFromSlugs(array $pairs): string
    {
        ksort($pairs, SORT_STRING); // canonical order by option slug
        $parts = [];
        foreach ($pairs as $opt => $val) {
            $parts[] = "{$opt}:{$val}";
        }
        return implode('|', $parts);
    }
}
