<?php

namespace App\Support;

/**
 * Draws a Code 128 (set B) barcode as SVG, so waybills scan without an extra package.
 */
class Barcode
{
    /**
     * Bar and space widths for each Code 128 value (index = value). The last one is the stop code.
     *
     * @var list<string>
     */
    public const array PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const int START_B = 104;

    private const int STOP = 106;

    /**
     * The code values for a text, including start, checksum and stop.
     *
     * @return list<int>
     */
    public static function values(string $text): array
    {
        $values = [self::START_B];
        $checksum = self::START_B;

        foreach (str_split($text) as $position => $character) {
            $value = ord($character) - 32;

            if ($value < 0 || $value > 94) {
                throw new \InvalidArgumentException('Code 128 set B only covers printable ASCII.');
            }

            $values[] = $value;
            $checksum += ($position + 1) * $value;
        }

        $values[] = $checksum % 103;
        $values[] = self::STOP;

        return $values;
    }

    public static function svg(string $text, int $height = 60, int $moduleWidth = 2): string
    {
        $quietZone = 10 * $moduleWidth;
        $x = $quietZone;
        $bars = '';

        foreach (self::values($text) as $value) {
            foreach (str_split(self::PATTERNS[$value]) as $index => $width) {
                $width = (int) $width * $moduleWidth;

                // Even positions are bars, odd positions are spaces.
                if ($index % 2 === 0) {
                    $bars .= '<rect x="'.$x.'" y="0" width="'.$width.'" height="'.$height.'"/>';
                }

                $x += $width;
            }
        }

        $total = $x + $quietZone;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$total.'" height="'.$height.'" viewBox="0 0 '.$total.' '.$height.'">'
            .'<rect width="100%" height="100%" fill="#fff"/><g fill="#000">'.$bars.'</g></svg>';
    }

    /**
     * For an <img src>, which is how dompdf draws SVG best.
     */
    public static function dataUri(string $text, int $height = 60): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($text, $height));
    }
}
