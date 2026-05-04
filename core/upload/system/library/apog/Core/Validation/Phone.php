<?php

namespace Apog\Core\Validation;

/**
 * Phone normalization utility.
 *
 * Converts raw user input into a standardized international format (E.164-like)
 * for supported countries.
 *
 * Supported countries:
 * - GR (Greece)
 * - CY (Cyprus)
 *
 * Features:
 * - Accepts local formats (e.g. 69xxxxxxxx, 2xxxxxxxxx, 8-digit CY numbers)
 * - Accepts international formats (+30, +357, 0030, 00357)
 * - Removes formatting characters (spaces, dashes, parentheses)
 * - Optionally restricts normalization to a specific ISO2 country code
 *
 * Output:
 * - +30XXXXXXXXXX (Greece)
 * - +357XXXXXXXX (Cyprus)
 *
 * Return:
 * - string: normalized phone number
 * - null: invalid or unsupported input
 */
class Phone {

    /**
     * Country-specific phone configuration
     *
     * Structure:
     * - prefix: international dialing prefix
     * - length: expected length of the national significant number (NSN)
     * - pattern: regex for national significant number (NSN)
     *
     * Notes:
     * - GR:
     *   - Mobile: 69XXXXXXXX (10 digits total with leading 69)
     *   - Landline: 2XXXXXXXXX (10 digits total with leading 2)
     *
     * - CY:
     *   - All numbers are 8 digits
     */
    private const COUNTRIES = [
        // Greece (+30)
        'GR'  => [
            'name'    => 'Greece',
            'prefix'  => '30',
            'length'  => 10,
            'pattern' => '(?:69\d{8}|2\d{9})'
        ],

        // Cyprus (+357)
        'CY' => [
            'name'    => 'Cyprus',
            'prefix'  => '357',
            'length'  => 8,
            'pattern' => '(?:[2-9]\d{7})'
        ],
    ];

    /**
     * Normalize a raw phone number into international format (E.164-like).
     *
     * If a country code is provided, normalization will only attempt
     * matching rules for that country. Otherwise all supported countries
     * are evaluated.
     *
     * @param string|null $phone Raw user input phone number
     * @param string|null $countryCode Optional ISO2 country code (e.g. GR, CY)
     *
     * @return string|null Normalized phone number in international format,
     *                     or null if input cannot be parsed for any supported country.
     */
    public static function normalize(?string $phone, ?string $countryCode = null): ?string {
        if (empty(trim($phone))) {
            return null;
        }

        // Remove formatting characters (spaces, dashes, parentheses)
        // We keep digits and plus sign for international detection
        $cleanPhone = preg_replace('/(?!^\+)[^\d]/', '', $phone);

        $countryCode = $countryCode ? strtoupper($countryCode) : null;
        if ($countryCode && isset(self::COUNTRIES[$countryCode])) {
            $countries = [$countryCode => self::COUNTRIES[$countryCode]];
        } else {
            $countries = self::COUNTRIES;
        }

        // Try matching against configured country rules
        foreach ($countries as $code => $country) {
            $prefix = $country['prefix'];
            $pattern = $country['pattern'];

            // Match optional international prefix or local format
            $regex = '/^(?:\+' . $prefix . '|00' . $prefix . ')?(' . $pattern . ')$/';

            if (preg_match($regex, $cleanPhone, $matches)) {
                $subscriberNumber = $matches[1];
                if (!empty($subscriberNumber)) {
                    return '+' . $prefix . $subscriberNumber;
                }
            }
        }

        return null;
    }
}
