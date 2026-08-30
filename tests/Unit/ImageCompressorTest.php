<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\ImageCompressor;
use PHPUnit\Framework\TestCase;

/**
 * 憑證影像壓縮的測試。
 */
final class ImageCompressorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            $this->markTestSkipped('GD 未安裝,略過影像壓縮測試。');
        }
    }

    public function testIsCompressibleRecognizesImageMimes(): void
    {
        $this->assertTrue(ImageCompressor::isCompressible('image/jpeg'));
        $this->assertTrue(ImageCompressor::isCompressible('image/png'));
        $this->assertTrue(ImageCompressor::isCompressible('image/webp'));
        $this->assertFalse(ImageCompressor::isCompressible('application/pdf'));
        $this->assertFalse(ImageCompressor::isCompressible('text/plain'));
    }

    public function testCompressDownscalesLargeImageAndShrinksSize(): void
    {
        $dir = sys_get_temp_dir();
        $src = $dir . '/pcq_src_' . uniqid() . '.jpg';
        $dst = $dir . '/pcq_dst_' . uniqid() . '.jpg';

        $im = imagecreatetruecolor(4000, 3000);
        for ($i = 0; $i < 40; $i++) {
            imagefilledrectangle($im, random_int(0, 4000), random_int(0, 3000), random_int(0, 4000), random_int(0, 3000), imagecolorallocate($im, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        }
        imagejpeg($im, $src, 95);
        imagedestroy($im);
        $before = (int) filesize($src);

        $ok = ImageCompressor::compressToJpeg($src, $dst, 1600, 75);
        $this->assertTrue($ok);
        $this->assertFileExists($dst);

        [$w, $h] = getimagesize($dst);
        $this->assertLessThanOrEqual(1600, max($w, $h));
        $this->assertSame(round(4000 / 3000, 3), round($w / $h, 3), '長寬比應維持');
        $this->assertLessThan($before, (int) filesize($dst));

        @unlink($src);
        @unlink($dst);
    }

    public function testCompressReturnsFalseForNonImage(): void
    {
        $dir = sys_get_temp_dir();
        $src = $dir . '/pcq_notimg_' . uniqid() . '.txt';
        $dst = $dir . '/pcq_out_' . uniqid() . '.jpg';
        file_put_contents($src, 'not an image');

        $this->assertFalse(ImageCompressor::compressToJpeg($src, $dst));
        $this->assertFileDoesNotExist($dst);

        @unlink($src);
        @unlink($dst);
    }
}
