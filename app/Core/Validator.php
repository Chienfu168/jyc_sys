<?php

namespace App\Core;

/**
 * 輕量表單驗證輔助。每個規則接收 $input 與「欄位 => 標籤」對應,
 * 回傳第一個錯誤訊息(字串)或在全部通過時回傳 null,與各 Controller
 * 既有的 `if ($error = Validator::xxx(...))` 用法一致。
 *
 * 空值一律視為通過(交由 required() 處理必填),讓規則可自由組合。
 */
final class Validator
{
    /**
     * 必填:欄位不可為空白。
     *
     * @param array<string, string> $fields 欄位 => 顯示標籤
     */
    public static function required(array $input, array $fields): ?string
    {
        foreach ($fields as $field => $label) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                return "{$label}不可空白";
            }
        }

        return null;
    }

    /**
     * Email 格式。空值略過。
     *
     * @param array<string, string> $fields
     */
    public static function email(array $input, array $fields): ?string
    {
        foreach ($fields as $field => $label) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return "{$label}格式不正確";
            }
        }

        return null;
    }

    /**
     * 數值(可含小數與負號)。空值略過。
     *
     * @param array<string, string> $fields
     */
    public static function numeric(array $input, array $fields): ?string
    {
        foreach ($fields as $field => $label) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && !is_numeric($value)) {
                return "{$label}必須是數字";
            }
        }

        return null;
    }

    /**
     * 日期,格式需為 YYYY-MM-DD 且為合法日曆日期。空值略過。
     *
     * @param array<string, string> $fields
     */
    public static function date(array $input, array $fields): ?string
    {
        foreach ($fields as $field => $label) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)
                || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return "{$label}日期格式不正確";
            }
        }

        return null;
    }

    /**
     * 字串長度上限。空值略過。
     *
     * @param array<string, string> $fields
     */
    public static function maxLength(array $input, array $fields, int $max): ?string
    {
        foreach ($fields as $field => $label) {
            $value = (string) ($input[$field] ?? '');
            if ($value !== '' && mb_strlen($value) > $max) {
                return "{$label}長度不可超過 {$max} 個字元";
            }
        }

        return null;
    }

    /**
     * 欄位值需落在允許清單內。空值略過。
     *
     * @param array<int, string|int> $allowed
     */
    public static function in(array $input, string $field, string $label, array $allowed): ?string
    {
        $value = $input[$field] ?? '';
        if ($value === '' || $value === null) {
            return null;
        }

        if (!in_array((string) $value, array_map('strval', $allowed), true)) {
            return "{$label}選項不正確";
        }

        return null;
    }

    /**
     * 依序套用多個規則,回傳第一個錯誤或 null。
     *
     * @param array<int, ?string> $errors 各規則的回傳值
     */
    public static function firstError(array $errors): ?string
    {
        foreach ($errors as $error) {
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }
}
