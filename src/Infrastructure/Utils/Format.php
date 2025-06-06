<?php

namespace App\Infrastructure\Utils;

/**
 * Hulpmethoden voor het formatteren van prijzen, datums en datum–tijd-waarden.
 * Dit vervangt de losse functies formatPrice(), formatDate() en formatDateTime() uit legacy-code.
 */
final class Format
{
    private function __construct()
    {
    }

    /**
     * Formatteer een prijs in euro's met twee decimalen en nl_NL-notatie.
     */
    public static function price(float|int|string $value): string
    {
        $amount = (float) $value;
        return '€ ' . number_format($amount, 2, ',', '.');
    }

    /**
     * Formatteer een datum naar het opgegeven formaat (standaard d-m-Y).
     */
    public static function date(string|int $date, string $format = 'd-m-Y'): string
    {
        return date($format, is_numeric($date) ? (int) $date : strtotime($date));
    }

    /**
     * Formatteer een datum/tijd naar d-m-Y H:i (24u-notatie).
     */
    public static function dateTime(string|int $dateTime, string $format = 'd-m-Y H:i'): string
    {
        return date($format, is_numeric($dateTime) ? (int) $dateTime : strtotime($dateTime));
    }
}
