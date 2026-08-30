<?php

namespace App\Core;

/**
 * 影像壓縮:將上傳的憑證照片縮到合理尺寸並重新編碼為 JPEG,大幅降低檔案大小。
 * 供零用金等手機快速記帳的憑證上傳使用。缺少 GD 或非影像時回傳 false,呼叫端可退回原檔。
 */
final class ImageCompressor
{
    /** 可壓縮的來源 MIME。 */
    public static function isCompressible(string $mime): bool
    {
        return in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    }

    /**
     * 將 $srcPath 影像壓縮輸出為 JPEG 到 $destPath。
     * 長邊超過 $maxDim 會等比例縮小;品質 $quality(1-100)。
     */
    public static function compressToJpeg(string $srcPath, string $destPath, int $maxDim = 1600, int $quality = 75): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        $info = @getimagesize($srcPath);
        if ($info === false) {
            return false;
        }
        [$width, $height] = $info;
        $mime = $info['mime'] ?? '';
        if ($width < 1 || $height < 1 || !self::isCompressible((string) $mime)) {
            return false;
        }

        $src = self::createFrom($srcPath, (string) $mime);
        if ($src === null) {
            return false;
        }

        try {
            $scale = min(1.0, $maxDim / max($width, $height));
            $targetW = max(1, (int) round($width * $scale));
            $targetH = max(1, (int) round($height * $scale));

            $dst = imagecreatetruecolor($targetW, $targetH);
            if ($dst === false) {
                return false;
            }
            // 透明底(PNG/GIF/WEBP)轉 JPEG 時填白,避免變黑。
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

            $quality = max(1, min(100, $quality));
            $ok = imagejpeg($dst, $destPath, $quality);
            imagedestroy($dst);
            return $ok;
        } finally {
            imagedestroy($src);
        }
    }

    private static function createFrom(string $path, string $mime): ?\GdImage
    {
        $img = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        return $img instanceof \GdImage ? $img : null;
    }
}
