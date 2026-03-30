<?php

declare(strict_types=1);

function to_slug(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($converted)) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value;
}
