<?php

namespace App\Domain\OfficialLetters;

/**
 * 陳報公文(函)的純格式邏輯(不依賴資料庫):將多行文字轉為公文慣用的
 * 中文數字條列(一、二、三...)與括號子項((一)(二)(三)...)。
 */
final class LetterFormat
{
    private const DIGITS = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九'];

    /**
     * 將 1~99 的整數轉換為中文數字,用於公文條列編號。超出範圍時以阿拉伯數字表示。
     */
    public static function ordinal(int $n): string
    {
        if ($n < 1 || $n > 99) {
            return (string) $n;
        }

        if ($n < 10) {
            return self::DIGITS[$n];
        }

        $tens = intdiv($n, 10);
        $ones = $n % 10;
        $text = $tens === 1 ? '十' : self::DIGITS[$tens] . '十';

        return $ones === 0 ? $text : $text . self::DIGITS[$ones];
    }

    /**
     * 將多行文字(每行一項)拆分為去除空白與空行後的項目陣列。
     *
     * @return array<int, string>
     */
    public static function lines(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = array_map('trim', $lines);

        return array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));
    }
}
