<?php

namespace App\Core;

final class Validator
{
    public static function required(array $input, array $fields): ?string
    {
        foreach ($fields as $field => $label) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                return "{$label}不可空白";
            }
        }

        return null;
    }
}
