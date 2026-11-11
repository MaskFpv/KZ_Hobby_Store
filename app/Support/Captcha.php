<?php

namespace App\Support;

/**
 * Captcha gambar sederhana tanpa layanan luar (PHP GD).
 * Kode disimpan di session dan hanya berlaku sekali pakai.
 */
class Captcha
{
    public const SESSION_KEY = 'captcha_code';

    /** Tanpa huruf/angka yang mirip (0/O, 1/I/L). */
    private const CHARS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LENGTH = 5;

    public static function generate(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }

        session([self::SESSION_KEY => $code]);

        return $code;
    }

    /** Cek jawaban lalu hapus kode, supaya satu kode tidak bisa dipakai berulang. */
    public static function check(?string $answer): bool
    {
        $code = session()->pull(self::SESSION_KEY);

        return $code !== null && $answer !== null
            && hash_equals($code, strtoupper(trim($answer)));
    }

    /** Gambar PNG 180x60: huruf miring acak, garis & titik pengganggu. */
    public static function png(string $code): string
    {
        $w = 180;
        $h = 60;
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 14, 12, 34));

        // Garis pengganggu
        for ($i = 0; $i < 6; $i++) {
            $c = imagecolorallocate($img, random_int(60, 140), random_int(40, 120), random_int(120, 200));
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $c);
        }

        // Tiap huruf digambar kecil, diputar, lalu diperbesar ke kanvas
        $palette = [[255, 46, 136], [34, 228, 255], [255, 255, 255], [178, 140, 255]];
        foreach (str_split($code) as $i => $char) {
            $glyph = imagecreatetruecolor(12, 16);
            $bg = imagecolorallocate($glyph, 14, 12, 34);
            imagefill($glyph, 0, 0, $bg);
            imagecolortransparent($glyph, $bg);
            [$r, $g, $b] = $palette[array_rand($palette)];
            imagestring($glyph, 5, 2, 0, $char, imagecolorallocate($glyph, $r, $g, $b));

            $rotated = imagerotate($glyph, random_int(-25, 25), $bg);
            imagecolortransparent($rotated, $bg);
            imagecopyresized($img, $rotated, 12 + $i * 32, random_int(4, 14), 0, 0, 30, 40, imagesx($rotated), imagesy($rotated));
            imagedestroy($glyph);
            imagedestroy($rotated);
        }

        // Titik noise
        for ($i = 0; $i < 220; $i++) {
            imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), imagecolorallocate($img, random_int(80, 255), random_int(80, 255), random_int(80, 255)));
        }

        ob_start();
        imagepng($img);
        imagedestroy($img);

        return ob_get_clean();
    }
}
