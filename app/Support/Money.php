<?php

namespace App\Support;

use Illuminate\Support\Number;
use InvalidArgumentException;
use NumberFormatter;

/**
 * Money is stored as integer minor units (piastres, cents, fils) of the installation's currency.
 */
final class Money
{
    /**
     * Decimal places per currency code, resolved once from ICU.
     *
     * @var array<string, int>
     */
    private static array $decimalsByCurrency = [];

    /**
     * Get the installation's ISO 4217 currency code.
     */
    public static function currency(): string
    {
        return strtoupper((string) config('app.currency'));
    }

    /**
     * Get the number of decimal places a currency uses: 2 for EGP, 3 for KWD, 0 for JPY.
     */
    public static function decimals(?string $currency = null): int
    {
        $currency = strtoupper($currency ?? self::currency());

        return self::$decimalsByCurrency[$currency] ??= (int) (new NumberFormatter("en@currency={$currency}", NumberFormatter::CURRENCY))
            ->getAttribute(NumberFormatter::FRACTION_DIGITS);
    }

    /**
     * Get the number of minor units in one major unit: 100 for EGP.
     */
    public static function factor(?string $currency = null): int
    {
        return 10 ** self::decimals($currency);
    }

    /**
     * Convert a decimal amount such as "125.5" into minor units (12550), rounding half up.
     *
     * The amount is parsed as a string so that no floating-point arithmetic is involved.
     */
    public static function toMinor(string|int|float $amount, ?string $currency = null): int
    {
        $decimals = self::decimals($currency);
        $value = trim((string) $amount);

        if (preg_match('/^(-?)(\d*)(?:\.(\d*))?$/', $value, $matches) !== 1 || ($matches[2] === '' && ($matches[3] ?? '') === '')) {
            throw new InvalidArgumentException("[{$value}] is not a valid decimal amount.");
        }

        $sign = $matches[1];
        $whole = $matches[2] === '' ? '0' : $matches[2];
        $fraction = str_pad($matches[3] ?? '', $decimals + 1, '0');

        $minor = (int) ($whole.substr($fraction, 0, $decimals));

        if ((int) $fraction[$decimals] >= 5) {
            $minor++;
        }

        return $sign === '-' ? -$minor : $minor;
    }

    /**
     * Convert minor units into a plain decimal string ("125.50") suitable for form inputs.
     */
    public static function toDecimal(int $minor, ?string $currency = null): string
    {
        $decimals = self::decimals($currency);

        if ($decimals === 0) {
            return (string) $minor;
        }

        $factor = 10 ** $decimals;
        $absolute = abs($minor);

        return ($minor < 0 ? '-' : '').intdiv($absolute, $factor).'.'.str_pad((string) ($absolute % $factor), $decimals, '0', STR_PAD_LEFT);
    }

    /**
     * Format minor units for display, e.g. "EGP 125.50".
     */
    public static function format(int $minor, ?string $currency = null, ?string $locale = null): string
    {
        $currency = strtoupper($currency ?? self::currency());
        $decimals = self::decimals($currency);

        return (string) Number::currency($minor / (10 ** $decimals), in: $currency, locale: $locale, precision: $decimals);
    }

    /**
     * Represent minor units in API responses.
     *
     * @return array{amount: int, currency: string, formatted: string}
     */
    public static function toArray(int $minor, ?string $currency = null): array
    {
        $currency = strtoupper($currency ?? self::currency());

        return [
            'amount' => $minor,
            'currency' => $currency,
            'formatted' => self::format($minor, $currency),
        ];
    }
}
