<?php

declare(strict_types=1);

namespace Velm\Modules\Base;

use Velm\Environment;

/**
 * Ensures ISO-4217 currencies exist for geo and company bootstrap.
 */
final class CurrencyBootstrap
{
    /** @var array<string, array{full_name: string, symbol: string, decimal_places: int}> */
    public const PROFILES = [
        'EUR' => ['full_name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
        'USD' => ['full_name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
        'GBP' => ['full_name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
        'CHF' => ['full_name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimal_places' => 2],
        'JPY' => ['full_name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0],
        'CAD' => ['full_name' => 'Canadian Dollar', 'symbol' => 'CA$', 'decimal_places' => 2],
        'AUD' => ['full_name' => 'Australian Dollar', 'symbol' => 'A$', 'decimal_places' => 2],
        'CNY' => ['full_name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_places' => 2],
        'INR' => ['full_name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_places' => 2],
        'BRL' => ['full_name' => 'Brazilian Real', 'symbol' => 'R$', 'decimal_places' => 2],
        'KES' => ['full_name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'decimal_places' => 2],
        'NGN' => ['full_name' => 'Nigerian Naira', 'symbol' => '₦', 'decimal_places' => 2],
        'ZAR' => ['full_name' => 'South African Rand', 'symbol' => 'R', 'decimal_places' => 2],
    ];

    /**
     * Demo rates expressed as units of currency per 1 EUR (used to derive cross rates).
     *
     * @var array<string, float>
     */
    public const DEMO_RATES_PER_EUR = [
        'EUR' => 1.0,
        'USD' => 1.08,
        'GBP' => 0.86,
        'CHF' => 0.96,
        'JPY' => 163.0,
        'CAD' => 1.47,
        'AUD' => 1.65,
        'CNY' => 7.85,
        'INR' => 90.5,
        'BRL' => 5.9,
        'KES' => 129.0,
        'NGN' => 1680.0,
        'ZAR' => 20.5,
    ];

    /** @var list<string> */
    private const ZERO_DECIMAL_CODES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    /** @var list<string> */
    private const THREE_DECIMAL_CODES = [
        'BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND',
    ];

    public static function decimalPlacesForCode(string $code): int
    {
        $code = strtoupper(trim($code));

        if (in_array($code, self::ZERO_DECIMAL_CODES, true)) {
            return 0;
        }

        if (in_array($code, self::THREE_DECIMAL_CODES, true)) {
            return 3;
        }

        return 2;
    }

    public static function ensureCode(Environment $env, string $code): ?int
    {
        $code = strtoupper(trim($code));

        if ($code === '' || ! $env->registry->has('res.currency')) {
            return null;
        }

        $existing = $env->model('res.currency')->search([['name', '=', $code]], limit: 1);

        if ($existing->count() > 0) {
            return $existing->ids()[0];
        }

        $profile = self::PROFILES[$code] ?? null;

        return $env->model('res.currency')->create([
            'name' => $code,
            'full_name' => $profile['full_name'] ?? $code,
            'symbol' => $profile['symbol'] ?? $code,
            'decimal_places' => $profile['decimal_places'] ?? self::decimalPlacesForCode($code),
            'active' => false,
        ])->ids()[0];
    }

    public static function demoRatePerEur(string $code): ?float
    {
        $code = strtoupper(trim($code));

        return self::DEMO_RATES_PER_EUR[$code] ?? null;
    }
}
