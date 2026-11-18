<?php

namespace App\Support;

final class ApplicationReason
{
    public static function wordCount(string $value): int
    {
        // Count words separated by whitespace, including line breaks.
        return count(preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
