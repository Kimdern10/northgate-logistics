<?php

use App\Support\Barcode;

test('every Code 128 pattern is eleven modules wide and the stop code thirteen', function () {
    $patterns = Barcode::PATTERNS;
    $stop = array_pop($patterns);

    expect($patterns)->toHaveCount(106)
        ->and(array_unique($patterns))->toHaveCount(106)
        ->and(array_map(fn (string $pattern): int => array_sum(str_split($pattern)), $patterns))->each->toBe(11)
        ->and(array_sum(str_split($stop)))->toBe(13);
});

test('values start with code B, end with the stop code and carry the checksum', function () {
    // "NG1": N=46, G=39, 1=17 → (104 + 46×1 + 39×2 + 17×3) mod 103 = 279 mod 103 = 73
    expect(Barcode::values('NG1'))->toBe([104, 46, 39, 17, 73, 106]);
});

test('the svg draws bars', function () {
    $svg = Barcode::svg('NG1000000001');

    expect($svg)->toStartWith('<svg')
        // start + 12 characters + checksum have 3 bars each; the stop code has 4
        ->and(substr_count($svg, '<rect x='))->toBe(14 * 3 + 4);
});

test('text outside printable ASCII is refused', function () {
    Barcode::values("NG\n1");
})->throws(InvalidArgumentException::class);
