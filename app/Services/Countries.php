<?php

namespace App\Services;

use Collator;
use ResourceBundle;

/**
 * The world's countries and their English names, from PHP's intl data, so the list works even when GeoNames is down.
 */
final class Countries
{
    /**
     * Codes in intl's region list that aren't countries: groupings, test codes, "unknown", and territories that
     * ISO 3166 only reserves. What's left is ISO's 249 countries plus Kosovo (XK), which GeoNames uses too.
     */
    private const array NOT_COUNTRIES = ['EU', 'EZ', 'UN', 'ZZ', 'QO', 'XA', 'XB', 'AC', 'CP', 'DG', 'EA', 'IC', 'TA', 'CQ'];

    /**
     * Every country, by code, in name order, e.g. ['AF' => 'Afghanistan', 'AX' => 'Åland Islands', …].
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        /** @var ResourceBundle $regions PHP's intl extension always ships ICU's English region names. */
        $regions = ResourceBundle::create('en', 'ICUDATA-region')->get('Countries');
        $countries = [];

        foreach ($regions as $code => $name) {
            if (preg_match('/^[A-Z]{2}$/', $code) === 1 && ! in_array($code, self::NOT_COUNTRIES, true)) {
                $countries[$code] = $name;
            }
        }

        (new Collator('en'))->asort($countries);

        return $countries;
    }

    /**
     * The country's English name, or null when the code isn't a country.
     */
    public static function name(string $code): ?string
    {
        return self::all()[$code] ?? null;
    }
}
