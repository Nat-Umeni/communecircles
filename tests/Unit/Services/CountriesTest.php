<?php

namespace Tests\Unit\Services;

use App\Services\Countries;
use Collator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CountriesTest extends TestCase
{
    public function test_names_a_country_by_its_code(): void
    {
        $this->assertSame('United Kingdom', Countries::name('GB'));
        $this->assertSame('Liechtenstein', Countries::name('LI'));
    }

    #[DataProvider('notCountries')]
    public function test_has_no_name_for_a_code_that_is_not_a_country(string $code): void
    {
        $this->assertNull(Countries::name($code));
    }

    public function test_lists_the_249_iso_countries_and_kosovo_by_code(): void
    {
        $countries = Countries::all();

        $this->assertCount(250, $countries);
        $this->assertSame('United Kingdom', $countries['GB']);
        $this->assertSame('Kosovo', $countries['XK']);
    }

    public function test_lists_the_countries_in_name_order(): void
    {
        $names = array_values(Countries::all());
        $sorted = $names;
        (new Collator('en'))->sort($sorted);

        $this->assertSame($sorted, $names);
    }

    #[DataProvider('notCountries')]
    public function test_leaves_out_codes_that_are_not_countries(string $code): void
    {
        $this->assertArrayNotHasKey($code, Countries::all());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notCountries(): array
    {
        return [
            'the European Union' => ['EU'],
            'the Eurozone' => ['EZ'],
            'the United Nations' => ['UN'],
            'unknown region' => ['ZZ'],
            'outlying Oceania' => ['QO'],
            'a test code' => ['XA'],
            'another test code' => ['XB'],
            'Ascension Island (reserved)' => ['AC'],
            'Clipperton Island (reserved)' => ['CP'],
            'Diego Garcia (reserved)' => ['DG'],
            'Ceuta and Melilla (reserved)' => ['EA'],
            'the Canary Islands (reserved)' => ['IC'],
            'Tristan da Cunha (reserved)' => ['TA'],
            'Sark (reserved)' => ['CQ'],
            'a made-up code' => ['QQ'],
            'a lower-case code' => ['gb'],
            'a numeric region' => ['150'],
        ];
    }
}
