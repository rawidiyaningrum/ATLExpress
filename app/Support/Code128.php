<?php

namespace App\Support;

/**
 * Generator barcode Code 128 (subset B) tanpa dependensi eksternal.
 *
 * Dipakai untuk mencetak barcode nomor dokumen pada estimasi supaya isi
 * dokumen mudah diverifikasi secara mesin.
 */
class Code128
{
    /**
     * Pola lebar bar/space tiap nilai Code 128 (indeks = nilai 0-106).
     *
     * @var array<int, string>
     */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312',
        '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222',
        '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131',
        '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321',
        '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121',
        '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321',
        '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224',
        '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114',
        '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112',
        '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113',
        '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412',
        '211214', '211232', '2331112',
    ];

    private const START_B = 104;

    private const STOP = 106;

    /**
     * Menggambar barcode sebagai SVG (bar hitam di atas latar transparan).
     */
    public static function svg(string $text, int $height = 44, float $module = 1.0, int $quietZone = 8): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        if ($text === '') {
            return '';
        }

        $modules = self::modules($text);
        $totalModules = count($modules) + ($quietZone * 2);
        $width = $totalModules * $module;
        $offset = $quietZone * $module;

        $rects = '';
        $barStart = null;
        $cursor = $offset;

        for ($i = 0, $count = count($modules); $i <= $count; $i++) {
            $isBar = $i < $count && $modules[$i] === 1;

            if ($isBar && $barStart === null) {
                $barStart = $cursor;
            } elseif (! $isBar && $barStart !== null) {
                $rects .= sprintf(
                    '<rect x="%s" y="0" width="%s" height="%d"/>',
                    self::num($barStart),
                    self::num($cursor - $barStart),
                    $height,
                );
                $barStart = null;
            }

            $cursor += $module;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%d" viewBox="0 0 %s %d" shape-rendering="crispEdges" fill="#111827" role="img" aria-label="%s">%s</svg>',
            self::num($width),
            $height,
            self::num($width),
            $height,
            e($text),
            $rects,
        );
    }

    /**
     * Mengubah teks menjadi deret modul (1 = bar, 0 = space).
     *
     * @return array<int, int>
     */
    private static function modules(string $text): array
    {
        $codes = [self::START_B];

        for ($i = 0, $length = strlen($text); $i < $length; $i++) {
            $codes[] = ord($text[$i]) - 32;
        }

        $checksum = self::START_B;

        for ($i = 1, $count = count($codes); $i < $count; $i++) {
            $checksum += $codes[$i] * $i;
        }

        $codes[] = $checksum % 103;
        $codes[] = self::STOP;

        $modules = [];

        foreach ($codes as $code) {
            $pattern = self::PATTERNS[$code];
            $isBar = true;

            for ($i = 0, $length = strlen($pattern); $i < $length; $i++) {
                $modules = array_merge($modules, array_fill(0, (int) $pattern[$i], $isBar ? 1 : 0));
                $isBar = ! $isBar;
            }
        }

        return $modules;
    }

    private static function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
