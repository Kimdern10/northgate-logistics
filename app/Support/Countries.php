<?php

namespace App\Support;

/**
 * Countries the company ships to, grouped into regions for pricing.
 */
class Countries
{
    /**
     * @var array<string, array{0: string, 1: string}> code => [name, region]
     */
    public const array LIST = [
        'NG' => ['Nigeria', 'Africa'],
        'GH' => ['Ghana', 'Africa'],
        'KE' => ['Kenya', 'Africa'],
        'ZA' => ['South Africa', 'Africa'],
        'EG' => ['Egypt', 'Africa'],
        'MA' => ['Morocco', 'Africa'],
        'SN' => ['Senegal', 'Africa'],
        'CI' => ["Côte d'Ivoire", 'Africa'],
        'CM' => ['Cameroon', 'Africa'],
        'RW' => ['Rwanda', 'Africa'],
        'GB' => ['United Kingdom', 'Europe'],
        'IE' => ['Ireland', 'Europe'],
        'FR' => ['France', 'Europe'],
        'DE' => ['Germany', 'Europe'],
        'NL' => ['Netherlands', 'Europe'],
        'BE' => ['Belgium', 'Europe'],
        'ES' => ['Spain', 'Europe'],
        'IT' => ['Italy', 'Europe'],
        'PT' => ['Portugal', 'Europe'],
        'CH' => ['Switzerland', 'Europe'],
        'SE' => ['Sweden', 'Europe'],
        'PL' => ['Poland', 'Europe'],
        'TR' => ['Türkiye', 'Europe'],
        'US' => ['United States', 'Americas'],
        'CA' => ['Canada', 'Americas'],
        'MX' => ['Mexico', 'Americas'],
        'BR' => ['Brazil', 'Americas'],
        'AR' => ['Argentina', 'Americas'],
        'CL' => ['Chile', 'Americas'],
        'AE' => ['United Arab Emirates', 'Middle East'],
        'SA' => ['Saudi Arabia', 'Middle East'],
        'QA' => ['Qatar', 'Middle East'],
        'IL' => ['Israel', 'Middle East'],
        'CN' => ['China', 'Asia Pacific'],
        'HK' => ['Hong Kong', 'Asia Pacific'],
        'JP' => ['Japan', 'Asia Pacific'],
        'KR' => ['South Korea', 'Asia Pacific'],
        'IN' => ['India', 'Asia Pacific'],
        'SG' => ['Singapore', 'Asia Pacific'],
        'MY' => ['Malaysia', 'Asia Pacific'],
        'AU' => ['Australia', 'Asia Pacific'],
        'NZ' => ['New Zealand', 'Asia Pacific'],
    ];

    /**
     * @return array<string, string> code => name, sorted by name
     */
    public static function options(): array
    {
        $options = array_map(fn (array $country): string => $country[0], self::LIST);
        asort($options);

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::LIST);
    }

    public static function name(string $code): string
    {
        return self::LIST[strtoupper($code)][0] ?? strtoupper($code);
    }

    public static function region(string $code): ?string
    {
        return self::LIST[strtoupper($code)][1] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function regions(): array
    {
        return array_values(array_unique(array_column(self::LIST, 1)));
    }
}
