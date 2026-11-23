<?php

use App\Support\Money;

it('reports the decimal places each currency uses', function (string $currency, int $decimals) {
    expect(Money::decimals($currency))->toBe($decimals)
        ->and(Money::factor($currency))->toBe(10 ** $decimals);
})->with([
    'Egyptian pound' => ['EGP', 2],
    'Kuwaiti dinar' => ['KWD', 3],
    'Japanese yen' => ['JPY', 0],
]);

it('converts decimal amounts to minor units', function (string|int|float $amount, string $currency, int $minor) {
    expect(Money::toMinor($amount, $currency))->toBe($minor);
})->with([
    'two decimals' => ['125.50', 'EGP', 12550],
    'one decimal' => ['125.5', 'EGP', 12550],
    'whole number string' => ['125', 'EGP', 12500],
    'integer' => [125, 'EGP', 12500],
    'float that is inexact in binary' => [19.99, 'EGP', 1999],
    'leading decimal point' => ['.5', 'EGP', 50],
    'surrounding whitespace' => [' 10.00 ', 'EGP', 1000],
    'extra digit rounds half up' => ['0.295', 'EGP', 30],
    'extra digits below half round down' => ['12.3449', 'EGP', 1234],
    'three-decimal currency' => ['1.5', 'KWD', 1500],
    'zero-decimal currency' => ['1500', 'JPY', 1500],
]);

it('rejects amounts that are not decimal numbers', function (string $amount) {
    Money::toMinor($amount, 'EGP');
})->throws(InvalidArgumentException::class)->with(['', '.', 'abc', '1,5', '1.2.3']);

it('converts minor units back to decimal strings', function (int $minor, string $currency, string $decimal) {
    expect(Money::toDecimal($minor, $currency))->toBe($decimal);
})->with([
    [12550, 'EGP', '125.50'],
    [5, 'EGP', '0.05'],
    [0, 'EGP', '0.00'],
    [1500, 'KWD', '1.500'],
    [1500, 'JPY', '1500'],
]);

it('formats minor units for display and API responses', function () {
    // ICU separates the currency code from the amount with a non-breaking space.
    expect(Money::format(12550, 'EGP', 'en'))->toBe("EGP\u{A0}125.50")
        ->and(Money::toArray(12550, 'EGP'))->toBe([
            'amount' => 12550,
            'currency' => 'EGP',
            'formatted' => "EGP\u{A0}125.50",
        ]);
});
